// JS/theme-switcher.js
document.addEventListener('DOMContentLoaded', () => {
    const themeSwitcher = document.getElementById('theme-switcher');
    if (!themeSwitcher) return;

    const body = document.body;
    const moonIconClass = 'fa-moon';
    const sunIconClass = 'fa-sun';

    const setIcon = (theme) => {
        const icon = themeSwitcher.querySelector('i');
        if (theme === 'dark') {
            icon.classList.remove(moonIconClass);
            icon.classList.add(sunIconClass);
        } else {
            icon.classList.remove(sunIconClass);
            icon.classList.add(moonIconClass);
        }
    };

    const applyTheme = (theme) => {
        if (theme === 'dark') {
            body.classList.add('dark-theme');
        } else {
            body.classList.remove('dark-theme');
        }
        setIcon(theme);
        localStorage.setItem('theme', theme);
    };

    themeSwitcher.addEventListener('click', () => {
        const currentTheme = body.classList.contains('dark-theme') ? 'dark' : 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        applyTheme(newTheme);
    });

    // Apply saved theme on page load
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        applyTheme(savedTheme);
    } else {
        // Default to light theme and set icon
        setIcon('light');
    }
});