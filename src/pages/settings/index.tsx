import { Card } from '@/components/base';
import CustomSwitch from '@/components/base/switch';
import type { DebugState } from '@/types';
import { classNames } from '@/utils';
import apiFetch from '@wordpress/api-fetch';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Activity,
    AlertTriangle,
    Bug,
    CheckCircle2,
    Database,
    EyeOff,
    FileText,
    HardDrive,
    Layers,
    Mail,
    Search,
    Server,
    Settings as SettingsIcon,
    SlidersHorizontal,
    Sparkles,
    Zap
} from 'lucide-react';
import type React from 'react';
import { toast } from 'sonner';

interface SettingsResponse {
    success: boolean;
    settings: DebugState;
    message?: string;
}

interface FeatureItem {
    id: string;
    title: string;
    description: string;
    icon: React.ElementType;
    enabled: boolean;
    category: 'core' | 'logging' | 'performance' | 'tools';
    available: boolean;
}

interface DebugToggle {
    name: keyof DebugState;
    title: string;
    description: string;
    icon: React.ElementType;
    danger?: boolean;
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
        id: 'api-docs',
        title: __('API Docs', 'debug-suite'),
        description: __('View and inspect REST API documentation.', 'debug-suite'),
        icon: FileText,
        enabled: false,
        category: 'tools',
        available: true
    },
    {
        id: 'options-viewer',
        title: __('Options Viewer', 'debug-suite'),
        description: __('Inspect and manage WordPress options in the database.', 'debug-suite'),
        icon: SettingsIcon,
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

const FEATURES_API = '/debug-suite/v1/features';

type TabKey = 'constants' | 'features';

const DebugConfig = () => {
    const [settings, setSettings] = useState<DebugState>({
        wp_debug: window.debugSuite.wp_debug || false,
        wp_debug_log: window.debugSuite.wp_debug_log || false,
        wp_debug_display: window.debugSuite.wp_debug_display || false
    });
    const [saving, setSaving] = useState(false);

    const [features, setFeatures] = useState<FeatureItem[]>(initialFeatures);
    const [featuresLoading, setFeaturesLoading] = useState(true);
    const [savingFeatureId, setSavingFeatureId] = useState<string | null>(null);
    const [featuresError, setFeaturesError] = useState<string | null>(null);
    const [featureSearch, setFeatureSearch] = useState('');
    const [featureCategory, setFeatureCategory] = useState<string>('all');

    const [activeTab, setActiveTab] = useState<TabKey>('constants');

    const updateSetting = async (values: Partial<DebugState> & Record<string, unknown>) => {
        setSaving(true);
        const updatedSettings = { ...settings, ...values };
        try {
            const response = await apiFetch<SettingsResponse>({
                path: '/debug-suite/v1/settings',
                method: 'POST',
                data: updatedSettings
            });

            if (response.success) {
                setSettings(updatedSettings);
            }
        } catch (error) {
            console.error('Error updating setting:', error);
        } finally {
            setSaving(false);
        }
    };

    const changeHandler = (event: React.ChangeEvent<HTMLInputElement>) => {
        const key = event.target.name as keyof DebugState;
        const value = event.target.checked;
        setSettings((prev) => ({
            ...prev,
            [key]: value
        }));
        toast.promise(updateSetting({ [key]: value }), {
            loading: __('Applying setting...', 'debug-suite'),
            success: __('Setting applied successfully!', 'debug-suite'),
            error: __('Failed to apply setting.', 'debug-suite')
        });
    };

    const updateEnv = (mode: boolean) => {
        toast.promise(
            updateSetting({
                ...settings,
                wp_debug: mode,
                wp_debug_log: mode
            }),
            {
                loading: __('Applying configuration...', 'debug-suite'),
                success: __('Configuration applied successfully!', 'debug-suite'),
                error: __('Failed to apply configuration.', 'debug-suite')
            }
        );
    };

    const fetchFeatures = useCallback(async () => {
        setFeaturesLoading(true);
        setFeaturesError(null);
        try {
            const response = await apiFetch<{ features: Record<string, boolean> }>({ path: FEATURES_API });
            const saved = response?.features ?? {};
            setFeatures((prev) =>
                prev.map((f) => ({
                    ...f,
                    enabled: typeof saved[f.id] === 'boolean' ? saved[f.id] : f.enabled
                }))
            );
        } catch (err: unknown) {
            console.error('Error fetching features:', err);
            setFeaturesError(__('Failed to load features. Please try again.', 'debug-suite'));
        } finally {
            setFeaturesLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchFeatures();
    }, [fetchFeatures]);

    const toggleFeature = useCallback(
        async (id: string) => {
            const next = !features.find((f) => f.id === id)?.enabled;
            setFeatures((prev) => prev.map((f) => (f.id === id ? { ...f, enabled: next } : f)));
            setSavingFeatureId(id);
            setFeaturesError(null);
            try {
                await apiFetch({
                    path: FEATURES_API,
                    method: 'POST',
                    data: { feature_id: id, enabled: next }
                });
                window.location.reload();
            } catch (err: unknown) {
                console.error('Error saving feature:', err);
                setFeaturesError(__('Failed to save. Please try again.', 'debug-suite'));
                setFeatures((prev) => prev.map((f) => (f.id === id ? { ...f, enabled: !next } : f)));
            } finally {
                setSavingFeatureId(null);
            }
        },
        [features]
    );

    const debugToggles: DebugToggle[] = [
        {
            name: 'wp_debug',
            title: __('WP_DEBUG', 'debug-suite'),
            description: __(
                'Enable the built-in WordPress debug mode to track PHP errors, notices, and warnings.',
                'debug-suite'
            ),
            icon: Bug
        },
        {
            name: 'wp_debug_log',
            title: __('WP_DEBUG_LOG', 'debug-suite'),
            description: __(
                'Save all errors to a debug.log file for later analysis. Recommended for production sites.',
                'debug-suite'
            ),
            icon: FileText
        }
    ];

    const availableFeatures = useMemo(() => features.filter((f) => f.available), [features]);
    const activeFeatureCount = useMemo(() => availableFeatures.filter((f) => f.enabled).length, [availableFeatures]);
    const activeConstantCount = useMemo(
        () => [settings.wp_debug, settings.wp_debug_log, settings.wp_debug_display].filter(Boolean).length,
        [settings]
    );

    const filteredFeatures = features.filter((item) => {
        const matchesSearch =
            item.title.toLowerCase().includes(featureSearch.toLowerCase()) ||
            item.description.toLowerCase().includes(featureSearch.toLowerCase());
        const matchesCategory = featureCategory === 'all' || item.category === featureCategory;
        return matchesSearch && matchesCategory && item.available;
    });

    const categories = [
        { id: 'all', label: __('All', 'debug-suite'), count: availableFeatures.length },
        {
            id: 'core',
            label: __('Core', 'debug-suite'),
            count: availableFeatures.filter((f) => f.category === 'core').length
        },
        {
            id: 'logging',
            label: __('Logging', 'debug-suite'),
            count: availableFeatures.filter((f) => f.category === 'logging').length
        },
        {
            id: 'performance',
            label: __('Performance', 'debug-suite'),
            count: availableFeatures.filter((f) => f.category === 'performance').length
        },
        {
            id: 'tools',
            label: __('Tools', 'debug-suite'),
            count: availableFeatures.filter((f) => f.category === 'tools').length
        }
    ];

    return (
        <div className="mx-auto w-full max-w-5xl space-y-6">
            {/* Tab nav */}
            <div className="sticky top-2 z-10 flex w-fit gap-1 rounded-xl border border-gray-200 bg-white/80 p-1 shadow-sm backdrop-blur dark:border-gray-700 dark:bg-gray-900/80">
                {[
                    {
                        key: 'constants' as TabKey,
                        label: __('Debug Constants', 'debug-suite'),
                        icon: SlidersHorizontal,
                        count: activeConstantCount
                    },
                    {
                        key: 'features' as TabKey,
                        label: __('Features', 'debug-suite'),
                        icon: Layers,
                        count: activeFeatureCount
                    }
                ].map((tab) => {
                    const Icon = tab.icon;
                    const active = activeTab === tab.key;
                    return (
                        <button
                            key={tab.key}
                            onClick={() => setActiveTab(tab.key)}
                            className={classNames(
                                'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition',
                                active
                                    ? 'bg-primary text-white shadow-sm'
                                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'
                            )}>
                            <Icon className="h-4 w-4" />
                            {tab.label}
                            <span
                                className={classNames(
                                    'inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-xs font-semibold',
                                    active
                                        ? 'bg-white/25 text-white'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'
                                )}>
                                {tab.count}
                            </span>
                        </button>
                    );
                })}
            </div>

            {activeTab === 'constants' && (
                <Card>
                    <Card.Header className="flex justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <div className="bg-primary/10 text-primary flex h-10 w-10 items-center justify-center rounded-lg">
                                <SlidersHorizontal className="h-5 w-5" />
                            </div>
                            <div>
                                <h2 className="text-base font-semibold text-gray-900 dark:text-white">
                                    {__('WordPress Debug Constants', 'debug-suite')}
                                </h2>
                                <p className="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                    {__('Control wp-config.php debug flags', 'debug-suite')}
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <CustomSwitch
                                name="wp_debug"
                                checked={Boolean(settings.wp_debug)}
                                onChange={(e) => updateEnv(e.target.checked)}
                                disabled={saving}
                            />
                            <span className="text-sm text-gray-500 dark:text-gray-400">
                                {settings.wp_debug
                                    ? __('Debug Mode Enabled', 'debug-suite')
                                    : __('Debug Mode Disabled', 'debug-suite')}
                            </span>
                        </div>
                    </Card.Header>

                    <Card.Body className="divide-y divide-gray-100 dark:divide-gray-800">
                        {debugToggles.map((toggle) => {
                            const Icon = toggle.icon;
                            const value = Boolean(settings[toggle.name]);
                            const showWarning = toggle.danger && value;
                            return (
                                <label
                                    key={toggle.name}
                                    className={classNames(
                                        'flex cursor-pointer items-start gap-4 px-6 py-5 transition hover:bg-gray-50 dark:hover:bg-gray-800/50',
                                        value && 'bg-primary/[0.03] dark:bg-primary/10'
                                    )}>
                                    <div
                                        className={classNames(
                                            'mt-0.5 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg transition-colors',
                                            value
                                                ? showWarning
                                                    ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400'
                                                    : 'bg-primary/10 text-primary'
                                                : 'bg-gray-100 text-gray-400 dark:bg-gray-800'
                                        )}>
                                        {toggle.danger && !value ? (
                                            <EyeOff className="h-5 w-5" />
                                        ) : (
                                            <Icon className="h-5 w-5" />
                                        )}
                                    </div>
                                    <div className="flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h3 className="font-mono text-sm font-semibold text-gray-900 dark:text-white">
                                                {toggle.title}
                                            </h3>
                                            {value ? (
                                                <span
                                                    className={classNames(
                                                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                                                        showWarning
                                                            ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400'
                                                            : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                                    )}>
                                                    {showWarning ? (
                                                        <AlertTriangle className="h-3 w-3" />
                                                    ) : (
                                                        <CheckCircle2 className="h-3 w-3" />
                                                    )}
                                                    {showWarning
                                                        ? __('Unsafe in production', 'debug-suite')
                                                        : __('Active', 'debug-suite')}
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                                    {__('Off', 'debug-suite')}
                                                </span>
                                            )}
                                        </div>
                                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {toggle.description}
                                        </p>
                                    </div>
                                    <CustomSwitch
                                        name={toggle.name}
                                        checked={value}
                                        onChange={changeHandler}
                                        disabled={saving}
                                        className="mt-1"
                                    />
                                </label>
                            );
                        })}
                    </Card.Body>
                </Card>
            )}

            {activeTab === 'features' && (
                <Card>
                    <Card.Header>
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div className="flex items-center gap-3">
                                <div className="bg-primary/10 text-primary flex h-10 w-10 items-center justify-center rounded-lg">
                                    <Sparkles className="h-5 w-5" />
                                </div>
                                <div>
                                    <h2 className="text-base font-semibold text-gray-900 dark:text-white">
                                        {__('Debug Suite Features', 'debug-suite')}
                                    </h2>
                                    <p className="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                        {__('Toggle modules on or off — page reloads on change', 'debug-suite')}
                                    </p>
                                </div>
                            </div>
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <input
                                    type="text"
                                    placeholder={__('Search features...', 'debug-suite')}
                                    className="focus:border-primary focus:ring-primary h-10 w-full rounded-lg border border-gray-200 bg-white pr-4 pl-10 text-sm focus:ring-1 focus:outline-none sm:w-72 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    value={featureSearch}
                                    onChange={(e) => setFeatureSearch(e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="mt-4 flex flex-wrap gap-2">
                            {categories.map((category) => (
                                <button
                                    key={category.id}
                                    onClick={() => setFeatureCategory(category.id)}
                                    className={classNames(
                                        'inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium transition',
                                        featureCategory === category.id
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'
                                    )}>
                                    {category.label}
                                    <span
                                        className={classNames(
                                            'inline-flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] font-semibold',
                                            featureCategory === category.id
                                                ? 'bg-white/25 text-white'
                                                : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                                        )}>
                                        {category.count}
                                    </span>
                                </button>
                            ))}
                        </div>
                    </Card.Header>

                    <Card.Body>
                        {featuresError && (
                            <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                                {featuresError}
                            </div>
                        )}

                        {featuresLoading ? (
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {Array.from({ length: 3 }).map((_, i) => (
                                    <div
                                        key={i}
                                        className="h-36 animate-pulse rounded-xl border border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/50"
                                    />
                                ))}
                            </div>
                        ) : filteredFeatures.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-16 text-center">
                                <div className="rounded-full bg-gray-100 p-3 dark:bg-gray-800">
                                    <Search className="h-6 w-6 text-gray-400" />
                                </div>
                                <h3 className="mt-4 text-base font-medium text-gray-900 dark:text-white">
                                    {__('No features found', 'debug-suite')}
                                </h3>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {__('Try adjusting your search or category filter.', 'debug-suite')}
                                </p>
                                <button
                                    onClick={() => {
                                        setFeatureSearch('');
                                        setFeatureCategory('all');
                                    }}
                                    className="text-primary hover:text-primary/80 mt-4 text-sm font-medium">
                                    {__('Clear filters', 'debug-suite')}
                                </button>
                            </div>
                        ) : (
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {filteredFeatures.map((item) => {
                                    const Icon = item.icon;
                                    const isSaving = savingFeatureId === item.id;
                                    return (
                                        <div
                                            key={item.id}
                                            className={classNames(
                                                'group relative flex flex-col rounded-xl border p-4 transition hover:shadow-md',
                                                item.enabled
                                                    ? 'border-primary/30 bg-primary/[0.03] dark:border-primary/40 dark:bg-primary/10'
                                                    : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800/30',
                                                isSaving && 'opacity-60'
                                            )}>
                                            <div className="flex items-start justify-between">
                                                <div
                                                    className={classNames(
                                                        'flex h-10 w-10 items-center justify-center rounded-lg transition',
                                                        item.enabled
                                                            ? 'bg-primary/15 text-primary'
                                                            : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                                                    )}>
                                                    <Icon className="h-5 w-5" />
                                                </div>
                                                <CustomSwitch
                                                    checked={item.enabled}
                                                    onChange={() => toggleFeature(item.id)}
                                                    disabled={isSaving}
                                                />
                                            </div>
                                            <h3 className="mt-3 text-base font-semibold text-gray-900 dark:text-white">
                                                {item.title}
                                            </h3>
                                            <p className="mt-1 flex-1 text-sm text-gray-500 dark:text-gray-400">
                                                {item.description}
                                            </p>
                                            <div className="mt-3 flex items-center justify-between">
                                                <span
                                                    className={classNames(
                                                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                                                        item.enabled
                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                                            : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'
                                                    )}>
                                                    <span
                                                        className={classNames(
                                                            'h-1.5 w-1.5 rounded-full',
                                                            item.enabled ? 'bg-emerald-500' : 'bg-gray-400'
                                                        )}
                                                    />
                                                    {item.enabled
                                                        ? __('Active', 'debug-suite')
                                                        : __('Inactive', 'debug-suite')}
                                                </span>
                                                <span className="text-xs text-gray-400 capitalize">
                                                    {item.category}
                                                </span>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </Card.Body>
                </Card>
            )}
        </div>
    );
};

export default DebugConfig;
