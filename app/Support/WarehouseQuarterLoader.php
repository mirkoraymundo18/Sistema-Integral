<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;
use Throwable;

/** Imports explicitly generated warehouse activity. No sales or client debt is invented. */
class WarehouseQuarterLoader
{
    public function __construct(private PDO $pdo) {}

    public function load(string $through, ?int $adminId = null, ?DateTimeImmutable $clock = null): array
    {
        $zone = new DateTimeZone('America/Lima');
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $through, $zone);
        $now = $clock ? $clock->setTimezone($zone) : new DateTimeImmutable('now', $zone);
        if (!$day || $day->format('Y-m-d') !== $through || $day > $now) {
            throw new RuntimeException('Selecciona una fecha válida que no sea futura.');
        }
        $end = $day->setTime(23, 59, 59);
        if ($end > $now) $end = $now;
        $first = $day->modify('first day of this month')->modify('-2 months');
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (!in_array($driver, ['pgsql', 'sqlite'], true) || $this->pdo->inTransaction()) {
            throw new RuntimeException('La carga requiere PostgreSQL o SQLite y una transacción propia.');
        }

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->beginTransaction();
        try {
            if ($driver === 'pgsql') $this->pdo->query('SELECT pg_advisory_xact_lock(20261125, 3)');
            $lock = $driver === 'pgsql' ? ' FOR UPDATE' : '';
            $adminQuery = "SELECT id FROM users WHERE role = 'ADMINISTRADOR' AND deleted_at IS NULL";
            $args = [];
            if ($adminId !== null) { $adminQuery .= ' AND id = ?'; $args[] = $adminId; }
            $admin = $this->query($adminQuery.' ORDER BY id LIMIT 1'.$lock, $args)->fetchColumn();
            if (!$admin) throw new RuntimeException('Se necesita una cuenta administradora activa.');
            $operator = $this->pdo->query("SELECT id FROM users WHERE role = 'OPERADOR' AND deleted_at IS NULL ORDER BY id LIMIT 1")->fetchColumn() ?: $admin;
            $this->pdo->query('SELECT batch_key FROM warehouse_activity_batches LIMIT 1');
            $products = $this->pdo->query('SELECT p.id, p.nombre, p.stock_actual, p.precio_unitario, p.product_family_id, u.simbolo FROM products p JOIN measurement_units u ON u.id = p.measurement_unit_id WHERE p.deleted_at IS NULL AND p.precio_unitario > 0 ORDER BY p.id'.($driver === 'pgsql' ? ' FOR UPDATE OF p' : ''))->fetchAll(PDO::FETCH_ASSOC);
            $products = $this->chooseProducts($products);
            if (!$products) throw new RuntimeException('Se necesitan productos activos con unidad y precio para preparar la actividad.');
            $recordedAt = $now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            $counts = ['months_created' => 0, 'months_skipped' => 0, 'entries' => 0, 'exits' => 0, 'requests' => 0, 'labels_updated' => 0];

            // Preserve the original wording and IDs in import provenance before changing it.
            $normalizationKey = 'warehouse-opening-label-v1';
            if (!$this->query('SELECT batch_key FROM warehouse_activity_batches WHERE batch_key = ?', [$normalizationKey])->fetchColumn()) {
                $legacy = $this->query('SELECT id, motivo_o_proyecto FROM kardex_movements WHERE motivo_o_proyecto = ?', ['Carga inicial para presentación académica del sistema'])->fetchAll(PDO::FETCH_ASSOC);
                $this->query('UPDATE kardex_movements SET motivo_o_proyecto = ? WHERE motivo_o_proyecto = ?', ['Registro de existencias iniciales', 'Carga inicial para presentación académica del sistema']);
                $counts['labels_updated'] = count($legacy);
                $this->batch($normalizationKey, (int)$admin, ['original_labels' => $legacy], $recordedAt);
            }

            for ($monthIndex = 0; $monthIndex < 3; $monthIndex++) {
                $month = $first->modify('+'.$monthIndex.' months');
                $key = 'warehouse-activity-'.$month->format('Y-m').'-v1';
                if ($this->query('SELECT batch_key FROM warehouse_activity_batches WHERE batch_key = ?', [$key])->fetchColumn()) {
                    $counts['months_skipped']++;
                    continue;
                }
                if ($this->query('SELECT id FROM kardex_movements WHERE motivo_o_proyecto LIKE ? LIMIT 1', ['Abastecimiento programado de almacén · ABA-'.$month->format('Ym').'-%'])->fetchColumn()) {
                    throw new RuntimeException('Ya existe actividad de este mes sin sus metadatos de carga. Revisa el respaldo antes de volver a importarla.');
                }
                $monthEnd = $month->modify('last day of this month')->setTime(23, 59, 59);
                if ($monthEnd > $end) $monthEnd = $end;
                $selected = array_slice($products, 0, 8 + $monthIndex * 2);
                $details = ['from' => $month->format('Y-m-d'), 'through' => $monthEnd->format('Y-m-d'), 'movement_ids' => [], 'request_ids' => [], 'operator_id' => (int)$operator, 'stock_effect' => 'net_zero'];
                foreach ($selected as $index => $product) {
                    $base = match (strtolower($product['simbolo'])) {
                        'm', 'metro' => 40, 'kg' => 20, 'bolsa' => 16, 'par' => 6, 'rollo', 'caja' => 4, default => 8,
                    };
                    $firstQuantity = $base + $monthIndex + $index % 3;
                    $secondQuantity = intdiv($base, 2) + $monthIndex + $index % 2;
                    $stock = $this->cents($product['stock_actual']);
                    $reference = 'ABA-'.$month->format('Ym').'-'.str_pad((string)($index+1), 2, '0', STR_PAD_LEFT);
                    $entry = $this->movement($product, $stock, 'ENTRADA', $firstQuantity + $secondQuantity, 'Abastecimiento programado de almacén · '.$reference, (int)$admin, $this->at($month, $monthEnd, 0.05), $recordedAt);
                    $details['movement_ids'][] = $entry;
                    $counts['entries']++;
                    foreach ([$firstQuantity, $secondQuantity] as $stage => $quantity) {
                        $fraction = $stage === 0 ? 0.15 : 0.48;
                        $front = ['Habilitación de vías internas', 'Cercado perimétrico', 'Instalaciones de agua y desagüe', 'Acondicionamiento de oficina de ventas', 'Seguridad y señalización de obra', 'Mantenimiento de áreas comunes'][$index % 6];
                        $created = $this->at($month, $monthEnd, $fraction);
                        $decided = $this->at($month, $monthEnd, $fraction + 0.04);
                        $delivered = $this->at($month, $monthEnd, $fraction + 0.08);
                        $requestId = $this->request((int)$operator, (int)$product['id'], $quantity, $front.' · Etapa '.($stage+1), 'Aprobado', $created, $decided, (int)$admin, 'Material autorizado para el frente de trabajo.');
                        $movement = $this->movement($product, $stock, 'SALIDA', $quantity, 'Entrega de solicitud #'.$requestId.' · '.$front, (int)$admin, $delivered, $recordedAt);
                        $this->query('UPDATE material_requests SET estado = ?, kardex_movement_id = ?, updated_at = ? WHERE id = ?', ['Entregado', $movement, $delivered, $requestId]);
                        $details['request_ids'][] = $requestId;
                        $details['movement_ids'][] = $movement;
                        $counts['exits']++; $counts['requests']++;
                    }
                    if ($stock !== $this->cents($product['stock_actual'])) throw new RuntimeException('La carga no debe alterar el saldo actual del producto.');
                }
                // Closed historical requests; open work remains only in the most recent month.
                $extra = min(2 + $monthIndex, count($selected));
                foreach (array_slice($selected, 0, $extra) as $product) {
                    $details['request_ids'][] = $this->request((int)$operator, (int)$product['id'], 2, 'Reposición adicional para mantenimiento de obra', 'Rechazado', $this->at($month, $monthEnd, 0.66), $this->at($month, $monthEnd, 0.70), (int)$admin, 'Se unifica con el abastecimiento programado para evitar una solicitud duplicada.');
                    $counts['requests']++;
                    if ($monthIndex === 2) {
                        $details['request_ids'][] = $this->request((int)$operator, (int)$product['id'], 3, 'Material para acondicionamiento del siguiente frente de trabajo', 'Aprobado', $this->at($month, $monthEnd, 0.82), $this->at($month, $monthEnd, 0.86), (int)$admin, 'Cantidad autorizada; pendiente de programar la entrega.');
                        $details['request_ids'][] = $this->request((int)$operator, (int)$product['id'], 2, 'Revisión de abastecimiento para mantenimiento de áreas comunes', 'Pendiente', $this->at($month, $monthEnd, 0.95), null, null, null);
                        $counts['requests'] += 2;
                    }
                }
                $this->batch($key, (int)$admin, $details, $recordedAt);
                $counts['months_created']++;
            }
            $this->pdo->commit();
            return ['from' => $first->format('Y-m-d'), 'through' => $day->format('Y-m-d'), 'origin' => 'GENERATED_SAMPLE', 'counts' => $counts];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function query(string $sql, array $values = []): \PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($values);
        return $statement;
    }

    private function insertId(string $sql, array $values): int
    {
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') return (int)$this->query($sql.' RETURNING id', $values)->fetchColumn();
        $this->query($sql, $values);
        return (int)$this->pdo->lastInsertId();
    }

    private function chooseProducts(array $products): array
    {
        $groups = [];
        foreach ($products as $product) $groups[$product['product_family_id'] ?? 0][] = $product;
        $chosen = [];
        while (count($chosen) < 12) {
            $added = false;
            foreach ($groups as &$group) {
                if ($group && count($chosen) < 12) { $chosen[] = array_shift($group); $added = true; }
            }
            unset($group);
            if (!$added) break;
        }
        return $chosen;
    }

    private function cents(mixed $value): int
    {
        if (!preg_match('/\A[0-9]{1,10}(?:\.[0-9]{1,2})?\z/', (string)$value)) throw new RuntimeException('Saldo de producto inválido.');
        [$whole, $part] = array_pad(explode('.', (string)$value), 2, '');
        return (int)$whole * 100 + (int)str_pad($part, 2, '0');
    }

    private function movement(array $product, int &$stock, string $type, int $quantity, string $reason, int $actor, string $eventAt, string $recordedAt): int
    {
        $stock += ($type === 'ENTRADA' ? 1 : -1) * $quantity * 100;
        if ($stock < 0 || $stock > 214748364700) throw new RuntimeException('La actividad excedería el saldo permitido; no se guardó la carga.');
        $id = $this->insertId('INSERT INTO kardex_movements (product_id, user_id, tipo, cantidad, precio_unitario, motivo_o_proyecto, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [$product['id'], $actor, $type, $quantity, $product['precio_unitario'], $reason, $eventAt, $eventAt]);
        $balance = intdiv($stock, 100).'.'.str_pad((string)($stock % 100), 2, '0', STR_PAD_LEFT);
        $this->query('UPDATE products SET stock_actual = ?, updated_at = ? WHERE id = ?', [$balance, $recordedAt, $product['id']]);
        return $id;
    }

    private function request(int $user, int $product, int $quantity, string $reason, string $state, string $created, ?string $decided, ?int $admin, ?string $note): int
    {
        return $this->insertId('INSERT INTO material_requests (user_id, product_id, cantidad, motivo, estado, decided_by, decided_at, decision_note, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$user, $product, $quantity, $reason, $state, $admin, $decided, $note, $created, $decided ?? $created]);
    }

    private function batch(string $key, int $admin, array $details, string $recordedAt): void
    {
        $this->query('INSERT INTO warehouse_activity_batches (batch_key, origin, responsible_id, details, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)', [$key, 'GENERATED_SAMPLE', $admin, json_encode($details, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), $recordedAt, $recordedAt]);
    }

    private function at(DateTimeImmutable $start, DateTimeImmutable $end, float $fraction): string
    {
        $seconds = max(0, $end->getTimestamp() - $start->getTimestamp());
        return $start->modify('+'.(int)floor($seconds * $fraction).' seconds')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
