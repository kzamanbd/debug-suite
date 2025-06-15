import ErrorLogs from '@/pages/error-logs';
import FileManager from '@/pages/file-manager';
import ManageLogs from '@/pages/manage-logs';
import NotFound from '@/pages/not-found';
import Settings from '@/pages/overview-settings';
import { __ } from '@wordpress/i18n';

export type DebugSuiteRoute = {
    id: string;
    title?: string | JSX.Element;
    path: string;
    element: JSX.Element;
    className?: string;
    icon?: string;
};
const routes: DebugSuiteRoute[] = [
    {
        id: 'debug-suite-settings',
        title: __('Settings', 'debug-suite'),
        path: '/',
        element: <Settings />
    },
    {
        id: 'error-logs',
        title: __('Error Logs Overview', 'debug-suite'),
        path: '/error-logs',
        element: <ErrorLogs />
    },
    {
        id: 'file-logs-manage',
        title: __('Manage File Logs', 'debug-suite'),
        path: '/file-logs/manage',
        element: <ManageLogs />
    },
    {
        id: 'file-manager',
        title: __('File Manager', 'debug-suite'),
        path: '/file-manager',
        element: <FileManager />
    },
    {
        id: 'not-found',
        path: '*',
        element: <NotFound />,
        className: 'hidden'
    }
];
export default routes;
