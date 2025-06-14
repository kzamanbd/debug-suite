import FileLogs from '@/pages/FileLogs';
import ManageLogs from '@/pages/ManageLogs';
import Settings from '@/pages/Settings';
import { __ } from '@wordpress/i18n';

export type DebugSuiteRoute = {
    id: string;
    title: string;
    path: string;
    element: JSX.Element;
};
const routes: DebugSuiteRoute[] = [
    {
        id: 'debug-suite-settings',
        title: __('Settings', 'debug-suite'),
        path: '/',
        element: <Settings />
    },
    {
        id: 'file-logs',
        title: __('File Logs Overview', 'debug-suite'),
        path: '/file-logs',
        element: <FileLogs />
    },
    {
        id: 'file-logs-manage',
        title: __('Manage File Logs', 'debug-suite'),
        path: '/file-logs/manage',
        element: <ManageLogs />
    }
];

export default routes;
