/**
 * CustomCheckbox component.
 *
 * A reusable checkbox component styled to match the design system, using Tailwind CSS v4 and tailwind-merge.
 * Based on the CustomSwitch component but designed for checkbox interactions.
 *
 * @since 1.0.0
 */

import { classNames } from '@/utils';
import { Check } from 'lucide-react';

type CustomCheckboxProps = React.InputHTMLAttributes<HTMLInputElement> & {
    className?: string;
    label?: string;
};

const CustomCheckbox = ({ className = '', label, ...props }: CustomCheckboxProps): JSX.Element => {
    return (
        <div className={classNames('relative inline-flex items-center', className)}>
            <label className="relative h-5 w-5" htmlFor={props.id}>
                <input
                    type="checkbox"
                    className="peer absolute z-10 h-full w-full cursor-pointer opacity-0"
                    {...props}
                />
                <span
                    className={classNames(
                        'peer-checked:border-primary peer-checked:bg-primary peer-focus:ring-primary/20 dark:peer-checked:border-primary dark:peer-checked:bg-primary flex h-full w-full items-center justify-center rounded border-2 border-gray-300 bg-white transition-all duration-200 peer-focus:ring-2 dark:border-gray-600 dark:bg-gray-800',
                        props.disabled
                            ? 'cursor-not-allowed opacity-50'
                            : 'hover:border-gray-400 dark:hover:border-gray-500'
                    )}
                >
                    <Check
                        size={12}
                        className="text-white opacity-0 transition-opacity duration-200 peer-checked:opacity-100"
                    />
                </span>
            </label>
            {label && (
                <div
                    className={classNames(
                        'ml-2 text-sm font-medium text-gray-700 select-none dark:text-gray-300',
                        props.disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'
                    )}
                >
                    {label}
                </div>
            )}
        </div>
    );
};

export default CustomCheckbox;
