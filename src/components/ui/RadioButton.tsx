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
    <label className={cn('inline-flex cursor-pointer items-center gap-2', className)} htmlFor={id}>
        <input
            type="radio"
            id={id}
            className="h-4 w-4 rounded border-gray-300 accent-blue-600 focus:ring-2 focus:ring-blue-500"
            {...props}
        />
        <span className="text-sm text-gray-700">{label}</span>
    </label>
);

export default RadioButton;
