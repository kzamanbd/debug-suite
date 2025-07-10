import { classNames } from '@/utils';
import { CheckCircle, X, XCircle } from 'lucide-react';
import type { ReactNode } from 'react';
import { createContext, useCallback, useContext, useState } from 'react';

interface Toast {
    id: string;
    title: string;
    icon?: ReactNode;
    variant?: 'default' | 'success' | 'error';
}

interface ToastOptions {
    title: string;
    duration?: number;
    icon?: ReactNode;
    variant?: 'default' | 'success' | 'error';
}

interface ToastContextValue {
    toast: (options: ToastOptions) => string;
    dismiss: (toastId: string) => void;
    toasts: Toast[];
    success: (title: string, duration?: number) => string;
    error: (title: string, duration?: number) => string;
}

interface ToastProviderProps {
    children: ReactNode;
}

const ToastContext = createContext<ToastContextValue | undefined>(undefined);

export function useToast(): ToastContextValue {
    const context = useContext(ToastContext);
    if (!context) {
        throw new Error('useToast must be used within a ToastProvider');
    }
    return context;
}

export function ToastProvider({ children }: ToastProviderProps) {
    const [toasts, setToasts] = useState<Toast[]>([]);

    const toast = useCallback(({ title, duration = 2000, icon, variant = 'default' }: ToastOptions): string => {
        const id = Math.random().toString(36).slice(2, 9);
        setToasts((prev) => [...prev, { id, title, icon, variant }]);

        if (duration !== Infinity) {
            setTimeout(() => {
                setToasts((prev) => prev.filter((toast) => toast.id !== id));
            }, duration);
        }

        return id;
    }, []);

    const success = useCallback(
        (title: string, duration = 2000): string => {
            return toast({
                title,
                duration,
                icon: <CheckCircle className="size-4" />,
                variant: 'success'
            });
        },
        [toast]
    );

    const error = useCallback(
        (title: string, duration = 2000): string => {
            return toast({
                title,
                duration,
                icon: <XCircle className="size-4" />,
                variant: 'error'
            });
        },
        [toast]
    );

    const dismiss = useCallback((toastId: string) => {
        setToasts((prev) => prev.filter((toast) => toast.id !== toastId));
    }, []);

    return (
        <ToastContext.Provider value={{ toast, dismiss, toasts, success, error }}>
            {children}
            <div className="fixed right-0 bottom-0 z-50 flex w-full max-w-md flex-col items-end space-y-2 p-4">
                {toasts.map(({ id, title, icon, variant }) => (
                    <div
                        key={id}
                        className={classNames(
                            'bg-background relative flex items-center gap-2 rounded-lg border p-4 pr-8 shadow-lg',
                            'animate-in slide-in-from-bottom-5 fade-in duration-300',
                            'data-[state=closed]:animate-out data-[state=closed]:slide-out-to-right-5 data-[state=closed]:fade-out',
                            variant === 'success' && 'border-green-200 bg-green-50',
                            variant === 'error' && 'border-red-200 bg-red-50'
                        )}
                    >
                        {icon && (
                            <div
                                className={classNames(
                                    variant === 'success' && 'text-green-600',
                                    variant === 'error' && 'text-red-600',
                                    variant === 'default' && 'text-primary'
                                )}
                            >
                                {icon}
                            </div>
                        )}
                        <p
                            className={classNames(
                                'text-sm',
                                variant === 'success' && 'text-green-800',
                                variant === 'error' && 'text-red-800',
                                variant === 'default' && 'text-foreground'
                            )}
                        >
                            {title}
                        </p>
                        <button
                            onClick={() => dismiss(id)}
                            className="absolute top-2 right-2 opacity-70 transition-opacity hover:opacity-100"
                        >
                            <X className="size-4" />
                        </button>
                    </div>
                ))}
            </div>
        </ToastContext.Provider>
    );
}
