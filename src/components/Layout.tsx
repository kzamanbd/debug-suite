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
        <div className={cn(className, 'mt-5 rounded-lg bg-white p-6 shadow-md')}>
            <h2>{title}</h2>
            <div>{children}</div>
        </div>
    );
};

export default Layout;
