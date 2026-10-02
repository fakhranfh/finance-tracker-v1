import './shared/modal.js';

const COLLAPSE_STORAGE_KEY = 'dashboard-sidebar-collapsed';

const sidebar = document.querySelector('[data-sidebar]');
const backdrop = document.querySelector('[data-sidebar-backdrop]');
const mobileToggle = document.querySelector('[data-sidebar-toggle]');
const collapseToggle = document.querySelector('[data-sidebar-collapse-toggle]');
const labels = document.querySelectorAll('[data-sidebar-label]');

function openSidebar() {
    sidebar?.classList.remove('-translate-x-full');
    backdrop?.classList.remove('hidden');
}

function closeSidebar() {
    sidebar?.classList.add('-translate-x-full');
    backdrop?.classList.add('hidden');
}

mobileToggle?.addEventListener('click', () => {
    const isOpen = !sidebar?.classList.contains('-translate-x-full');
    isOpen ? closeSidebar() : openSidebar();
});

backdrop?.addEventListener('click', closeSidebar);
document.querySelector('[data-sidebar-close]')?.addEventListener('click', closeSidebar);

function setCollapsed(collapsed) {
    sidebar?.classList.toggle('lg:w-20', collapsed);
    sidebar?.classList.toggle('lg:w-64', !collapsed);
    labels.forEach((label) => label.classList.toggle('lg:hidden', collapsed));
    localStorage.setItem(COLLAPSE_STORAGE_KEY, collapsed ? '1' : '0');
}

collapseToggle?.addEventListener('click', () => {
    setCollapsed(!sidebar?.classList.contains('lg:w-20'));
});

if (localStorage.getItem(COLLAPSE_STORAGE_KEY) === '1') {
    setCollapsed(true);
}
