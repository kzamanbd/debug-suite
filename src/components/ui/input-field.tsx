/**
 * InputField component for Debug Suite UI.
 *
 * Standard text input with label and error support.
 *
 * @since 1.0.0
 */
import { classNames } from '@/utils';
import type { InputHTMLAttributes } from 'react';

interface InputFieldProps extends InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    error?: string;
    className?: string;
}

const randomId = () => `input-${Math.random().toString(36).slice(2, 9)}`;

const InputField = ({ label, error, className = '', ...props }: InputFieldProps) => {
    const id = props.id || randomId();
    return (
        <>
            {label && (
                <label htmlFor={id} className="mb-1 block text-sm font-medium text-gray-700">
                    {label}
                </label>
            )}
            <input
                id={id}
                className={classNames(
                    'focus:ring-primary-500 block w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:outline-none',
                    error ? 'border-red-500' : 'border-gray-300',
                    className
                )}
                {...props}
            />
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </>
    );
};

export default InputField;
