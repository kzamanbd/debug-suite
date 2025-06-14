/**
 * InputField component for Debug Suite UI.
 *
 * Standard text input with label and error support.
 *
 * @since 1.0.0
 */
import { cn } from '@/utils/cn';
import { InputHTMLAttributes } from 'react';

interface InputFieldProps extends InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    error?: string;
    className?: string;
}

const InputField = ({ label, error, className = '', id, ...props }: InputFieldProps) => (
    <div className="space-y-1">
        {label && (
            <label htmlFor={id} className="block text-sm font-medium text-gray-700">
                {label}
            </label>
        )}
        <input
            id={id}
            className={cn(
                'block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm',
                error ? 'border-red-500' : 'border-gray-300',
                className
            )}
            {...props}
        />
        {error && <p className="text-xs text-red-600 mt-1">{error}</p>}
    </div>
);

export default InputField;
