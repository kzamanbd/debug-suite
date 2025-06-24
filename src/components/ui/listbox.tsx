/**
 * Listbox component for Debug Suite UI.
 *
 * Uses Headless UI Listbox with full typing and accessibility support.
 * Perfect for select-style dropdowns without search functionality.
 *
 * @since 1.0.0
 */
import { classNames } from '@/utils';
import { Listbox as HeadlessListbox, ListboxButton, ListboxOption, ListboxOptions } from '@headlessui/react';
import { ChevronDownIcon } from 'lucide-react';

interface Option {
    value: string;
    label: string;
    meta?: string;
    icon?: string;
}

interface ListboxProps {
    options: Option[];
    value?: Option | null;
    onChange: (option: Option | null) => void;
    placeholder?: string;
    label?: string;
    error?: string;
    className?: string;
    isDisabled?: boolean;
    formatOptionLabel?: (option: Option) => React.ReactNode;
    formatButtonLabel?: (option: Option | null) => React.ReactNode;
}

const Listbox = ({
    options,
    value,
    onChange,
    placeholder = 'Select an option...',
    label,
    error,
    className = '',
    isDisabled = false,
    formatOptionLabel,
    formatButtonLabel
}: ListboxProps) => {
    const renderButtonContent = () => {
        if (formatButtonLabel && value) {
            return formatButtonLabel(value);
        }
        if (value) {
            return (
                <div className="flex items-center gap-2">
                    {value.icon && <span className="text-sm">{value.icon}</span>}
                    <span>{value.label}</span>
                </div>
            );
        }
        return <span className="text-gray-500">{placeholder}</span>;
    };

    const renderOption = (option: Option) => {
        if (formatOptionLabel) {
            return formatOptionLabel(option);
        }
        return (
            <div className="flex min-w-0 items-center gap-2">
                {option.icon && <span className="flex-shrink-0 text-sm">{option.icon}</span>}
                <div className="flex min-w-0 flex-1 flex-col">
                    <div className="truncate text-sm font-medium">{option.label}</div>
                    {option.meta && <div className="truncate text-xs text-gray-500">{option.meta}</div>}
                </div>
            </div>
        );
    };

    return (
        <div className={classNames('space-y-1', className)}>
            {label && <label className="mb-1 block text-sm font-medium text-gray-700">{label}</label>}
            <HeadlessListbox value={value} onChange={(value) => onChange(value)} disabled={isDisabled}>
                <div className="relative">
                    <ListboxButton
                        className={classNames(
                            'w-full rounded-lg border border-gray-300 bg-white py-2 pr-8 pl-3 text-left text-sm',
                            'data-focus:outline-primary-500 focus:not-data-focus:outline-none data-focus:outline-2 data-focus:-outline-offset-2',
                            'focus:border-primary-500 focus:ring-primary-500 focus:ring-1',
                            'data-hover:border-gray-400',
                            isDisabled && 'cursor-not-allowed border-gray-200 bg-gray-50 text-gray-500',
                            error && 'border-red-300 focus:border-red-500 focus:ring-red-500 data-focus:outline-red-500'
                        )}
                    >
                        {renderButtonContent()}
                        <span className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                            <ChevronDownIcon
                                className={classNames('h-4 w-4 text-gray-400', isDisabled && 'text-gray-300')}
                            />
                        </span>
                    </ListboxButton>

                    <ListboxOptions
                        anchor="bottom"
                        transition
                        modal={false}
                        className={classNames(
                            'w-fit min-w-[var(--button-width)] rounded-lg border border-gray-200 bg-white p-1 shadow-lg [--anchor-gap:4px] empty:invisible',
                            'transition duration-100 ease-in data-leave:data-closed:scale-95 data-leave:data-closed:opacity-0',
                            'z-50'
                        )}
                    >
                        {options.length === 0 ? (
                            <div className="relative cursor-default px-3 py-2 text-sm text-gray-500 select-none">
                                No options available.
                            </div>
                        ) : (
                            options.map((option) => (
                                <ListboxOption
                                    key={option.value}
                                    value={option}
                                    className="group data-focus:bg-primary-50 data-focus:text-primary-900 data-selected:bg-primary-100 data-selected:text-primary-900 flex cursor-pointer items-center gap-2 rounded-md px-3 py-2 select-none"
                                >
                                    <div className="flex-1">{renderOption(option)}</div>
                                </ListboxOption>
                            ))
                        )}
                    </ListboxOptions>
                </div>
            </HeadlessListbox>
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
};

export default Listbox;
