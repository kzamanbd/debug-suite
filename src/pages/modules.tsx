import Card from '@/components/base/card';
import CustomSwitch from '@/components/base/switch';
import { __ } from '@wordpress/i18n';
import {
    Activity,
    Bug,
    Database,
    Globe,
    HardDrive,
    LayoutDashboard,
    Mail,
    Search,
    Server,
    Settings,
    Shield,
    Terminal,
    Zap
} from 'lucide-react';
import { useState } from 'react';

interface ModuleItem {
    id: string;
    title: string;
    description: string;
    icon: React.ElementType;
    enabled: boolean;
    category: 'core' | 'logging' | 'performance' | 'tools';
}

const initialModules: ModuleItem[] = [
    {
        id: 'debug-log',
        title: __('Debug Log', 'debug-suite'),
        description: __('Capture and view WordPress debug.log entries in real-time.', 'debug-suite'),
        icon: Bug,
        enabled: true,
        category: 'logging'
    },
    {
        id: 'email-log',
        title: __('Email Log', 'debug-suite'),
        description: __('Log all outgoing emails sent by WordPress for debugging.', 'debug-suite'),
        icon: Mail,
        enabled: true,
        category: 'logging'
    },
    {
        id: 'system-info',
        title: __('System Info', 'debug-suite'),
        description: __('View detailed server, PHP, and WordPress environment information.', 'debug-suite'),
        icon: Server,
        enabled: true,
        category: 'core'
    },
    {
        id: 'file-manager',
        title: __('File Manager', 'debug-suite'),
        description: __('Browse and manage your WordPress file system directly from the dashboard.', 'debug-suite'),
        icon: HardDrive,
        enabled: false,
        category: 'tools'
    },
    {
        id: 'query-monitor',
        title: __('Query Monitor', 'debug-suite'),
        description: __('Analyze database queries and identify performance bottlenecks.', 'debug-suite'),
        icon: Database,
        enabled: false,
        category: 'performance'
    },
    {
        id: 'cron-manager',
        title: __('Cron Manager', 'debug-suite'),
        description: __('View and control WordPress cron jobs and scheduled events.', 'debug-suite'),
        icon: Activity,
        enabled: true,
        category: 'tools'
    },
    {
        id: 'api-logger',
        title: __('API Logger', 'debug-suite'),
        description: __('Log and inspect REST API requests and responses.', 'debug-suite'),
        icon: Globe,
        enabled: false,
        category: 'logging'
    },
    {
        id: 'options-viewer',
        title: __('Options Viewer', 'debug-suite'),
        description: __('Inspect and manage WordPress options in the database.', 'debug-suite'),
        icon: Settings,
        enabled: true,
        category: 'tools'
    },
    {
        id: 'terminal',
        title: __('Terminal', 'debug-suite'),
        description: __('Execute WP-CLI commands directly from your browser.', 'debug-suite'),
        icon: Terminal,
        enabled: false,
        category: 'tools'
    },
    {
        id: 'security-check',
        title: __('Security Check', 'debug-suite'),
        description: __('Scan for common security vulnerabilities and misconfigurations.', 'debug-suite'),
        icon: Shield,
        enabled: true,
        category: 'core'
    },
    {
        id: 'asset-manager',
        title: __('Asset Manager', 'debug-suite'),
        description: __('Manage enqueued scripts and styles to optimize performance.', 'debug-suite'),
        icon: Zap,
        enabled: false,
        category: 'performance'
    },
    {
        id: 'dashboard-widgets',
        title: __('Dashboard Widgets', 'debug-suite'),
        description: __('Enable or disable custom dashboard widgets for quick insights.', 'debug-suite'),
        icon: LayoutDashboard,
        enabled: true,
        category: 'core'
    }
];

const Modules = () => {
    const [modules, setModules] = useState<ModuleItem[]>(initialModules);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedCategory, setSelectedCategory] = useState<string>('all');

    const toggleModule = (id: string) => {
        setModules((prev) =>
            prev.map((module) => (module.id === id ? { ...module, enabled: !module.enabled } : module))
        );
    };

    const filteredModules = modules.filter((module) => {
        const matchesSearch =
            module.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
            module.description.toLowerCase().includes(searchQuery.toLowerCase());
        const matchesCategory = selectedCategory === 'all' || module.category === selectedCategory;
        return matchesSearch && matchesCategory;
    });

    const categories = [
        { id: 'all', label: __('All', 'debug-suite') },
        { id: 'core', label: __('Core', 'debug-suite') },
        { id: 'logging', label: __('Logging', 'debug-suite') },
        { id: 'performance', label: __('Performance', 'debug-suite') },
        { id: 'tools', label: __('Tools', 'debug-suite') }
    ];

    return (
        <div className="space-y-6">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-end">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div className="relative">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input
                            type="text"
                            placeholder={__('Search modules...', 'debug-suite')}
                            className="focus:border-primary-500 focus:ring-primary-500 h-10 w-full rounded-md border border-gray-300 bg-white pr-4 pl-10 text-sm focus:ring-1 focus:outline-none sm:w-64 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                        />
                    </div>
                </div>
            </div>

            <div className="flex flex-wrap gap-2 border-b border-gray-200 pb-4 dark:border-gray-700">
                {categories.map((category) => (
                    <button
                        key={category.id}
                        onClick={() => setSelectedCategory(category.id)}
                        className={`rounded-full px-4 py-1.5 text-sm font-medium transition-colors ${
                            selectedCategory === category.id
                                ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'
                        }`}>
                        {category.label}
                    </button>
                ))}
            </div>

            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {filteredModules.map((module) => (
                    <Card
                        key={module.id}
                        className={`group relative overflow-hidden border transition-all duration-300 hover:shadow-md ${
                            module.enabled
                                ? 'border-gray-200 dark:border-gray-700'
                                : 'border-gray-200 bg-gray-50 opacity-75 dark:border-gray-700 dark:bg-gray-800/50'
                        }`}>
                        <Card.Body className="p-5">
                            <div className="flex items-start justify-between gap-4">
                                <div
                                    className={`rounded-lg p-2.5 ${
                                        module.enabled
                                            ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400'
                                            : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                                    }`}>
                                    <module.icon className="h-6 w-6" />
                                </div>
                                <CustomSwitch
                                    checked={module.enabled}
                                    onChange={() => toggleModule(module.id)}
                                    className="shrink-0"
                                />
                            </div>
                            <div className="mt-4">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">{module.title}</h3>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{module.description}</p>
                            </div>
                            <div className="mt-4 flex items-center gap-2">
                                <span
                                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                                        module.enabled
                                            ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                                    }`}>
                                    {module.enabled ? __('Active', 'debug-suite') : __('Inactive', 'debug-suite')}
                                </span>
                                <span className="text-xs text-gray-400 capitalize">{module.category}</span>
                            </div>
                        </Card.Body>
                    </Card>
                ))}
            </div>

            {filteredModules.length === 0 && (
                <div className="flex flex-col items-center justify-center py-12 text-center">
                    <div className="rounded-full bg-gray-100 p-3 dark:bg-gray-800">
                        <Search className="h-6 w-6 text-gray-400" />
                    </div>
                    <h3 className="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                        {__('No modules found', 'debug-suite')}
                    </h3>
                    <p className="mt-1 text-gray-500 dark:text-gray-400">
                        {__('Try adjusting your search or category filter.', 'debug-suite')}
                    </p>
                    <button
                        onClick={() => {
                            setSearchQuery('');
                            setSelectedCategory('all');
                        }}
                        className="text-primary-600 hover:text-primary-500 dark:text-primary-400 mt-4 text-sm font-medium">
                        {__('Clear filters', 'debug-suite')}
                    </button>
                </div>
            )}
        </div>
    );
};

export default Modules;
