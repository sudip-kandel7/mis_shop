

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Theme
    initTheme();

    // Setup Theme Toggle Button
    const themeBtn = document.getElementById('theme-toggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', toggleTheme);
    }

    // Setup Mobile Menu Toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }

    // Setup Toast Auto-Dismiss
    const autoToasts = document.querySelectorAll('.toast-message');
    autoToasts.forEach(toast => {
        setTimeout(() => {
            toast.remove();
        }, 5000);
    });
});

/**
 * Initializes theme based on local storage or user preferences.
 */
function initTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
        updateThemeIcon(true);
    } else {
        document.documentElement.classList.remove('dark');
        updateThemeIcon(false);
    }
}

/**
 * Toggles dark mode.
 */
function toggleTheme() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    updateThemeIcon(isDark);
}

/**
 * Updates the theme toggle button icon.
 */
function updateThemeIcon(isDark) {
    const themeIcon = document.getElementById('theme-toggle-icon');
    if (!themeIcon) return;

    if (isDark) {
        // Sun Icon
        themeIcon.innerHTML = `
            <svg class="w-5 h-5 text-yellow-400 transition-transform duration-300 hover:rotate-45" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 9h-1m14.071-4.071l-.707.707M6.343 17.657l-.707.707m2.828-9.9a5 5 0 117.072 7.072l-.707-.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707"></path>
            </svg>
        `;
    } else {
        // Moon Icon
        themeIcon.innerHTML = `
            <svg class="w-5 h-5 text-slate-700 dark:text-slate-300 transition-transform duration-300 hover:-rotate-12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
        `;
    }
}

/**
 * Dynamically displays a Toast notification on the client.
 */
function showToast(type, message) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast-message px-4 py-3 rounded-lg shadow-xl flex items-center gap-3 text-sm font-medium border transition-all duration-300 ${
        type === 'success' 
        ? 'bg-emerald-50/90 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-200 border-emerald-200 dark:border-emerald-800' 
        : type === 'error'
        ? 'bg-rose-50/90 dark:bg-rose-950/80 text-rose-800 dark:text-rose-200 border-rose-200 dark:border-rose-800'
        : 'bg-indigo-50/90 dark:bg-indigo-950/80 text-indigo-800 dark:text-indigo-200 border-indigo-200 dark:border-indigo-800'
    }`;

    // Add Icon based on type
    let iconSvg = '';
    if (type === 'success') {
        iconSvg = `<svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
    } else if (type === 'error') {
        iconSvg = `<svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
    } else {
        iconSvg = `<svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
    }

    toast.innerHTML = `
        ${iconSvg}
        <span>${message}</span>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 5000);
}
