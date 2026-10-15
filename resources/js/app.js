import './bootstrap';

const shell = document.querySelector('[data-app-shell]');
const sidebar = document.querySelector('[data-app-sidebar]');
const overlay = document.querySelector('[data-sidebar-overlay]');
const mobileToggle = document.querySelector('[data-mobile-nav-toggle]');
const collapseToggle = document.querySelector('[data-sidebar-collapse]');

if (shell && sidebar) {
    const storageKey = 'energiciency-sidebar-collapsed';
    const desktopBreakpoint = window.matchMedia('(min-width: 768px)');
    let lastFocusedElement = null;

    if (window.innerWidth >= 768 && localStorage.getItem(storageKey) === 'true') {
        shell.classList.add('sidebar-collapsed');
    }
    collapseToggle?.setAttribute('aria-pressed', String(shell.classList.contains('sidebar-collapsed')));
    collapseToggle?.setAttribute('aria-label', shell.classList.contains('sidebar-collapsed') ? 'Perluas sidebar' : 'Ciutkan sidebar');

    const closeMobileNav = (restoreFocus = true) => {
        shell.classList.remove('mobile-nav-open');
        mobileToggle?.setAttribute('aria-expanded', 'false');
        overlay?.classList.add('hidden');
        document.body.style.overflow = '';
        if (restoreFocus && lastFocusedElement instanceof HTMLElement) {
            lastFocusedElement.focus();
        }
        lastFocusedElement = null;
    };

    mobileToggle?.addEventListener('click', () => {
        const isOpen = shell.classList.toggle('mobile-nav-open');
        if (isOpen) {
            lastFocusedElement = mobileToggle;
            document.body.style.overflow = 'hidden';
            sidebar.querySelector('a[href]')?.focus();
        } else {
            document.body.style.overflow = '';
        }
        mobileToggle.setAttribute('aria-expanded', String(isOpen));
        overlay?.classList.toggle('hidden', !isOpen);
    });

    overlay?.addEventListener('click', closeMobileNav);
    sidebar.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', () => {
            if (!desktopBreakpoint.matches) closeMobileNav(false);
        });
    });
    desktopBreakpoint.addEventListener('change', (event) => {
        if (event.matches) closeMobileNav(false);
    });

    collapseToggle?.addEventListener('click', () => {
        const isCollapsed = shell.classList.toggle('sidebar-collapsed');
        localStorage.setItem(storageKey, String(isCollapsed));
        collapseToggle.setAttribute('aria-pressed', String(isCollapsed));
        collapseToggle.setAttribute('aria-label', isCollapsed ? 'Perluas sidebar' : 'Ciutkan sidebar');
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && shell.classList.contains('mobile-nav-open')) closeMobileNav();
        if (event.key === 'Tab' && shell.classList.contains('mobile-nav-open')) {
            const focusable = [...sidebar.querySelectorAll('a[href], button:not([disabled])')];
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        }
    });
}

document.addEventListener('click', (event) => {
    const openButton = event.target.closest('[data-confirm-open]');
    if (openButton) {
        document.getElementById(openButton.dataset.confirmOpen)?.showModal();
        return;
    }

    const closeButton = event.target.closest('[data-confirm-close]');
    if (closeButton) closeButton.closest('dialog')?.close();
});
