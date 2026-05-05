/**
 * Select component for Debug Suite UI.
 *
 * Built on Base UI primitives (the upstream Radix-style toolkit used by the
 * shadcn `base-luma` style). Provides a unified API with optional searchable
 * (combobox) variant. Replaces the previous react-select implementation.
 *
 * @since 1.0.0
 */
import { classNames } from '@/utils';
import { Combobox } from '@base-ui/react/combobox';
import { Select } from '@base-ui/react/select';
import { __ } from '@wordpress/i18n';
import { CheckIcon, ChevronDownIcon, SearchIcon } from 'lucide-react';
import type { ReactNode } from 'react';

export interface Option {
    value: string;
    label: string;
}

export interface SelectProps {
    options: Option[];
    value?: Option | null;
    onChange?: (option: Option | null) => void;
    placeholder?: string;
    label?: string;
    error?: string;
    className?: string;
    isDisabled?: boolean;
    formatOptionLabel?: (option: Option) => ReactNode;
    searchable?: boolean;
}

const triggerBase =
    'inline-flex h-10 w-full items-center justify-between gap-2 rounded-lg border bg-white px-3 py-2 text-sm transition-colors focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-800 dark:text-gray-100';

const positionerBase = 'z-[9999] debug-suite-root-app';

const popupBase =
    'max-h-60 w-[var(--anchor-width)] min-w-[10rem] overflow-y-auto overflow-x-hidden rounded-lg border border-gray-200 bg-white p-1 text-sm shadow-2xl outline-none ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-900 dark:ring-white/10';

const itemBase =
    'flex cursor-pointer items-center justify-between gap-2 rounded px-2 py-1.5 text-sm select-none data-highlighted:bg-gray-100 data-disabled:cursor-not-allowed data-disabled:opacity-50 dark:data-highlighted:bg-gray-800';

const renderLabel = (option: Option, formatOptionLabel?: (option: Option) => ReactNode): ReactNode => {
    if (formatOptionLabel) {
        return formatOptionLabel(option);
    }
    return (
        <span title={option.label} className="truncate font-medium">
            {option.label}
        </span>
    );
};

const PlainSelect = ({
    options,
    value,
    onChange,
    placeholder,
    error,
    className,
    isDisabled,
    formatOptionLabel
}: SelectProps) => {
    return (
        <Select.Root
            value={value?.value ?? ''}
            onValueChange={(next) => {
                if (!onChange) return;
                if (!next) {
                    onChange(null);
                    return;
                }
                onChange(options.find((opt) => opt.value === next) ?? null);
            }}
            disabled={isDisabled}>
            <Select.Trigger
                className={classNames(
                    triggerBase,
                    error
                        ? 'border-red-300 hover:border-red-300 focus:ring-red-500'
                        : 'border-gray-200 hover:border-gray-300 dark:border-gray-700',
                    className
                )}>
                <Select.Value>
                    {(val: string) => {
                        const opt = options.find((o) => o.value === val);
                        return opt ? (
                            renderLabel(opt, formatOptionLabel)
                        ) : (
                            <span className="text-gray-400">
                                {placeholder ?? __('Select an option…', 'debug-suite')}
                            </span>
                        );
                    }}
                </Select.Value>
                <Select.Icon>
                    <ChevronDownIcon className="h-4 w-4 text-gray-400" />
                </Select.Icon>
            </Select.Trigger>
            <Select.Portal>
                <Select.Positioner sideOffset={4} className={positionerBase}>
                    <Select.Popup className={popupBase}>
                        <Select.List>
                            {options.map((opt) => (
                                <Select.Item key={opt.value} value={opt.value} className={itemBase}>
                                    <Select.ItemText>{renderLabel(opt, formatOptionLabel)}</Select.ItemText>
                                    <Select.ItemIndicator>
                                        <CheckIcon className="text-primary h-4 w-4" />
                                    </Select.ItemIndicator>
                                </Select.Item>
                            ))}
                        </Select.List>
                    </Select.Popup>
                </Select.Positioner>
            </Select.Portal>
        </Select.Root>
    );
};

const SearchableSelect = ({
    options,
    value,
    onChange,
    placeholder,
    error,
    className,
    isDisabled,
    formatOptionLabel
}: SelectProps) => {
    return (
        <Combobox.Root<Option>
            items={options}
            value={value ?? null}
            onValueChange={(next) => onChange?.(next ?? null)}
            disabled={isDisabled}
            itemToStringLabel={(item) => item.label}
            itemToStringValue={(item) => item.value}
            isItemEqualToValue={(a, b) => a?.value === b?.value}>
            <Combobox.InputGroup
                className={classNames(
                    triggerBase,
                    'cursor-text',
                    error
                        ? 'border-red-300 focus-within:ring-red-500'
                        : 'focus-within:ring-primary border-gray-200 dark:border-gray-700',
                    className
                )}>
                <SearchIcon className="h-4 w-4 flex-shrink-0 text-gray-400" />
                <Combobox.Input
                    placeholder={placeholder ?? __('Search…', 'debug-suite')}
                    className="w-full flex-1 border-0 bg-transparent outline-none placeholder:text-gray-400"
                />
                <Combobox.Trigger className="flex h-5 w-5 items-center justify-center text-gray-400 hover:text-gray-600">
                    <ChevronDownIcon className="h-4 w-4" />
                </Combobox.Trigger>
            </Combobox.InputGroup>
            <Combobox.Portal>
                <Combobox.Positioner sideOffset={4} className={positionerBase}>
                    <Combobox.Popup className={popupBase}>
                        <Combobox.Empty className="px-2 py-1.5 text-sm text-gray-500">
                            {__('No matches', 'debug-suite')}
                        </Combobox.Empty>
                        <Combobox.List>
                            {(item: Option) => (
                                <Combobox.Item key={item.value} value={item} className={itemBase}>
                                    <span className="truncate">{renderLabel(item, formatOptionLabel)}</span>
                                    <Combobox.ItemIndicator>
                                        <CheckIcon className="text-primary h-4 w-4" />
                                    </Combobox.ItemIndicator>
                                </Combobox.Item>
                            )}
                        </Combobox.List>
                    </Combobox.Popup>
                </Combobox.Positioner>
            </Combobox.Portal>
        </Combobox.Root>
    );
};

const SimpleSelect = (props: SelectProps) => {
    const { label, error, searchable } = props;
    return (
        <div className="flex flex-col space-y-1">
            {label && <label className="mb-1 block text-sm font-medium text-gray-700">{label}</label>}
            {searchable ? <SearchableSelect {...props} /> : <PlainSelect {...props} />}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
};

export default SimpleSelect;
