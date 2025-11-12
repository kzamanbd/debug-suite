import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import App from './App';
import activeMenuLink from './utils/menu';

domReady(() => {
    const container = document.getElementById('debug-suite-root-app');
    if (container) {
        const root = createRoot(container);
        root.render(<App />);
    }
});

activeMenuLink('debug-suite');
