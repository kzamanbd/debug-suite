/**
 * External dependencies
 */
import { DebugSuiteRoute } from '@/routing/routes';
import { cn } from '@/utils/cn';
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
    return (
        <div className={cn(className, 'mt-5 rounded-lg bg-white p-6 shadow-md')}>
            {typeof title === 'string' ? (
                <div className="text-2xl font-semibold text-gray-900 dark:text-white">{title}</div>
            ) : title ? (
                title
            ) : null}
            <div>{children}</div>
        </div>
    );
};

export default Layout;
