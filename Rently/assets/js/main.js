// assets/js/main.js
// Javascript for Theme Toggle, Language Toggle, and Lazy Loading

document.addEventListener('DOMContentLoaded', () => {

    // --- 1. Dark Mode Toggle ---
    const themeToggleBtn = document.getElementById('theme-toggle');
    
    // Check saved theme in localStorage
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        themeToggleBtn.innerText = '☀️';
    }

    // Toggle theme on button click
    themeToggleBtn.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        if (currentTheme === 'dark') {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            themeToggleBtn.innerText = '🌙';
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            themeToggleBtn.innerText = '☀️';
        }
    });


    // --- 2. Simple Image Lazy Loading ---
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        // Only set attributes if it's not already loading lazily
        if (!img.getAttribute('loading')) {
            img.setAttribute('loading', 'lazy');
        }
    });

});
