import Card from '@/components/base/card';
import CustomSwitch from '@/components/base/switch';
import { Fill } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { Activity, Database, Globe, HardDrive, Mail, Search, Server, Settings, Zap } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

interface FeatureItem {
    id: string;
    title: string;
    description: string;
    icon: React.ElementType;
    enabled: boolean;
    category: 'core' | 'logging' | 'performance' | 'tools';
    available: boolean;
}

const initialFeatures: FeatureItem[] = [
    {
        id: 'email-log',
        title: __('Email Log', 'debug-suite'),
        description: __('Log all outgoing emails sent by WordPress for debugging.', 'debug-suite'),
        icon: Mail,
        enabled: true,
        category: 'logging',
        available: true
    },
    {
        id: 'system-info',
        title: __('System Info', 'debug-suite'),
        description: __('View detailed server, PHP, and WordPress environment information.', 'debug-suite'),
        icon: Server,
        enabled: true,
        category: 'core',
        available: false
    },
    {
        id: 'file-manager',
        title: __('File Manager', 'debug-suite'),
        description: __('Browse and manage your WordPress file system directly from the dashboard.', 'debug-suite'),
        icon: HardDrive,
        enabled: false,
        category: 'tools',
        available: false
    },
    {
        id: 'query-monitor',
        title: __('Query Monitor', 'debug-suite'),
        description: __('Analyze database queries and identify performance bottlenecks.', 'debug-suite'),
        icon: Database,
        enabled: false,
        category: 'performance',
        available: false
    },
    {
        id: 'cron-manager',
        title: __('Cron Manager', 'debug-suite'),
        description: __('View and control WordPress cron jobs and scheduled events.', 'debug-suite'),
        icon: Activity,
        enabled: true,
        category: 'tools',
        available: false
    },
    {
        id: 'api-logger',
        title: __('API Logger', 'debug-suite'),
        description: __('Log and inspect REST API requests and responses.', 'debug-suite'),
        icon: Globe,
        enabled: false,
        category: 'logging',
        available: false
    },
    {
        id: 'options-viewer',
        title: __('Options Viewer', 'debug-suite'),
        description: __('Inspect and manage WordPress options in the database.', 'debug-suite'),
        icon: Settings,
        enabled: true,
        category: 'tools',
        available: false
    },
    {
        id: 'asset-manager',
        title: __('Asset Manager', 'debug-suite'),
        description: __('Manage enqueued scripts and styles to optimize performance.', 'debug-suite'),
        icon: Zap,
        enabled: false,
        category: 'performance',
        available: false
    }
];

const API_BASE = '/debug-suite/v1/features';

const Feature = () => {
    const [features, setFeatures] = useState<FeatureItem[]>(initialFeatures);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedCategory, setSelectedCategory] = useState<string>('all');
    const [loading, setLoading] = useState(true);
    const [savingId, setSavingId] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    const fetchFeatures = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await apiFetch<{ features: Record<string, boolean> }>({ path: API_BASE });
            const saved = response?.features ?? {};
            setFeatures((prev) =>
                prev.map((f) => ({
                    ...f,
                    enabled: typeof saved[f.id] === 'boolean' ? saved[f.id] : f.enabled
                }))
            );
        } catch (err) {
            setError(__('Failed to load features.', 'debug-suite'));
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchFeatures();
    }, [fetchFeatures]);

    const toggleFeature = useCallback(
        async (id: string) => {
            const next = !features.find((f) => f.id === id)?.enabled;
            setFeatures((prev) => prev.map((f) => (f.id === id ? { ...f, enabled: next } : f)));
            setSavingId(id);
            setError(null);
            try {
                await apiFetch({
                    path: API_BASE,
                    method: 'POST',
                    data: { feature_id: id, enabled: next }
                });
                window.location.reload();
            } catch (err) {
                setError(__('Failed to save. Please try again.', 'debug-suite'));
                setFeatures((prev) => prev.map((f) => (f.id === id ? { ...f, enabled: !next } : f)));
            } finally {
                setSavingId(null);
            }
        },
        [features]
    );

    const filteredFeatures = features.filter((item) => {
        const matchesSearch =
            item.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
            item.description.toLowerCase().includes(searchQuery.toLowerCase());
        const matchesCategory = selectedCategory === 'all' || item.category === selectedCategory;
        return matchesSearch && matchesCategory && item.available;
    });

    const categories = [
        { id: 'all', label: __('All', 'debug-suite') },
        { id: 'core', label: __('Core', 'debug-suite') },
        { id: 'logging', label: __('Logging', 'debug-suite') },
        { id: 'performance', label: __('Performance', 'debug-suite') },
        { id: 'tools', label: __('Tools', 'debug-suite') }
    ];

    if (loading) {
        return (
            <div className="flex items-center justify-center py-12">
                <div className="text-gray-500 dark:text-gray-400">{__('Loading features…', 'debug-suite')}</div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {error && (
                <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                    {error}
                </div>
            )}
            <Fill name="debug-suite-layout-header-right">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-end">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input
                                type="text"
                                placeholder={__('Search features...', 'debug-suite')}
                                className="focus:border-primary-500 focus:ring-primary-500 h-10 w-full rounded-md border border-gray-300 bg-white pr-4 pl-10 text-sm focus:ring-1 focus:outline-none sm:w-64 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                    </div>
                </div>
            </Fill>

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
                {filteredFeatures.map((item) => (
                    <Card
                        key={item.id}
                        className={`group relative overflow-hidden border transition-all duration-300 hover:shadow-md ${
                            item.enabled
                                ? 'border-gray-200 dark:border-gray-700'
                                : 'border-gray-200 bg-gray-50 opacity-75 dark:border-gray-700 dark:bg-gray-800/50'
                        }`}>
                        <Card.Body className="p-5">
                            <div className="flex items-start justify-between gap-4">
                                <div
                                    className={`rounded-lg p-2.5 ${
                                        item.enabled
                                            ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400'
                                            : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                                    }`}>
                                    <item.icon className="h-6 w-6" />
                                </div>
                                <CustomSwitch
                                    checked={item.enabled}
                                    onChange={() => toggleFeature(item.id)}
                                    disabled={savingId === item.id}
                                    className="shrink-0"
                                />
                            </div>
                            <div className="mt-4">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">{item.title}</h3>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{item.description}</p>
                            </div>
                            <div className="mt-4 flex items-center gap-2">
                                <span
                                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                                        item.enabled
                                            ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                                    }`}>
                                    {item.enabled ? __('Active', 'debug-suite') : __('Inactive', 'debug-suite')}
                                </span>
                                <span className="text-xs text-gray-400 capitalize">{item.category}</span>
                            </div>
                        </Card.Body>
                    </Card>
                ))}
            </div>

            {filteredFeatures.length === 0 && (
                <div className="flex flex-col items-center justify-center py-12 text-center">
                    <div className="rounded-full bg-gray-100 p-3 dark:bg-gray-800">
                        <Search className="h-6 w-6 text-gray-400" />
                    </div>
                    <h3 className="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                        {__('No features found', 'debug-suite')}
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

export default Feature;
