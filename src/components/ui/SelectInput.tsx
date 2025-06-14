/**
 * SelectInput component for Debug Suite UI.
 *
 * Uses react-select with full typing and variant support.
 *
 * @since 1.0.0
 */
import { cn } from '@/utils/cn';
import Select, { GroupBase, Props as SelectProps } from 'react-select';

export type SelectInputVariant = 'primary' | 'success' | 'danger' | 'light';

interface CustomSelectInputProps<
    Option,
    IsMulti extends boolean = false,
    Group extends GroupBase<Option> = GroupBase<Option>
> extends SelectProps<Option, IsMulti, Group> {
    label?: string;
    error?: string;
    variant?: SelectInputVariant;
    className?: string;
}

const variantClasses: Record<SelectInputVariant, string> = {
    primary: 'focus:ring-blue-500',
    success: 'focus:ring-green-500',
    danger: 'focus:ring-red-500',
    light: 'focus:ring-gray-400'
};

function SelectInput<Option, IsMulti extends boolean = false, Group extends GroupBase<Option> = GroupBase<Option>>({
    label,
    error,
    variant = 'primary',
    className = '',
    ...props
}: CustomSelectInputProps<Option, IsMulti, Group>) {
    return (
        <div className="space-y-1">
            {label && <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>}
            <Select
                className={cn('react-select-container', variantClasses[variant], className)}
                classNamePrefix="react-select"
                {...props}
            />
            {error && <p className="text-xs text-red-600 mt-1">{error}</p>}
        </div>
    );
}

export default SelectInput;
