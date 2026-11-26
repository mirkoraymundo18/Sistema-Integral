<?php
namespace App\Console\Commands;
use App\Actions\CheckSystemIntegrity;
use Illuminate\Console\Command;
class CheckSystem extends Command {
    protected $signature='sistema:verificar';
    protected $description='Comprobar stock, reservas, ventas y aplicaciones de pagos sin modificar datos';
    public function handle(CheckSystemIntegrity $checker):int {
        $report=$checker->check();
        $this->table(['Registro','Revisados'],collect($report['counts'])->map(fn($n,$name)=>[$name,$n])->values()->all());
        foreach($report['issues'] as $item) $this->line(strtoupper($item['level']).' ['.$item['code'].'] '.$item['detail']);
        $this->line('Errores: '.$report['errors'].'. Observaciones: '.$report['warnings'].'. No se modificaron datos.');
        return $report['errors']?self::FAILURE:self::SUCCESS;
    }
}
