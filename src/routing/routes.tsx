import DebugLog from '@/pages/debug-log';
import EmailLog from '@/pages/email-log';
import ManageLogs from '@/pages/manage-logs';
import NotFound from '@/pages/not-found';
import SetupGuide from '@/pages/setup-guide';
import Overview from '@/pages/overview';
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
        path: '/setup',
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
        id: 'debug-log',
        title: __('Debug Log', 'debug-suite'),
        path: '/debug-log',
        element: <DebugLog />
    },
    {
        id: 'email-log',
        title: __('Email Log', 'debug-suite'),
        description: __('View and manage your email logs.', 'debug-suite'),
        path: '/email-log',
        element: <EmailLog />
    },
    {
        id: 'file-logs-manage',
        title: __('Manage File Logs', 'debug-suite'),
        description: __("Manage your application's log files - clear, download, or archive them.", 'debug-suite'),
        path: '/file-logs/manage',
        element: <ManageLogs />
    },
    {
        id: 'not-found',
        path: '*',
        element: <NotFound />,
        className: 'hidden'
    }
];
export default routes;
