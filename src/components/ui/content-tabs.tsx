import { classNames } from '@/utils';
import { Tabs } from '@base-ui/react/tabs';
import type { ReactNode } from 'react';

interface ContentTabsProps {
    tabs: Array<{ key: string; label: string; content: ReactNode }>;
    className?: string;
}

/**
 * ContentTabs component using Base UI Tabs for modern, accessible tab navigation.
 *
 * @since 1.0.0
 * @param {ContentTabsProps} props - The props for the component.
 * @return {JSX.Element} The rendered component.
 */
const ContentTabs = ({ tabs, className = '' }: ContentTabsProps): JSX.Element => {
    return (
        <Tabs.Root defaultValue={tabs[0]?.key}>
            <div className={classNames('mb-6', className)}>
                <Tabs.List className="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                    {tabs.map((tab) => (
                        <Tabs.Tab
                            key={tab.key}
                            value={tab.key}
                            className={({ active }) =>
                                classNames(
                                    'px-4 py-2 text-sm font-medium transition-colors focus:outline-none',
                                    active
                                        ? 'border-primary-600 text-primary-700 dark:text-primary-300 border-b-2'
                                        : 'hover:text-primary-600 dark:hover:text-primary-300 text-gray-500 dark:text-gray-400'
                                )
                            }>
                            {tab.label}
                        </Tabs.Tab>
                    ))}
                </Tabs.List>
            </div>
            {tabs.map((tab) => (
                <Tabs.Panel key={tab.key} value={tab.key} className="focus:outline-none">
                    {tab.content}
                </Tabs.Panel>
            ))}
        </Tabs.Root>
    );
};

export default ContentTabs;
