import DebugLog from '@/pages/debug-log';
import FileManager from '@/pages/file-manager';
import ManageLogs from '@/pages/manage-logs';
import NotFound from '@/pages/not-found';
import Overview from '@/pages/overview';
import { __ } from '@wordpress/i18n';
import { ReactNode } from 'react';

export type DebugSuiteRoute = {
    id: string;
    title?: string | ReactNode;
    description?: string | ReactNode;
    path: string;
    element: ReactNode;
    className?: string;
    icon?: string;
};
const routes: DebugSuiteRoute[] = [
    {
        id: 'overview',
        title: __('Overview', 'debug-suite'),
        description: __('Overview of your debug suite settings.', 'debug-suite'),
        path: '/',
        element: <Overview />
    },
    {
        id: 'debug-log',
        title: __('Debug Log', 'debug-suite'),
        description: __('View and manage your debug logs.', 'debug-suite'),
        path: '/debug-log',
        element: <DebugLog />
    },
    {
        id: 'file-logs-manage',
        title: __('Manage File Logs', 'debug-suite'),
        description: __("Manage your application's log files - clear, download, or archive them.", 'debug-suite'),
        path: '/file-logs/manage',
        element: <ManageLogs />
    },
    {
        id: 'file-manager',
        title: __('File Manager', 'debug-suite'),
        description: __('Manage files and directories on your server.', 'debug-suite'),
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
