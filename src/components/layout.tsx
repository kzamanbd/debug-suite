import type { DebugSuiteRoute } from '@/routing/routes';
import { classNames } from '@/utils';
import type { ReactNode } from 'react';
import { useEffect } from 'react';
import Navigation from './navigation';

interface LayoutProps {
    children: ReactNode;
    className?: string;
    route: DebugSuiteRoute;
}

const LayoutHeader = ({ route }: { route: DebugSuiteRoute }) => {
    if (!route.title) {
        return null;
    }
    return (
        <div className="flex items-center justify-between">
            <div className="flex-1">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    {typeof route.title === 'string' ? (
                        <div className="text-xl font-semibold text-gray-900 dark:text-white">{route.title}</div>
                    ) : (
                        route.title
                    )}
                    <div className="flex flex-wrap items-center gap-2"></div>
                </div>
                {route.description && (
                    <div className="mt-2">
                        {typeof route.description === 'string' ? (
                            <div className="text-sm text-gray-600 dark:text-gray-400">{route.description}</div>
                        ) : (
                            route.description
                        )}
                    </div>
                )}
            </div>
        </div>
    );
};

const Layout = ({ route, children, className = '' }: LayoutProps): JSX.Element => {
    useEffect(() => {
        // update the document title based on the route
        if (route.title && typeof route.title === 'string') {
            document.title = `${route.title} - Debug Suite`;
        }
    }, [route.path, route.title]);

    return (
        <>
            <Navigation route={route} />
            <div className={classNames(className, 'debug-suite-layout', route.className)}>
                <LayoutHeader route={route} />
                <main className="mt-6 flex-1">{children}</main>
            </div>
        </>
    );
};

export default Layout;
