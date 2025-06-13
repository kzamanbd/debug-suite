import App from './App';
import { createRoot } from '@wordpress/element';
import activeMenu from './utils/menu';
import './main.css';
import domReady from '@wordpress/dom-ready';

domReady(() => {
    const container = document.getElementById('debug-suite-admin-app');
    if (container) {
        const root = createRoot(container);
        root.render(<App />);
    }
});

activeMenu('debug-suite');
