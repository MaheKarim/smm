import './bootstrap';

// Alpine.js
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Initialize theme from localStorage before Alpine loads
if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
}

// Chart.js default configuration
if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = "'DM Sans', sans-serif";
    Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim() || '#64748b';
    Chart.defaults.borderColor = getComputedStyle(document.documentElement).getPropertyValue('--border').trim() || '#e2e8f0';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.padding = 16;
    Chart.defaults.elements.line.tension = 0.4;
    Chart.defaults.elements.point.radius = 4;
    Chart.defaults.elements.point.hoverRadius = 6;
}

// Helper function for formatting numbers
window.formatNumber = function(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
};

// Helper function for platform colors
window.platformColors = {
    facebook: '#1877f2',
    youtube: '#ff0000',
    instagram: '#e4405f',
    google: '#4285f4',
};

// Alpine start
Alpine.start();

// Theme watcher for Chart.js
const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.attributeName === 'class') {
            const isDark = document.documentElement.classList.contains('dark');
            if (typeof Chart !== 'undefined') {
                Chart.defaults.color = isDark ? '#94a3b8' : '#64748b';
                Chart.defaults.borderColor = isDark ? '#2d2640' : '#e2e8f0';
                
                // Update all charts
                Chart.instances.forEach(chart => {
                    chart.update();
                });
            }
        }
    });
});

observer.observe(document.documentElement, { attributes: true });
