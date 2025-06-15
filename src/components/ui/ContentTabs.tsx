import { cn } from '@/utils/cn';
import { Tab, TabGroup, TabList, TabPanel, TabPanels } from '@headlessui/react';
import { ReactNode } from 'react';

interface ContentTabsProps {
    tabs: { key: string; label: string; content: ReactNode }[];
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
            <div className={cn('mb-6', className)}>
                <TabList className="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                    {tabs.map((tab) => (
                        <Tab
                            key={tab.key}
                            className={({ selected }) =>
                                cn(
                                    'px-4 py-2 text-sm font-medium transition-colors focus:outline-none',
                                    selected
                                        ? 'border-b-2 border-blue-600 text-blue-700 dark:text-blue-300'
                                        : 'text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-300'
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
