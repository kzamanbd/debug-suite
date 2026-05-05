import ApiLog from '@/pages/api-log';
import EmailLog from '@/pages/email-log';
import ManageLogs from '@/pages/manage-logs';
import NotFound from '@/pages/not-found';
import Overview from '@/pages/overview';
import SetupGuide from '@/pages/settings';
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
        id: 'file-logs-manage',
        title: __('Manage File Logs', 'debug-suite'),
        description: __("Manage your application's log files - clear, download, or archive them.", 'debug-suite'),
        path: '/file-logs/manage',
        element: <ManageLogs />
    },
    {
        id: 'api-logger',
        title: __('API Logger', 'debug-suite'),
        description: __('Monitor and debug REST API requests and responses.', 'debug-suite'),
        path: '/api-logger',
        element: <ApiLog />
    },
    {
        id: 'email-log',
        title: __('Email Log', 'debug-suite'),
        description: __('View and manage your email logs.', 'debug-suite'),
        path: '/email-log',
        element: <EmailLog />
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
