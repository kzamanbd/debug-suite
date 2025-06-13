import App from './App';
import { createRoot } from '@wordpress/element';
import './main.css';

const container = document.getElementById('debug-suite-admin-app');
console.log('Hello world from TypeScript!', container);

if (container) {
    const root = createRoot(container); // createRoot(container!) if you use TypeScript
    root.render(<App />);
}
