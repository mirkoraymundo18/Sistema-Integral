// Enhances the server-rendered forms; ordinary submission remains available without JavaScript.
document.querySelectorAll('[data-print-reservation]').forEach(button => {
    button.addEventListener('click', () => window.print());
});
document.querySelectorAll('[data-cancel-reservation]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!window.confirm('¿Cancelar esta reserva y dejar el lote disponible? Se conservará su historial.')) event.preventDefault();
    });
});

const form = document.getElementById('reservation-form');
if (form) {
    form.querySelectorAll('.reservation-js-only').forEach(element => { element.hidden = false; });
    const client = form.elements.client_id;
    const lot = form.elements.land_lot_id;
    const price = form.elements.precio_acordado;
    const date = form.elements.fecha_vencimiento;
    const currency = document.getElementById('reservation-currency');
    const review = document.getElementById('reservation-review');
    const submit = form.querySelector('[data-reservation-submit]');
    const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    const selectedText = (select, fallback) => select.value ? select.selectedOptions[0].textContent.trim() : fallback;
    let approved = false;
    function update(replacePrice = false) {
        const option = lot.selectedOptions[0];
        const money = option?.dataset.currency;
        if (replacePrice) price.value = option?.dataset.price ?? '';
        currency.textContent = money ? 'Moneda: ' + (money === 'PEN' ? 'soles (S/)' : 'dólares (US$)') + '. Este importe no registra un pago.' : 'La moneda corresponde al lote. Este importe no registra un pago.';
        const values = {
            client: selectedText(client, 'Selecciona un cliente'),
            lot: selectedText(lot, 'Selecciona un lote'),
            area: lot.value ? option.dataset.area + ' m²' : 'Por seleccionar',
            price: money && price.value && price.validity.valid ? new Intl.NumberFormat('es-PE', {style:'currency', currency:money}).format(Number(price.value)) : 'Por definir',
            date: date.value && date.validity.valid ? date.value.split('-').reverse().join('/') + ' · 23:59, Perú' : 'Elige una fecha',
        };
        for (const [key, value] of Object.entries(values)) {
            document.querySelectorAll('[data-summary="' + key + '"], [data-review="' + key + '"]').forEach(element => { element.textContent = value; });
        }
        const complete = [Boolean(client.value), Boolean(lot.value), Boolean(price.value && price.validity.valid && date.value && date.validity.valid)].filter(Boolean).length;
        document.getElementById('reservation-progress').textContent = complete === 3 ? 'Todo listo. Revisa el resumen y confirma.' : complete + ' de 3 pasos completos';
        form.querySelectorAll('.reservation-step-title > span').forEach((element, index) => {
            const ready = [Boolean(client.value), Boolean(lot.value), Boolean(price.value && price.validity.valid && date.value && date.validity.valid)][index];
            element.classList.toggle('is-complete', ready);
        });
    }
    form.querySelectorAll('[data-filter-select]').forEach(input => {
        const select = form.elements[input.dataset.filterSelect];
        const options = [...select.options].filter(option => option.value);
        const feedback = form.querySelector('[data-filter-feedback="' + input.dataset.filterSelect + '"]');
        input.addEventListener('input', () => {
            const query = normalize(input.value);
            const matches = options.filter(option => normalize(option.textContent).includes(query));
            // Rebuild native options so filtering also works on mobile select controls.
            const previous = select.value;
            select.replaceChildren(select.options[0], ...matches);
            select.value = matches.some(option => option.value === previous) ? previous : '';
            feedback.textContent = matches.length ? matches.length + ' coincidencias. Elige una en la lista.' : 'Sin coincidencias. Prueba con otro nombre o número.';
            update(select === lot && select.value !== previous);
        });
    });
    lot.addEventListener('change', () => update(true));
    client.addEventListener('change', () => update());
    price.addEventListener('input', () => update());
    date.addEventListener('input', () => update());
    form.querySelectorAll('[data-reservation-days]').forEach(button => {
        button.addEventListener('click', () => {
            const [year, month, day] = date.min.split('-').map(Number);
            const value = new Date(Date.UTC(year, month - 1, day));
            value.setUTCDate(value.getUTCDate() + Number(button.dataset.reservationDays));
            date.value = value.toISOString().slice(0, 10);
            update();
        });
    });
    form.addEventListener('input', () => { approved = false; });
    form.addEventListener('change', () => { approved = false; });
    form.addEventListener('submit', event => {
        if (!approved && typeof review?.showModal === 'function') {
            event.preventDefault(); update(); review.showModal();
            document.getElementById('reservation-review-title').focus();
            return;
        }
        submit.disabled = true; submit.textContent = 'Guardando reserva…';
        submit.setAttribute('aria-busy', 'true');
    });
    review?.querySelector('[data-review-back]').addEventListener('click', () => review.close());
    review?.querySelector('[data-review-confirm]').addEventListener('click', () => {
        approved = true; review.close(); form.requestSubmit();
    });
    review?.addEventListener('close', () => submit.focus());
    window.addEventListener('pageshow', () => {
        approved = false; submit.disabled = false; submit.textContent = 'Confirmar reserva'; submit.removeAttribute('aria-busy');
    });
    update(price.value === '');
}
