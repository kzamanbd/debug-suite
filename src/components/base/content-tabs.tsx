import { classNames } from '@/utils';
import { Tabs } from '@base-ui/react/tabs';
import type { ReactNode } from 'react';

interface ContentTabsProps {
    tabs: Array<{ key: string; label: string; content: ReactNode }>;
    className?: string;
}

/**
 * ContentTabs component using Base UI Tabs (the upstream toolkit behind the
 * shadcn `base-luma` style) for accessible tab navigation.
 *
 * @since 1.0.0
 */
const ContentTabs = ({ tabs, className = '' }: ContentTabsProps): JSX.Element => {
    const defaultValue = tabs[0]?.key ?? 0;
    return (
        <Tabs.Root defaultValue={defaultValue}>
            <div className={classNames('mb-6', className)}>
                <Tabs.List className="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                    {tabs.map((tab) => (
                        <Tabs.Tab
                            key={tab.key}
                            value={tab.key}
                            className={classNames(
                                'px-4 py-2 text-sm font-medium transition-colors focus:outline-none',
                                'text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-300',
                                'data-[selected]:border-b-2 data-[selected]:border-primary-600 data-[selected]:text-primary-700 dark:data-[selected]:text-primary-300'
                            )}>
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
