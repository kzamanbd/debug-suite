import NotFound from '@/pages/not-found';
import Settings from '@/pages/settings';
import { applyFilters } from '@wordpress/hooks';
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
        id: 'home',
        path: '/',
        element: <Settings />
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
