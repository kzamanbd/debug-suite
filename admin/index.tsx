import App from './App';
import { createRoot } from '@wordpress/element';
import './main.css';

const container = document.getElementById('debug-suite-admin-app');
console.dir('Hello world from TypeScript!', container);

if (container) {
    const root = createRoot(container);
    root.render(<App />);
}
