/**
 * RadioButton component for Debug Suite UI.
 *
 * Custom radio button with label.
 *
 * @since 1.0.0
 */
import { cn } from '@/utils/cn';
import { InputHTMLAttributes } from 'react';

interface RadioButtonProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
    label: string;
    className?: string;
}

const RadioButton = ({ label, className = '', id, ...props }: RadioButtonProps) => (
    <label className={cn('inline-flex items-center gap-2 cursor-pointer', className)} htmlFor={id}>
        <input
            type="radio"
            id={id}
            className="accent-blue-600 w-4 h-4 rounded border-gray-300 focus:ring-2 focus:ring-blue-500"
            {...props}
        />
        <span className="text-sm text-gray-700">{label}</span>
    </label>
);

export default RadioButton;
