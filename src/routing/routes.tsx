import DebugLog from '@/pages/debug-log';
import ManageLogs from '@/pages/manage-logs';
import Feature from '@/pages/feature';
import NotFound from '@/pages/not-found';
import Overview from '@/pages/overview';
import SetupGuide from '@/pages/settings';
import FileManager from '@/pages/file-manager';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import type { ReactElement, ReactNode } from 'react';

export interface DebugSuiteRoute {
    id: string;
    title?: string | ReactNode;
    description?: string | ReactNode;
    path: string;
    element: ReactElement;
    className?: string;
    icon?: string;
}
const routes: DebugSuiteRoute[] = [
    {
        id: 'setup-guide',
        path: '/settings',
        element: <SetupGuide />
    },
    {
        id: 'overview',
        title: __('Overview', 'debug-suite'),
        description: __('Monitor your WordPress debug activity and system performance.', 'debug-suite'),
        path: '/',
        element: <Overview />
    },
    {
        id: 'file-manager',
        title: __('File Manager', 'debug-suite'),
        description: __('Manage files and directories on your server.', 'debug-suite'),
        path: '/file-manager',
        element: <FileManager />
    },
    {
        id: 'debug-log',
        title: __('Debug Log', 'debug-suite'),
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
        id: 'feature',
        title: __('Features', 'debug-suite'),
        description: __('Manage your application features.', 'debug-suite'),
        path: '/feature',
        element: <Feature />
    }
];

const notFoundRoute: DebugSuiteRoute = {
    id: 'not-found',
    path: '*',
    element: <NotFound />,
    className: 'hidden'
};

export const getRoutes = (): DebugSuiteRoute[] => {
    const filtered = applyFilters('debugSuite.routes', [...routes]) as DebugSuiteRoute[] | undefined;
    const resolved = Array.isArray(filtered) ? filtered : [...routes];
    return [...resolved, notFoundRoute];
};
