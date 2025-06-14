/**
 * Button component for Debug Suite UI.
 *
 * Supports loading state, spinner, and variants (primary, success, danger, light).
 *
 * @since 1.0.0
 */
import { cn } from '@/utils/cn';
import { ButtonHTMLAttributes } from 'react';

export type ButtonVariant = 'primary' | 'success' | 'danger' | 'light';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    children: JSX.Element;
    variant?: ButtonVariant;
    loading?: boolean;
    spinnerClassName?: string;
    className?: string;
}

const variantClasses: Record<ButtonVariant, string> = {
    primary: 'bg-blue-600 text-white hover:bg-blue-700',
    success: 'bg-green-600 text-white hover:bg-green-700',
    danger: 'bg-red-600 text-white hover:bg-red-700',
    light: 'bg-gray-100 text-gray-800 hover:bg-gray-200'
};

const Spinner = ({ className = '' }: { className?: string }) => (
    <svg
        className={cn('animate-spin h-5 w-5 text-white', className)}
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
    >
        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
    </svg>
);

const Button = ({
    children,
    variant = 'primary',
    loading = false,
    spinnerClassName = '',
    className = '',
    disabled,
    ...props
}: ButtonProps) => (
    <button
        type="button"
        className={cn(
            'inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',
            variantClasses[variant],
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
