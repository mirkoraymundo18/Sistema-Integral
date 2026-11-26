const requestForm = document.getElementById('request-form');
if (requestForm) {
    const details = document.getElementById('nueva-solicitud');
    const product = requestForm.elements.product_id;
    const quantity = requestForm.elements.cantidad;
    const status = document.getElementById('request-stock');
    const search = requestForm.querySelector('[data-request-product-search]');
    const options = [...product.options].filter(option => option.value);
    const placeholder = product.options[0];
    const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim();
    const update = () => {
        const selected = product.selectedOptions[0];
        if (!selected?.value) {
            status.textContent = product.options.length > 1 ? 'Selecciona un producto para consultar su stock.' : 'No hay materiales que coincidan con la búsqueda.';
            return;
        }
        quantity.step = selected.dataset.step;
        quantity.min = selected.dataset.step;
        const available = Number(selected.dataset.stock);
        status.textContent = 'Disponible: '+available+' '+selected.dataset.unit+'.'+(quantity.valueAsNumber > available ? ' Puedes solicitarlo; la entrega requiere disponibilidad.' : '');
    };
    search?.addEventListener('input', () => {
        const previous = product.value;
        const matches = options.filter(option => normalize(option.textContent).includes(normalize(search.value)));
        product.replaceChildren(placeholder, ...matches);
        product.value = matches.some(option => option.value === previous) ? previous : '';
        update();
    });
    document.querySelectorAll('[data-open-request]').forEach(link => link.addEventListener('click', () => {
        details.open = true;
        product.focus({preventScroll:true});
    }));
    requestForm.querySelector('[data-close-request]')?.addEventListener('click', () => { details.open = false; details.querySelector('summary').focus(); });
    product.addEventListener('change',update);
    quantity.addEventListener('input',update);
    update();
}
