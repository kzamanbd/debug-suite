/**
 * Select component for Debug Suite UI.
 *
 * Uses react-select with full typing and accessibility support.
 * Replaces both Combobox and Listbox components with a unified interface.
 *
 * @since 1.0.0
 */
import { classNames } from '@/utils';
import { ChevronDownIcon } from 'lucide-react';
import type { DropdownIndicatorProps, GroupBase, Props } from 'react-select';
import Select, { components } from 'react-select';

export interface Option {
    value: string;
    label: string;
}

export interface SelectProps
    extends Omit<Props<Option, false, GroupBase<Option>>, 'classNames' | 'value' | 'onChange'> {
    options: Option[];
    value?: Option | null;
    onChange: (option: Option | null) => void;
    placeholder?: string;
    label?: string;
    error?: string;
    className?: string;
    isDisabled?: boolean;
    isSearchable?: boolean;
    menuPlacement?: 'auto' | 'bottom' | 'top';
    menuPortalTarget?: HTMLElement;
    formatOptionLabel?: (option: Option) => React.ReactNode;
}

// Custom dropdown indicator with Lucide icon
const DropdownIndicator = (props: DropdownIndicatorProps<Option, false, GroupBase<Option>>) => {
    return (
        <components.DropdownIndicator {...props}>
            <ChevronDownIcon className="size-4 text-gray-400 group-hover:text-gray-600" />
        </components.DropdownIndicator>
    );
};

const SearchableSelect = ({
    value,
    label,
    error,
    onChange,
    options,
    className = '',
    placeholder = 'Select an option...',
    formatOptionLabel,
    ...props
}: SelectProps) => {
    const renderOptionLabel = (option: Option) => {
        if (formatOptionLabel) {
            return formatOptionLabel(option);
        }

        return (
            <div title={option.label} className="w-[var(--input-width)] truncate text-sm font-medium">
                {option.label}
            </div>
        );
    };

    return (
        <div className={classNames('flex flex-col space-y-1', className)}>
            {label && <label className="mb-1 block text-sm font-medium text-gray-700">{label}</label>}
            <Select<Option, false, GroupBase<Option>>
                components={{
                    DropdownIndicator
                }}
                options={options}
                value={value}
                className="w-full"
                onChange={(newValue) => onChange(newValue)}
                placeholder={placeholder}
                formatOptionLabel={renderOptionLabel}
                classNames={{
                    control: (state) => {
                        return classNames(
                            'bg-white border rounded-lg shadow-none',
                            error
                                ? 'border-red-300 hover:border-red-300'
                                : state.isFocused
                                  ? 'border-primary-500 hover:border-primary-500'
                                  : 'border-gray-300 hover:border-gray-400',
                            props.isDisabled && 'bg-gray-50 border-gray-200'
                        );
                    },
                    placeholder: () => 'text-gray-500',
                    input: () => 'm-0 [&>input]:focus:shadow-none',
                    valueContainer: () => 'py-0.5 p-2',
                    option: (state) => {
                        return classNames('cursor-pointer', state.isDisabled && 'cursor-not-allowed opacity-50');
                    },
                    noOptionsMessage: () => 'text-gray-500 p-2',
                    dropdownIndicator: () => 'p-1.5'
                }}
                {...props}
            />
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
};

export default SearchableSelect;
