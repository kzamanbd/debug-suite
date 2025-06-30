import type { DebugSuiteRoute } from '@/routing/routes';
import { classNames } from '@/utils';
import type { ReactNode } from 'react';
import { useEffect } from 'react';

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
        <div className="mb-4 flex items-center justify-between">
            <div>
                {typeof route.title === 'string' ? (
                    <div className="text-2xl font-semibold text-gray-900 dark:text-white">{route.title}</div>
                ) : (
                    route.title
                )}
                <div className="mt-2">
                    {typeof route.description === 'string' ? (
                        <div className="text-sm text-gray-600 dark:text-gray-400">{route.description}</div>
                    ) : (
                        route.description
                    )}
                </div>
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
        <div className={classNames(className, 'mt-5 min-h-screen rounded-lg bg-white p-5')}>
            <LayoutHeader route={route} />
            {/* Main content area */}
            <div>{children}</div>
        </div>
    );
};

export default Layout;
