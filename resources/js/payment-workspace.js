document.querySelectorAll('[data-payment-workspace]').forEach(workspace => {
    const navigation = workspace.querySelector('[data-payment-tabs]');
    const tabs = [...workspace.querySelectorAll('[data-payment-tab]')];
    const panels = [...workspace.querySelectorAll('[data-payment-panel]')];
    if (!navigation || !tabs.length) return;
    navigation.setAttribute('role', 'tablist');
    tabs.forEach(tab => {
        tab.setAttribute('role', 'tab');
        tab.setAttribute('aria-controls', tab.hash.slice(1));
    });
    panels.forEach(panel => panel.setAttribute('role', 'tabpanel'));
    const activate = (hash, focus = false) => {
        const current = tabs.find(tab => tab.hash === hash) ?? tabs[0];
        tabs.forEach(tab => {
            const selected = tab === current;
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
        });
        panels.forEach(panel => { panel.hidden = '#'+panel.id !== current.hash; });
        if (focus) current.focus();
        return current;
    };
    navigation.addEventListener('click', event => {
        const tab = event.target.closest('[data-payment-tab]');
        if (!tab) return;
        event.preventDefault();
        activate(tab.hash);
        history.replaceState(null, '', tab.hash);
    });
    navigation.addEventListener('keydown', event => {
        const index = tabs.indexOf(document.activeElement);
        if (index < 0 || !['ArrowLeft','ArrowRight','Home','End'].includes(event.key)) return;
        event.preventDefault();
        const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length-1 : (index+(event.key === 'ArrowRight' ? 1 : -1)+tabs.length)%tabs.length;
        activate(tabs[next].hash, true);
        history.replaceState(null, '', tabs[next].hash);
    });
    // Open a hidden payment panel before the existing installment/payment handlers run.
    workspace.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!link || link.hasAttribute('data-payment-tab')) return;
        if (link.origin === location.origin && link.pathname === location.pathname && panels.some(panel => '#'+panel.id === link.hash)) activate(link.hash);
    }, true);
    window.addEventListener('hashchange', () => activate(location.hash));
    activate(workspace.dataset.paymentHasErrors === 'true' && panels.some(panel => panel.id === 'payment-section') ? '#payment-section' : (location.hash || workspace.dataset.paymentDefaultPanel));
});
