/**
 * Badge component for Debug Suite UI.
 *
 * Supports variants: primary, success, danger, light.
 *
 * @since 1.0.0
 */
import { cn } from '@/utils/cn';
import { ReactNode } from 'react';

export type BadgeVariant = 'primary' | 'success' | 'danger' | 'light';

interface BadgeProps {
    children: ReactNode;
    variant?: BadgeVariant;
    className?: string;
}

const variantClasses: Record<BadgeVariant, string> = {
    primary: 'bg-blue-100 text-blue-800',
    success: 'bg-green-100 text-green-800',
    danger: 'bg-red-100 text-red-800',
    light: 'bg-gray-100 text-gray-800'
};

const Badge = ({ children, variant = 'primary', className = '' }: BadgeProps) => (
    <span className={cn('inline-block rounded px-2 py-0.5 text-xs font-semibold', variantClasses[variant], className)}>
        {children}
    </span>
);

export default Badge;
