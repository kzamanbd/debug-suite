/**
 * Combobox component for Debug Suite UI.
 *
 * Uses Headless UI Combobox with full typing and accessibility support.
 *
 * @since 1.0.0
 */
import { classNames } from '@/utils';
import {
    ComboboxButton,
    ComboboxInput,
    ComboboxOption,
    ComboboxOptions,
    Combobox as HeadlessCombobox
} from '@headlessui/react';
import { ChevronDownIcon, Square, SquareCheck } from 'lucide-react';
import { useState } from 'react';

interface Option {
    value: string;
    label: string;
    meta?: string;
}

interface ComboboxProps {
    options: Option[];
    value?: Option | null;
    onChange: (option: Option | null) => void;
    placeholder?: string;
    label?: string;
    error?: string;
    className?: string;
    isDisabled?: boolean;
    formatOptionLabel?: (option: Option) => React.ReactNode;
}

const Combobox = ({
    options,
    value,
    onChange,
    placeholder = 'Select an option...',
    label,
    error,
    className = '',
    isDisabled = false,
    formatOptionLabel
}: ComboboxProps) => {
    const [query, setQuery] = useState('');

    const filteredOptions =
        query === ''
            ? options
            : options.filter((option) => {
                  return (
                      option.label.toLowerCase().includes(query.toLowerCase()) ||
                      option.value.toLowerCase().includes(query.toLowerCase()) ||
                      (option.meta && option.meta.toLowerCase().includes(query.toLowerCase()))
                  );
              });

    const displayValue = (option: Option | null) => {
        return option?.label || '';
    };

    const renderOption = (option: Option) => {
        if (formatOptionLabel) {
            return formatOptionLabel(option);
        }
        return (
            <div className="flex min-w-0 flex-col">
                <div className="truncate text-sm font-medium">{option.label}</div>
                {option.meta && <div className="truncate text-xs text-gray-500">{option.meta}</div>}
            </div>
        );
    };

    return (
        <div className={classNames('space-y-1', className)}>
            {label && <label className="mb-1 block text-sm font-medium text-gray-700">{label}</label>}
            <HeadlessCombobox
                value={value}
                onChange={(value) => onChange(value)}
                onClose={() => setQuery('')}
                disabled={isDisabled}
            >
                <div className="relative">
                    <ComboboxInput
                        className={classNames(
                            'w-full rounded-lg border border-gray-300 bg-white py-2 pr-8 pl-3 text-sm',
                            'data-focus:outline-primary-500 focus:not-data-focus:outline-none data-focus:outline-2 data-focus:-outline-offset-2',
                            'focus:border-primary-500 focus:ring-primary-500 focus:ring-1',
                            isDisabled && 'cursor-not-allowed border-gray-200 bg-gray-50 text-gray-500',
                            error && 'border-red-300 focus:border-red-500 focus:ring-red-500 data-focus:outline-red-500'
                        )}
                        displayValue={displayValue}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder={placeholder}
                    />
                    <ComboboxButton className="group absolute inset-y-0 right-0 px-2.5">
                        <ChevronDownIcon
                            className={classNames(
                                'size-4 text-gray-400 group-data-hover:text-gray-600',
                                isDisabled && 'text-gray-300'
                            )}
                        />
                    </ComboboxButton>
                </div>

                <ComboboxOptions
                    anchor="bottom"
                    transition
                    modal={false}
                    className={classNames(
                        'w-fit min-w-[var(--input-width)] rounded-lg border border-gray-200 bg-white p-1 shadow-lg [--anchor-gap:4px] empty:invisible',
                        'transition duration-100 ease-in data-leave:data-closed:scale-95 data-leave:data-closed:opacity-0'
                    )}
                >
                    {filteredOptions.length === 0 ? (
                        <div className="relative cursor-default px-3 py-2 text-sm text-gray-500 select-none">
                            {query ? 'Nothing found.' : 'No options available.'}
                        </div>
                    ) : (
                        filteredOptions.map((option) => (
                            <ComboboxOption
                                key={option.value}
                                value={option}
                                className="group data-focus:bg-primary-50 data-focus:text-primary-900 flex cursor-pointer items-center gap-2 rounded-md px-3 py-2 select-none"
                            >
                                {value?.value === option.value ? (
                                    <SquareCheck className="text-primary-600 size-4 flex-shrink-0" />
                                ) : (
                                    <Square className="text-primary-600 size-4 flex-shrink-0" />
                                )}
                                <div className="min-w-0 flex-1">{renderOption(option)}</div>
                            </ComboboxOption>
                        ))
                    )}
                </ComboboxOptions>
            </HeadlessCombobox>
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
};

export default Combobox;
