import App from './App';
import { createRoot } from '@wordpress/element';
import activeMenu from './utils/menu';
import './main.css';


const container = document.getElementById('debug-suite-admin-app');

if (container) {
    activeMenu('debug-suite');
    const root = createRoot(container);
    root.render(<App />);
}
