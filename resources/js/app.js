import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import NProgress from 'nprogress';
import AOS from 'aos';
import 'nprogress/nprogress.css';
import 'aos/dist/aos.css';

window.Alpine = Alpine;
window.Chart = Chart;
window.NProgress = NProgress;
window.AOS = AOS;

Alpine.start();

const startAos = () => {
    AOS.init({
        once: true,
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startAos);
} else {
    startAos();
}

window.dispatchEvent(new CustomEvent('app-assets-ready'));
