import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import App from './App';
import activeMenu from './utils/menu';

domReady(() => {
    const container = document.getElementById('debug-suite-admin-app');
    if (container) {
        const root = createRoot(container);
        root.render(<App />);
    }
});

activeMenu('debug-suite');
