document.querySelectorAll('[data-sale-payment]').forEach(form => {
    const schedule = JSON.parse(form.querySelector('[data-payment-schedule]').textContent).map(row => ({...row, saldo:BigInt(row.saldo_centimos)}));
    const monthly = schedule.filter(row => row.numero > 0);
    const balance = BigInt(form.dataset.balanceCents);
    const mode = form.elements.modo_pago;
    const count = form.elements.cantidad_cuotas;
    const amount = form.elements.monto;
    const date = form.elements.fecha_pago;
    const method = form.elements.metodo;
    const reference = form.elements.referencia;
    const countLabel = form.querySelector('[data-payment-count]');
    const summary = form.querySelector('[data-payment-summary]');
    const balanceText = form.querySelector('[data-payment-balance]');
    const body = form.querySelector('[data-payment-rows]');
    const help = form.querySelector('[data-payment-amount-help]');
    const submit = form.querySelector('[data-payment-submit]');
    const review = form.closest('section').querySelector('[data-payment-review]');
    const currency = form.dataset.currency;
    const totalText = form.querySelector('[data-payment-total]');
    const money = cents => new Intl.NumberFormat('es-PE',{style:'currency',currency}).format(Number(cents)/100);
    const decimal = cents => (cents/100n).toString()+'.'+(cents%100n).toString().padStart(2,'0');
    const parse = value => {
        if (!/^[0-9]{1,12}(?:\.[0-9]{1,2})?$/.test(value)) return null;
        const [whole,part='']=value.split('.'); return BigInt(whole)*100n+BigInt(part.padEnd(2,'0'));
    };
    const dateLabel = value => value.split('-').reverse().join('/');
    let approved = false;
    let application = [];
    let paymentAmount = 0n;
    function update() {
        const advance = mode.value === 'ADELANTO';
        if (countLabel) countLabel.hidden = !advance;
        if (count) { count.disabled = !advance; count.required = advance; }
        amount.readOnly = mode.value !== 'LIBRE';
        amount.setCustomValidity('');
        reference.required = method.value !== 'EFECTIVO';
        reference.placeholder = reference.required ? 'Ingresa el número de operación' : 'Opcional para efectivo';
        let value;
        if (mode.value === 'SIGUIENTE') value = schedule[0]?.saldo ?? 0n;
        else if (mode.value === 'SALDO') value = balance;
        else if (advance) {
            const number = Number(count?.value ?? 0);
            value = schedule.filter(row=>row.numero===0).reduce((sum,row)=>sum+row.saldo,0n)
                + monthly.slice(0,number).reduce((sum,row)=>sum+row.saldo,0n);
        } else value = parse(amount.value);
        if (mode.value !== 'LIBRE') amount.value = decimal(value);
        help.textContent = mode.value === 'LIBRE' ? 'Puedes ingresar un abono parcial sin superar el saldo.' : 'Calculado automáticamente con los saldos pendientes de esta venta.';
        body.replaceChildren(); application=[];
        if (value===null || value<=0n || value>balance) {
            if (totalText) totalText.textContent = '—';
            summary.textContent = 'Ingresa un importe positivo de hasta '+money(balance)+'.';
            balanceText.textContent = '';
            amount.setCustomValidity('Revisa el importe del pago.'); return false;
        }
        let remaining=value;
        const fragment=document.createDocumentFragment();
        for (const row of schedule) {
            if (remaining===0n) break;
            const part = remaining < row.saldo ? remaining : row.saldo;
            application.push({...row,part,after:row.saldo-part});
            const tr=document.createElement('tr');
            for(const text of [row.concepto,dateLabel(row.vence_el),money(part),money(row.saldo-part)]) {
                const td=document.createElement('td');td.textContent=text;tr.append(td);
            }
            fragment.append(tr);remaining-=part;
        }
        if (schedule.length && remaining>0n) {
            summary.textContent='El cronograma no coincide con el saldo. Recarga la ficha antes de registrar el pago.';return false;
        }
        if (!schedule.length) {
            const tr=document.createElement('tr');const td=document.createElement('td');td.colSpan=4;
            td.textContent='Abono sobre el saldo general: esta venta todavía no tiene cronograma.';tr.append(td);fragment.append(tr);
        }
        body.append(fragment); paymentAmount=value;
        if (totalText) totalText.textContent=money(value);
        const initial = application.find(row=>row.numero===0);
        const concepts = application.length > 3 ? application.length+' conceptos pendientes' : (application.length ? application.map(row=>row.concepto).join(', ') : 'esta venta');
        summary.textContent=money(value)+' se aplicarán a '+concepts+'.'+(advance&&initial?' Incluye la inicial pendiente.':'');
        balanceText.textContent='Saldo total después del pago: '+money(balance-value)+'.';
        return true;
    }
    form.addEventListener('input',()=>{approved=false;update();});
    form.addEventListener('change',()=>{approved=false;update();});
    form.addEventListener('submit',event=>{
        if (!update()) {event.preventDefault();amount.focus();return;}
        if (!approved && typeof review?.showModal==='function') {
            event.preventDefault();
            review.querySelector('[data-payment-review-summary]').textContent=money(paymentAmount)+' · '+method.selectedOptions[0].textContent;
            review.querySelector('[data-payment-review-date]').textContent='Fecha del pago: '+dateLabel(date.value)+(reference.value?' · Referencia: '+reference.value:'');
            const list=review.querySelector('[data-payment-review-rows]');list.replaceChildren();
            for (const row of application) {const li=document.createElement('li');li.textContent=row.concepto+': '+money(row.part)+' · saldo '+money(row.after);list.append(li);}
            review.querySelector('[data-payment-review-balance]').textContent='Saldo general después del pago: '+money(balance-paymentAmount);
            review.showModal();review.querySelector('#payment-review-title').focus();return;
        }
        submit.disabled=true;submit.textContent='Registrando pago…';submit.setAttribute('aria-busy','true');
    });
    review.querySelector('[data-payment-edit]').addEventListener('click',()=>review.close());
    review.querySelector('[data-payment-confirm]').addEventListener('click',()=>{approved=true;review.close();form.requestSubmit();});
    review.addEventListener('close',()=>submit.focus());
    document.querySelectorAll('[data-pay-through][data-payment-sale="'+form.dataset.saleId+'"]').forEach(link=>{
        link.addEventListener('click',event=>{
            event.preventDefault();const number=Number(link.dataset.payThrough);
            const hasAdvance=[...mode.options].some(option=>option.value==='ADELANTO');
            if(number===0 || !hasAdvance) mode.value='SIGUIENTE';
            else {mode.value='ADELANTO';count.value=String(monthly.filter(row=>row.numero<=number).length);}
            approved=false;update();
            form.closest('section').scrollIntoView({behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'start'});
            mode.focus({preventScroll:true});
        });
    });
    window.addEventListener('pageshow',()=>{approved=false;submit.disabled=false;submit.textContent='Revisar y registrar pago';submit.removeAttribute('aria-busy');});
    document.querySelectorAll('[data-payment-mode][data-payment-sale="'+form.dataset.saleId+'"]').forEach(link=>{
        link.addEventListener('click', event=>{
            if (![...mode.options].some(option=>option.value===link.dataset.paymentMode)) return;
            event.preventDefault(); mode.value=link.dataset.paymentMode; approved=false; update();
            form.closest('section').scrollIntoView({behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'start'});
            mode.focus({preventScroll:true});
        });
    });
    update();
});
