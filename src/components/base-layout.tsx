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
    title?: string | JSX.Element;
    children: ReactNode;
    className?: string;
    route?: DebugSuiteRoute;
}

/**
 * Layout component.
 *
 * Provides a consistent page layout with a title and content area.
 *
 * @since 1.0.0
 */
const Layout = ({ title, children, className = '' }: LayoutProps): JSX.Element => {
    const LayoutTitle = () => {
        if (!title) {
            return null;
        }
        return (
            <div className="mb-4">
                {typeof title === 'string' ? (
                    <div className="text-2xl font-semibold text-gray-900 dark:text-white">{title}</div>
                ) : (
                    title
                )}
            </div>
        );
    };
    return (
        <div className={classNames(className, 'mt-5 min-h-screen rounded-lg bg-white p-6')}>
            <LayoutTitle />
            {/* Main content area */}
            <div>{children}</div>
        </div>
    );
};

export default Layout;
