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
    title: string;
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
        <div className={cn('max-w-5xl mx-auto px-4 py-8', className)}>
            <h1 className="text-3xl font-bold text-gray-900 mb-4">{title}</h1>
            <div>{children}</div>
        </div>
    );
};

export default Layout;
