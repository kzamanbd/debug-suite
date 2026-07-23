/**
 * Alert component for Debug Suite UI.
 *
 * Dismissible alert with variant styles.
 *
 * @since 1.0.0
 */
import { classNames } from '@/utils';
import { useState } from '@wordpress/element';
import type { ReactNode } from 'react';

export type AlertVariant = 'primary' | 'success' | 'danger' | 'warning' | 'default' | 'info';

interface AlertProps {
    children: ReactNode;
    variant?: AlertVariant;
    className?: string;
    dismissible?: boolean;
    onClose?: () => void;
}

const variantClasses: Record<AlertVariant, string> = {
    primary: 'bg-primary-50 text-primary-800 border-primary-200',
    success: 'bg-green-50 text-green-800 border-green-200',
    danger: 'bg-red-50 text-red-800 border-red-200',
    warning: 'bg-yellow-50 text-yellow-800 border-yellow-200',
    default: 'bg-gray-50 text-gray-800 border-gray-200',
    info: 'bg-blue-50 text-blue-800 border-blue-200'
};

const Alert = ({ children, variant = 'primary', className = '', dismissible = false, onClose }: AlertProps) => {
    const [visible, setVisible] = useState(true);
    if (!visible) return null;
    return (
        <div
            className={classNames(
                'relative flex items-start gap-2 rounded-lg border px-4 py-3',
                variantClasses[variant],
                className
            )}>
            <div className="flex-1">{children}</div>
            {dismissible && (
                <button
                    type="button"
                    className="ml-2 text-lg font-bold text-gray-400 hover:text-gray-700 focus:outline-none"
                    aria-label="Close"
                    onClick={() => {
                        setVisible(false);
                        onClose?.();
                    }}>
                    &times;
                </button>
            )}
        </div>
    );
};

export default Alert;
