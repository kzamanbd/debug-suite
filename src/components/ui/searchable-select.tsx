/**
 * SelectInput component for Debug Suite UI.
 *
 * Uses react-select with full typing and variant support.
 *
 * @since 1.0.0
 */
import { classNames } from '@/utils';
import Select, { GroupBase, Props as SelectProps } from 'react-select';

interface SearchableSelectProps<
    Option,
    IsMulti extends boolean = false,
    Group extends GroupBase<Option> = GroupBase<Option>
> extends SelectProps<Option, IsMulti, Group> {
    label?: string;
    error?: string;
    className?: string;
}

const SearchableSelect = <
    Option,
    IsMulti extends boolean = false,
    Group extends GroupBase<Option> = GroupBase<Option>
>({
    label,
    error,
    className = '',
    ...props
}: SearchableSelectProps<Option, IsMulti, Group>) => {
    return (
        <div className="space-y-1">
            {label && <label className="mb-1 block text-sm font-medium text-gray-700">{label}</label>}
            <Select
                className={classNames('react-select-container', className)}
                classNamePrefix="react-select"
                styles={{
                    option: (provided, state) => ({
                        ...provided,
                        cursor: 'pointer'
                    })
                }}
                {...props}
            />
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
};

export default SearchableSelect;
