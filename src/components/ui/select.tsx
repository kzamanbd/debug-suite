/**
 * Select component for Debug Suite UI.
 *
 * Built on Base UI Select (shadcn-style primitives) with full typing and
 * accessibility support. Exposes a simple Option-based `SimpleSelect` wrapper
 * plus the underlying styled primitives for advanced usage.
 *
 * @since 1.0.0
 */
import { cn } from '@/lib/utils';
import { Select as SelectPrimitive } from '@base-ui/react/select';
import { __ } from '@wordpress/i18n';
import { CheckIcon, ChevronDownIcon } from 'lucide-react';

export interface Option {
    value: string;
    label: string;
}

const SelectRoot = SelectPrimitive.Root;
const SelectValue = SelectPrimitive.Value;

function SelectTrigger({ className, children, ...props }: SelectPrimitive.Trigger.Props) {
    return (
        <SelectPrimitive.Trigger
            data-slot="select-trigger"
            className={cn(
                'flex h-9 w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-none transition-colors outline-none select-none hover:border-gray-400 focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary/20 data-[popup-open]:border-primary data-disabled:pointer-events-none data-disabled:opacity-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:border-neutral-600 [&>span]:truncate',
                className
            )}
            {...props}>
            {children}
            <SelectPrimitive.Icon className="shrink-0 text-gray-400">
                <ChevronDownIcon className="size-4" />
            </SelectPrimitive.Icon>
        </SelectPrimitive.Trigger>
    );
}

function SelectContent({ className, children, ...props }: SelectPrimitive.Popup.Props) {
    return (
        <SelectPrimitive.Portal>
            <div className="debug-suite-root-app">
                <SelectPrimitive.Positioner className="z-[99999] outline-none" sideOffset={4} alignItemWithTrigger={false}>
                    <SelectPrimitive.Popup
                        data-slot="select-content"
                        className={cn(
                            'max-h-[min(24rem,var(--available-height))] min-w-[var(--anchor-width)] origin-[var(--transform-origin)] overflow-y-auto overscroll-contain rounded-lg border border-gray-200 bg-white p-1 text-sm text-gray-900 shadow-lg outline-none data-[closed]:animate-out data-[closed]:fade-out-0 data-[open]:animate-in data-[open]:fade-in-0 data-[open]:zoom-in-95 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200',
                            className
                        )}
                        {...props}>
                        {children}
                    </SelectPrimitive.Popup>
                </SelectPrimitive.Positioner>
            </div>
        </SelectPrimitive.Portal>
    );
}

function SelectItem({ className, children, ...props }: SelectPrimitive.Item.Props) {
    return (
        <SelectPrimitive.Item
            data-slot="select-item"
            className={cn(
                'relative flex cursor-pointer items-center gap-2 rounded-md py-1.5 pr-2 pl-8 text-sm outline-none select-none data-disabled:pointer-events-none data-disabled:opacity-50 data-highlighted:bg-gray-100 data-selected:font-medium dark:data-highlighted:bg-neutral-800',
                className
            )}
            {...props}>
            <span className="absolute left-2 flex size-4 items-center justify-center">
                <SelectPrimitive.ItemIndicator>
                    <CheckIcon className="size-4 text-primary" />
                </SelectPrimitive.ItemIndicator>
            </span>
            <SelectPrimitive.ItemText>{children}</SelectPrimitive.ItemText>
        </SelectPrimitive.Item>
    );
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
    /** Kept for API compatibility — Base UI Select has built-in keyboard typeahead. */
    searchable?: boolean;
    formatOptionLabel?: (option: Option) => React.ReactNode;
}

const SimpleSelect = ({
    value,
    label,
    error,
    onChange,
    options,
    className,
    placeholder,
    isDisabled,
    formatOptionLabel
}: SelectProps) => {
    return (
        <div className="flex flex-col space-y-1">
            {label && <label className="mb-1 block text-sm font-medium text-gray-700">{label}</label>}

            <SelectRoot<Option>
                items={options}
                value={value ?? null}
                onValueChange={(option) => onChange?.(option)}
                disabled={isDisabled}
                isItemEqualToValue={(a, b) => a?.value === b?.value}>
                <SelectTrigger className={cn(error && 'border-red-300 focus-visible:border-red-400 focus-visible:ring-red-100', className)}>
                    <SelectValue placeholder={placeholder || __('Select Option', 'debug-suite')} />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option}>
                            {formatOptionLabel ? formatOptionLabel(option) : option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </SelectRoot>

            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
};

export { SelectContent, SelectItem, SelectRoot, SelectTrigger, SelectValue };
export default SimpleSelect;
