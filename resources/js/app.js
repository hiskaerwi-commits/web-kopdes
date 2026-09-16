const toggle = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#navigation');
const closeMenu = () => {
    toggle?.setAttribute('aria-expanded', 'false');
    toggle?.setAttribute('aria-label', 'Buka menu');
    navigation?.classList.remove('is-open');
    navigation?.querySelectorAll('details[open]').forEach(el => { el.open = false; });
};
toggle?.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
    navigation.classList.toggle('is-open', open);
});
navigation?.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        const isOpen = toggle?.getAttribute('aria-expanded') === 'true';
        closeMenu();
        if (isOpen) toggle.focus();
    }
});
document.addEventListener('click', event => {
    navigation?.querySelectorAll('details[open]').forEach(el => {
        if (!el.contains(event.target)) el.open = false;
    });
});
const networkRows = [...document.querySelectorAll('[data-network-row]')];
if (networkRows.length) {
    const PAGE_SIZE = 6;
    const emptyState = document.querySelector('[data-network-empty]');
    const countLabel = document.querySelector('[data-network-count]');
    const pageLabel = document.querySelector('[data-network-page-label]');
    const pagination = document.querySelector('[data-network-pagination]');
    const prevButton = document.querySelector('[data-network-prev]');
    const nextButton = document.querySelector('[data-network-next]');
    let query = '';
    let page = 0;

    const render = () => {
        const filtered = query ? networkRows.filter(row => row.textContent.toLocaleLowerCase('id').includes(query)) : networkRows;
        const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
        page = Math.min(page, totalPages - 1);
        const visible = new Set(filtered.slice(page * PAGE_SIZE, (page + 1) * PAGE_SIZE));
        networkRows.forEach(row => { row.hidden = !visible.has(row); });
        emptyState.hidden = filtered.length > 0;
        countLabel.textContent = filtered.length + ' koperasi';
        pageLabel.textContent = totalPages > 1 ? (page + 1) + ' / ' + totalPages : '';
        pagination.hidden = totalPages <= 1;
        prevButton.disabled = page === 0;
        nextButton.disabled = page >= totalPages - 1;
    };

    document.querySelector('[data-network-search]')?.addEventListener('input', event => {
        query = event.target.value.toLocaleLowerCase('id').trim();
        page = 0;
        render();
    });
    prevButton?.addEventListener('click', () => { page--; render(); });
    nextButton?.addEventListener('click', () => { page++; render(); });
    render();
}
document.querySelectorAll('[data-regulation-filter]').forEach(button => button.addEventListener('click', () => {
    const category = button.dataset.regulationFilter;
    document.querySelectorAll('[data-regulation-filter]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
    document.querySelectorAll('[data-regulation-category]').forEach(item => { item.hidden = item.dataset.regulationCategory !== category; });
}));
document.querySelectorAll('[data-carousel]').forEach(track => {
    const step = () => {
        const card = track.querySelector(':scope > *');
        const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
        return (card?.getBoundingClientRect().width || 0) + gap;
    };
    document.querySelectorAll(`[data-carousel-prev="${track.id}"]`).forEach(button => button.addEventListener('click', () => track.scrollBy({left: -step(), behavior: 'smooth'})));
    document.querySelectorAll(`[data-carousel-next="${track.id}"]`).forEach(button => button.addEventListener('click', () => track.scrollBy({left: step(), behavior: 'smooth'})));
});
