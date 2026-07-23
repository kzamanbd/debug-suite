/**
 * Badge component for Debug Suite UI.
 *
 * Supports variants: primary, success, danger, warning, default, info.
 *
 * @since 1.0.0
 */
import { classNames } from '@/utils';
import type { ReactNode } from 'react';

export type BadgeVariant = 'primary' | 'success' | 'danger' | 'warning' | 'default' | 'info';

interface BadgeProps {
    children: ReactNode;
    variant?: BadgeVariant;
    className?: string;
}

const variantClasses: Record<BadgeVariant, string> = {
    primary: 'bg-primary-100 text-primary-800',
    success: 'bg-green-100 text-green-800',
    danger: 'bg-red-100 text-red-800',
    warning: 'bg-yellow-100 text-yellow-800',
    default: 'bg-gray-100 text-gray-800',
    info: 'bg-blue-100 text-blue-800'
};

const Badge = ({ children, variant = 'default', className = '' }: BadgeProps) => (
    <span
        className={classNames(
            'inline-block rounded px-2 py-0.5 text-xs font-semibold',
            variantClasses[variant],
            className
        )}>
        {children}
    </span>
);

export default Badge;
