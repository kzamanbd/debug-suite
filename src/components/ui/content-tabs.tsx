import { classNames } from '@/utils';
import { Tab, TabGroup, TabList, TabPanel, TabPanels } from '@headlessui/react';
import type { ReactNode } from 'react';

interface ContentTabsProps {
    tabs: Array<{ key: string; label: string; content: ReactNode }>;
    className?: string;
}

/**
 * ContentTabs component using Headless UI Tabs for modern, accessible tab navigation.
 *
 * @since 1.0.0
 * @param {ContentTabsProps} props - The props for the component.
 * @return {JSX.Element} The rendered component.
 */
const ContentTabs = ({ tabs, className = '' }: ContentTabsProps): JSX.Element => {
    return (
        <TabGroup>
            <div className={classNames('mb-6', className)}>
                <TabList className="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                    {tabs.map((tab) => (
                        <Tab
                            key={tab.key}
                            className={({ selected }) =>
                                classNames(
                                    'px-4 py-2 text-sm font-medium transition-colors focus:outline-none',
                                    selected
                                        ? 'border-primary-600 text-primary-700 dark:text-primary-300 border-b-2'
                                        : 'hover:text-primary-600 dark:hover:text-primary-300 text-gray-500 dark:text-gray-400'
                                )
                            }
                        >
                            {tab.label}
                        </Tab>
                    ))}
                </TabList>
            </div>
            <TabPanels>
                {tabs.map((tab) => (
                    <TabPanel key={tab.key} className="focus:outline-none">
                        {tab.content}
                    </TabPanel>
                ))}
            </TabPanels>
        </TabGroup>
    );
};

export default ContentTabs;
