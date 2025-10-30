import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import App from './App';
import ConsoleApp from './console';
import activeMenuLink from './utils/menu';

domReady(() => {
    const container = document.getElementById('debug-suite-root-app');
    if (container) {
        const root = createRoot(container);
        root.render(<App />);
    }

    // load the standalone app

    const consoleContainer = document.getElementById('wp-admin-bar-debug-suite');
    if (consoleContainer) {
        // mount the console app
        const root = createRoot(consoleContainer);
        root.render(<ConsoleApp />);
    }
});

activeMenuLink('debug-suite');
