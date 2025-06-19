/**
 * External dependencies
 */
import { DebugSuiteRoute } from '@/routing/routes';
import { classNames } from '@/utils';
import { ReactNode } from 'react';

/**
 * Props for the Layout component.
 *
 * @since 1.0.0
 */
interface LayoutProps {
    children: ReactNode;
    className?: string;
    route: DebugSuiteRoute;
}

/**
 * Layout component.
 *
 * Provides a consistent page layout with a title and content area.
 *
 * @since 1.0.0
 */
const Layout = ({ route, children, className = '' }: LayoutProps): JSX.Element => {
    const LayoutHeader = () => {
        if (!route.title) {
            return null;
        }
        return (
            <div className="mb-4">
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
        );
    };
    return (
        <div className={classNames(className, 'mt-5 min-h-screen rounded-lg bg-white p-6')}>
            <LayoutHeader />
            {/* Main content area */}
            <div>{children}</div>
        </div>
    );
};

export default Layout;
