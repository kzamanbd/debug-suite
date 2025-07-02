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
    primary: 'bg-primary-600 text-white hover:bg-primary-700',
    success: 'bg-green-600 text-white hover:bg-green-700',
    danger: 'bg-red-600 text-white hover:bg-red-700',
    warning: 'bg-yellow-600 text-white hover:bg-yellow-700',
    default: 'bg-white text-gray-800 hover:bg-gray-50 hover:text-gray-900',
    info: 'bg-blue-600 text-white hover:bg-blue-700'
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
