/**
 * Alert component for Debug Suite UI.
 *
 * Dismissible alert with variant styles.
 *
 * @since 1.0.0
 */
import { cn } from '@/utils/cn';
import { useState } from '@wordpress/element';
import { ReactNode } from 'react';

export type AlertVariant = 'primary' | 'success' | 'danger' | 'light';

interface AlertProps {
    children: ReactNode;
    variant?: AlertVariant;
    className?: string;
    dismissible?: boolean;
    onClose?: () => void;
}

const variantClasses: Record<AlertVariant, string> = {
    primary: 'bg-blue-50 text-blue-800 border-blue-200',
    success: 'bg-green-50 text-green-800 border-green-200',
    danger: 'bg-red-50 text-red-800 border-red-200',
    light: 'bg-gray-50 text-gray-800 border-gray-200'
};

const Alert = ({ children, variant = 'primary', className = '', dismissible = false, onClose }: AlertProps) => {
    const [visible, setVisible] = useState(true);
    if (!visible) return null;
    return (
        <div
            className={cn(
                'relative rounded-lg border px-4 py-3 flex items-start gap-2',
                variantClasses[variant],
                className
            )}
        >
            <div className="flex-1">{children}</div>
            {dismissible && (
                <button
                    type="button"
                    className="ml-2 text-lg font-bold text-gray-400 hover:text-gray-700 focus:outline-none"
                    aria-label="Close"
                    onClick={() => {
                        setVisible(false);
                        onClose?.();
                    }}
                >
                    &times;
                </button>
            )}
        </div>
    );
};

export default Alert;
