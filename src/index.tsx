import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import App from './App';
import activeMenuLink from './utils/menu';

domReady(() => {
    const container = document.getElementById('debug-suite-admin-app');
    if (container) {
        const root = createRoot(container);
        root.render(<App />);
        document.title = 'Debug Suite';
        // add the favicon
        const link = document.createElement('link');
        link.rel = 'icon';
        link.href = window.debugSuite.favicon.toString();
        document.head.appendChild(link);
    }
});

activeMenuLink('debug-suite');
