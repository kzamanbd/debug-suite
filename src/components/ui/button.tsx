/**
 * Button component for Debug Suite UI.
 *
 * Supports loading state, spinner, and variants (primary, success, danger, light).
 *
 * @since 1.0.0
 */
import { classNames } from '@/utils';
import { Loader2 } from 'lucide-react';
import type { ButtonHTMLAttributes, ReactNode } from 'react';

export type ButtonVariant = 'primary' | 'success' | 'danger' | 'warning' | 'default' | 'info';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    children: ReactNode;
    variant?: ButtonVariant;
    loading?: boolean;
    spinnerClassName?: string;
    className?: string;
    size?: string;
}

const variantClasses: Record<ButtonVariant, string> = {
    primary: 'bg-primary-600 text-white hover:bg-primary-700',
    success: 'bg-green-600 text-white hover:bg-green-700',
    danger: 'bg-red-600 text-white hover:bg-red-700',
    warning: 'bg-yellow-600 text-white hover:bg-yellow-700',
    default: 'bg-gray-100 text-gray-800 hover:bg-gray-200',
    info: 'bg-blue-600 text-white hover:bg-blue-700'
};

const sizeClasses: Record<string, string> = {
    sm: 'px-2 py-1 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base'
};

const Spinner = ({ className = '' }: { className?: string }) => (
    <Loader2 className={classNames('h-5 w-5 animate-spin text-white', className)} />
);

const Button = ({
    children,
    size = 'md',
    variant = 'default',
    loading = false,
    spinnerClassName = '',
    className = '',
    disabled,
    ...props
}: ButtonProps) => (
    <button
        type="button"
        data-size={size}
        data-variant={variant}
        className={classNames(
            'inline-flex items-center gap-1 rounded-lg font-medium transition-colors focus:ring-2 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50',
            variantClasses[variant],
            sizeClasses[size],
            className
        )}
        disabled={loading || disabled}
        {...props}
    >
        {loading && <Spinner className={spinnerClassName} />}
        {children}
    </button>
);

export default Button;
