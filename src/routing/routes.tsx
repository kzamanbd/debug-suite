import DebugConfig from '@/pages/debug-config';
import DebugLog from '@/pages/debug-log';
import EmailLog from '@/pages/email-log';
import ManageLogs from '@/pages/manage-logs';
import NotFound from '@/pages/not-found';
import Onboarding from '@/pages/onboarding';
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
        id: 'onboarding',
        path: '/onboarding',
        element: <Onboarding />
    },
    {
        id: 'config',
        path: '/config',
        title: __('Debug Config', 'debug-suite'),
        description: __(
            "Welcome! Let's set up your WordPress debugging environment in just a few steps.",
            'debug-suite'
        ),
        element: <DebugConfig />
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
