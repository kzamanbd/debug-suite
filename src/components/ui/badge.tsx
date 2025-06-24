/**
 * Badge component for Debug Suite UI.
 *
 * Supports variants: primary, success, danger, light.
 *
 * @since 1.0.0
 */
import { classNames } from '@/utils';
import { ReactNode } from 'react';

interface BadgeProps {
    children: ReactNode;
    variant?: string;
    className?: string;
}

const variantClasses: Record<string, string> = {
    primary: 'bg-primary-100 text-primary-800',
    success: 'bg-green-100 text-green-800',
    danger: 'bg-red-100 text-red-800',
    warning: 'bg-yellow-100 text-yellow-800',
    default: 'bg-gray-100 text-gray-800'
};

const Badge = ({ children, variant = 'default', className = '' }: BadgeProps) => (
    <span
        className={classNames(
            'inline-block rounded px-2 py-0.5 text-xs font-semibold',
            variantClasses[variant],
            className
        )}
    >
        {children}
    </span>
);

export default Badge;
