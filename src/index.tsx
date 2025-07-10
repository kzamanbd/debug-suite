import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import App from './App';
import activeMenuLink from './utils/menu';

domReady(() => {
    const container = document.getElementById('debug-suite-root-app');
    if (container) {
        const root = createRoot(container);
        root.render(<App />);
        // add the favicon
        // Remove any existing favicon links
        const existingIcons = document.querySelectorAll('link[rel="icon"], link[rel="shortcut icon"]');
        existingIcons.forEach((icon) => icon.parentNode?.removeChild(icon));

        const faviconUrl = window.debugSuite.favicon;
        // Create new link
        const link = document.createElement('link');
        link.rel = 'icon';
        link.type = 'image/png';
        link.href = faviconUrl;

        // Append to head
        document.head.appendChild(link);
    }
});

activeMenuLink('debug-suite');
