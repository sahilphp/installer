/*
 * Page-specific JavaScript for the UI kit examples.
 *
 * Loaded through the page's `scripts` section and `{{ asset('js/ui-kit.js') }}`.
 * It proves that page-level assets survive layout inheritance without any
 * production-URL construction in the template.
 */
document.addEventListener('DOMContentLoaded', function () {
    var target = document.querySelector('[data-ui-kit-js]');

    if (target) {
        target.textContent = 'Page-specific JavaScript loaded';
    }

    document.documentElement.setAttribute('data-ui-kit', 'ready');
});
