/**
 * CustomSwitch component.
 *
 * A reusable toggle switch styled to match the requested design, using Tailwind CSS v4 and tailwind-merge.
 *
 * @since 1.0.0
 */

import { classNames } from '@/utils';

type CustomSwitchProps = React.InputHTMLAttributes<HTMLInputElement> & {
    className?: string;
};

const CustomSwitch = ({ className = '', ...props }: CustomSwitchProps): JSX.Element => (
    <div className={classNames('relative h-5 w-10', className)}>
        <input
            type="checkbox"
            value="toggle"
            className="peer absolute z-10 h-full w-full cursor-pointer opacity-0"
            {...props}
        />
        <span
            className={classNames(
                'peer-checked:bg-primary-500 block h-full rounded-full bg-gray-200 before:absolute before:bottom-1 before:left-1 before:h-3 before:w-3 before:rounded-full before:bg-white before:transition-all before:duration-300 peer-checked:before:left-6 dark:bg-gray-700 dark:peer-checked:before:bg-white',
                props.disabled ? 'cursor-not-allowed opacity-50' : ''
            )}
        />
    </div>
);

export default CustomSwitch;
