import { classNames } from '@/utils';
import { Loader2 } from 'lucide-react';
import type { ComponentPropsWithoutRef, ElementType, ReactNode } from 'react';

export type ButtonVariant = 'primary' | 'success' | 'danger' | 'warning' | 'default' | 'info';

interface ButtonOwnProps<T extends ElementType> {
    as?: T;
    children: ReactNode;
    variant?: ButtonVariant;
    loading?: boolean;
    spinnerClassName?: string;
    className?: string;
    size?: keyof typeof sizeClasses;
    disabled?: boolean;
}

const variantClasses: Record<ButtonVariant, string> = {
    primary:
        'bg-primary shadow-primary-100 hover:bg-primary-700 border-primary text-white from-primary-400 to-primary-500 dark:bg-primary-600 dark:hover:bg-primary-700 bg-gradient-to-br hover:from-primary-500 hover:to-primary-600',
    success:
        'bg-success shadow-success-100 border-success text-white bg-gradient-to-br from-green-400 to-green-500 dark:bg-green-600 dark:hover:bg-green-700 hover:from-green-500 hover:to-green-600',
    danger: 'bg-danger shadow-danger-100 border-danger text-white bg-gradient-to-br from-red-400 to-red-500 dark:bg-red-600 dark:hover:bg-red-700 hover:from-red-500 hover:to-red-600',
    warning:
        'bg-warning shadow-warning-100 border-warning text-white bg-gradient-to-br from-yellow-400 to-yellow-500 dark:bg-yellow-600 dark:hover:bg-yellow-700 hover:from-yellow-500 hover:to-yellow-600',
    default:
        'bg-white text-gray-800 hover:bg-gray-50 hover:text-gray-900 border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:hover:text-gray-100',
    info: 'bg-info shadow-info-100 border-info text-white bg-gradient-to-br from-blue-400 to-blue-500 dark:bg-blue-600 dark:hover:bg-blue-700 hover:from-blue-500 hover:to-blue-600'
};

const sizeClasses = {
    sm: 'px-2 py-1 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base'
};

const Spinner = ({ className = '' }: { className?: string }) => (
    <Loader2 className={classNames('h-5 w-5 animate-spin text-white', className)} />
);

type PolymorphicComponentProps<T extends ElementType> = ButtonOwnProps<T> &
    Omit<ComponentPropsWithoutRef<T>, keyof ButtonOwnProps<T>>;

const Button = <T extends ElementType = 'button'>({
    as,
    children,
    size = 'md',
    variant = 'default',
    loading = false,
    spinnerClassName = '',
    className = '',
    disabled,
    ...props
}: PolymorphicComponentProps<T>) => {
    const Component = as || 'button';
    const isButton = Component === 'button';

    return (
        <Component
            data-size={size}
            data-variant={variant}
            className={classNames(
                'flex cursor-pointer items-center justify-center gap-0.5 rounded-md border border-gray-200 px-2.5 py-1.5 text-sm font-medium dark:border-gray-700',
                variantClasses[variant],
                sizeClasses[size],
                className
            )}
            disabled={isButton ? loading || disabled : undefined}
            aria-disabled={loading || disabled}
            {...props}
        >
            {loading && <Spinner className={spinnerClassName} />}
            {children}
        </Component>
    );
};

export default Button;
