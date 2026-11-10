const root = document.getElementById('app');

if (root) {
    root.dataset.sahilReady = 'true';
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.Firefiber) {
        window.Firefiber.boot();
    }
});
