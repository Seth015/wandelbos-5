/**
 * Bosarchief Application
 * Main JavaScript for progressive enhancement
 */

(function() {
    'use strict';

    // Mark page as JavaScript-enabled
    document.documentElement.classList.add('js');

    /**
     * Initialize application on DOM ready
     */
    function init() {
        // Add any future interactions here
        console.log('Bosarchief application initialized');
    }

    /**
     * Run init when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

