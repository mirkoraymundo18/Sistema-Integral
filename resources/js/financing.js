document.querySelectorAll('[data-financing]').forEach(root => {
    const mode = root.querySelector('[name=modalidad]');
    const plan = root.querySelector('[name=financing_plan_id]');
    const initial = root.querySelector('[name=inicial]');
    const first = root.querySelector('[name=primera_cuota]');
    const cash = root.querySelector('[name=fecha_contado]');
    const summary = root.querySelector('[data-finance-summary]');
    const table = root.querySelector('[data-finance-preview]');
    const body = root.querySelector('[data-finance-rows]');
    const total = BigInt(root.dataset.totalCents);
    const decimal = value => (value / 100n).toString() + '.' + (value % 100n).toString().padStart(2,'0');
    const cents = value => {
        if (!/^[0-9]{1,12}(?:\.[0-9]{1,2})?$/.test(value)) return null;
        const [whole, part = ''] = value.split('.');
        return BigInt(whole) * 100n + BigInt(part.padEnd(2,'0'));
    };
    const money = value => new Intl.NumberFormat('es-PE', {style:'currency',currency:root.dataset.currency}).format(Number(value) / 100);
    const dateLabel = value => value.split('-').reverse().join('/');
    const monthlyDate = (value, offset) => {
        const [year, month, day] = value.split('-').map(Number);
        const maximum = new Date(Date.UTC(year, month + offset, 0)).getUTCDate();
        return new Date(Date.UTC(year, month - 1 + offset, Math.min(day, maximum))).toISOString().slice(0,10);
    };
    function update() {
        const financed = mode.value === 'CUOTAS';
        root.querySelectorAll('[data-finance-mode]').forEach(section => {
            const visible = section.dataset.financeMode === mode.value;
            section.hidden = !visible;
            section.querySelectorAll('input, select').forEach(field => { field.disabled = !visible; field.required = visible; });
        });
        initial.setCustomValidity('');
        table.hidden = true; body.replaceChildren();
        const rows = [];
        if (!financed) {
            summary.textContent = 'Pago total: ' + money(total) + '. Al guardar se registra el acuerdo; el cobro se registra por separado.';
            if (!cash.value || !cash.validity.valid) return;
            rows.push(['Pago único', cash.value, total]);
        } else {
            const option = plan.selectedOptions[0];
            if (!plan.value) { summary.textContent = 'Selecciona un plan para calcular la inicial mínima y las cuotas.'; return; }
            const count = Number(option.dataset.months);
            const basis = cents(option.dataset.minimum);
            const minimum = (total * basis + 9999n) / 10000n;
            if (total < BigInt(count)) {
                initial.setCustomValidity('El precio no permite ese número de cuotas.');
                summary.textContent = 'Elige un plan con menos cuotas para este precio.'; return;
            }
            initial.min = decimal(minimum); initial.max = decimal(total - BigInt(count));
            if (initial.value === '') initial.value = decimal(minimum);
            const start = cents(initial.value);
            if (start === null || start < minimum || start >= total || total - start < BigInt(count)) {
                initial.setCustomValidity('La inicial mínima es ' + money(minimum) + ' y debe dejar saldo para todas las cuotas.');
                summary.textContent = 'Revisa la inicial: el mínimo del plan es ' + money(minimum) + '.'; return;
            }
            const balance = total - start;
            const regular = balance / BigInt(count);
            summary.textContent = 'Inicial ' + money(start) + ' · Saldo a financiar ' + money(balance) + ' en ' + count + ' cuotas mensuales. Sin intereses; el último importe ajusta los céntimos.';
            if (!first.value || !first.validity.valid) return;
            if (start > 0n) rows.push(['Inicial', root.dataset.initialDate, start]);
            for (let index = 0; index < count; index++) {
                const amount = index === count - 1 ? balance - regular * BigInt(count - 1) : regular;
                rows.push(['Cuota ' + (index + 1), monthlyDate(first.value, index), amount]);
            }
        }
        const fragment = document.createDocumentFragment();
        for (const [label, due, amount] of rows) {
            const row = document.createElement('tr');
            for (const value of [label, dateLabel(due), money(amount)]) {
                const cell = document.createElement('td'); cell.textContent = value; row.append(cell);
            }
            fragment.append(row);
        }
        body.append(fragment); table.hidden = false;
    }
    root.addEventListener('change', update);
    root.addEventListener('input', event => { if (event.target.tagName === 'INPUT') update(); });
    update();
});
