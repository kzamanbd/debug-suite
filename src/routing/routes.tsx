import FileLogs from '@/pages/FileLogs';
import FileManager from '@/pages/FileManager';
import ManageLogs from '@/pages/ManageLogs';
import NotFound from '@/pages/NotFound';
import Settings from '@/pages/Settings';
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
