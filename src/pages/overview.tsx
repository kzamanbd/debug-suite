import Alert from '@/components/ui/alert';
import Badge from '@/components/ui/badge';
import Button from '@/components/ui/button';
import Card from '@/components/ui/card';

import apiFetch from '@wordpress/api-fetch';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import {
    Activity,
    AlertCircle,
    AlertTriangle,
    BookOpen,
    Bug,
    CheckCircle,
    Clock,
    Database,
    ExternalLink,
    FileText,
    HardDrive,
    HelpCircle,
    MessageSquare,
    RefreshCw,
    Settings,
    Timer,
    Zap
} from 'lucide-react';
import { Link } from 'react-router-dom';
import { toast } from 'react-toastify';

interface DashboardStats {
    logs: {
        total_entries: number;
        recent_errors: number;
        file_size_human: string;
        levels: Record<string, number>;
        last_modified: number;
    };
    system: {
        wp_debug: boolean;
        wp_debug_log: boolean;
        wp_debug_display: boolean;
        php_version: string;
        wp_version: string;
    };
}

interface TopError {
    message: string;
    count: number;
    level: string;
    last_seen: string;
}

interface SlowQuery {
    query: string;
    execution_time: number;
    calls: number;
    source: string;
}

// Mock slow queries data (replace with real API later)
const mockSlowQueries: SlowQuery[] = [
    {
        query: 'SELECT * FROM wp_posts WHERE post_status = "publish" ORDER BY post_date DESC',
        execution_time: 2.45,
        calls: 12,
        source: 'wp-includes/query.php:3421'
    },
    {
        query: 'SELECT COUNT(*) FROM wp_comments WHERE comment_approved = "1"',
        execution_time: 1.89,
        calls: 8,
        source: 'wp-includes/comment.php:892'
    },
    {
        query: 'SELECT * FROM wp_options WHERE autoload = "yes"',
        execution_time: 1.67,
        calls: 45,
        source: 'wp-includes/option.php:174'
    },
    {
        query: 'SELECT meta_value FROM wp_postmeta WHERE post_id IN (...)',
        execution_time: 1.23,
        calls: 23,
        source: 'wp-includes/meta.php:567'
    },
    {
        query: 'SELECT * FROM wp_users WHERE user_login = %s',
        execution_time: 0.98,
        calls: 5,
        source: 'wp-includes/user.php:245'
    }
];

const Overview = () => {
    const [stats, setStats] = useState<DashboardStats | null>(null);
    const [topErrors, setTopErrors] = useState<TopError[]>([]);
    const [slowQueries, setSlowQueries] = useState<SlowQuery[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const settings = window.debugSuite;

    const fetchDashboardData = useCallback(
        async (showRefreshToast = false) => {
            try {
                setRefreshing(true);

                // Fetch log stats
                const logStats = await apiFetch<DashboardStats['logs']>({
                    path: '/debug-suite/v1/logs/stats'
                });

                // Fetch recent log entries to analyze top errors
                const recentLogsPath = addQueryArgs('/debug-suite/v1/logs', {
                    per_page: 10,
                    level_filter: 'error'
                });

                const recentLogs = await apiFetch<{
                    entries: Array<{ message: string; level: string; timestamp: string }>;
                }>({
                    path: recentLogsPath
                });

                // Process top errors
                const errorCounts: Record<string, { count: number; level: string; last_seen: string }> = {};
                recentLogs.entries.forEach((entry) => {
                    const message = entry.message.substring(0, 100) + (entry.message.length > 100 ? '...' : '');
                    if (typeof errorCounts[message] === 'undefined') {
                        errorCounts[message] = { count: 0, level: entry.level, last_seen: entry.timestamp };
                    }
                    errorCounts[message].count++;
                    if (entry.timestamp > errorCounts[message].last_seen) {
                        errorCounts[message].last_seen = entry.timestamp;
                    }
                });

                const topErrorsList = Object.keys(errorCounts)
                    .map((message) => ({ message, ...errorCounts[message] }))
                    .sort((a, b) => b.count - a.count)
                    .slice(0, 5);

                setStats({
                    logs: logStats,
                    system: {
                        wp_debug: settings.wpDebug,
                        wp_debug_log: settings.wpDebugLog,
                        wp_debug_display: settings.wpDebugDisplay,
                        php_version: settings.phpVersion,
                        wp_version: settings.wpVersion
                    }
                });

                setTopErrors(topErrorsList);
                setSlowQueries(mockSlowQueries);

                if (showRefreshToast) {
                    toast.success(__('Dashboard data refreshed', 'debug-suite'));
                }
            } catch (error) {
                console.error('Error fetching dashboard data:', error);
                toast.error(__('Failed to load dashboard data', 'debug-suite'));
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        [settings]
    );

    useEffect(() => {
        void fetchDashboardData();
    }, [fetchDashboardData]);

    const getStatusIcon = (enabled: boolean) => {
        return enabled ? (
            <CheckCircle className="h-4 w-4 text-green-500" />
        ) : (
            <AlertCircle className="h-4 w-4 text-gray-400" />
        );
    };

    const getErrorLevelColor = (level: string) => {
        switch (level.toLowerCase()) {
            case 'error':
            case 'fatal':
                return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            case 'warning':
                return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
            case 'notice':
                return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
        }
    };

    if (loading) {
        return (
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <div className="h-8 w-48 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div className="mt-2 h-4 w-96 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div>
                    </div>
                </div>
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    {Array.from({ length: 4 }).map((_, i) => (
                        <div key={i} className="h-32 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-700"></div>
                    ))}
                </div>
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {Array.from({ length: 2 }).map((_, i) => (
                        <div key={i} className="h-96 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-700"></div>
                    ))}
                </div>
            </div>
        );
    }

    if (!stats) {
        return (
            <Alert variant="danger" className="mx-auto mt-8 max-w-md">
                <AlertCircle className="h-4 w-4" />
                <div>
                    <h3 className="font-semibold">{__('Unable to load dashboard', 'debug-suite')}</h3>
                    <p className="mt-1 text-sm">
                        {__('There was an error loading the dashboard data. Please refresh the page.', 'debug-suite')}
                    </p>
                </div>
            </Alert>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <Button
                    onClick={() => fetchDashboardData(true)}
                    className="flex items-center gap-2"
                    disabled={refreshing}
                >
                    <RefreshCw className={`h-4 w-4 ${refreshing ? 'animate-spin' : ''}`} />
                    {__('Refresh', 'debug-suite')}
                </Button>
            </div>

            {/* Quick Stats Cards */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                {/* Total Log Entries */}
                <Card className="border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100 p-6 dark:border-blue-800 dark:from-blue-900/20 dark:to-blue-800/20">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-blue-600 dark:text-blue-400">
                                {__('Total Log Entries', 'debug-suite')}
                            </p>
                            <p className="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                {stats.logs.total_entries.toLocaleString()}
                            </p>
                        </div>
                        <FileText className="h-8 w-8 text-blue-500" />
                    </div>
                </Card>

                {/* Recent Errors */}
                <Card className="border-red-200 bg-gradient-to-br from-red-50 to-red-100 p-6 dark:border-red-800 dark:from-red-900/20 dark:to-red-800/20">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-red-600 dark:text-red-400">
                                {__('Recent Errors (24h)', 'debug-suite')}
                            </p>
                            <p className="text-2xl font-bold text-red-900 dark:text-red-100">
                                {stats.logs.recent_errors}
                            </p>
                        </div>
                        <AlertTriangle className="h-8 w-8 text-red-500" />
                    </div>
                </Card>

                {/* Log File Size */}
                <Card className="border-purple-200 bg-gradient-to-br from-purple-50 to-purple-100 p-6 dark:border-purple-800 dark:from-purple-900/20 dark:to-purple-800/20">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-purple-600 dark:text-purple-400">
                                {__('Log File Size', 'debug-suite')}
                            </p>
                            <p className="text-2xl font-bold text-purple-900 dark:text-purple-100">
                                {stats.logs.file_size_human}
                            </p>
                        </div>
                        <HardDrive className="h-8 w-8 text-purple-500" />
                    </div>
                </Card>

                {/* System Status */}
                <Card className="border-green-200 bg-gradient-to-br from-green-50 to-green-100 p-6 dark:border-green-800 dark:from-green-900/20 dark:to-green-800/20">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-green-600 dark:text-green-400">
                                {__('Debug Status', 'debug-suite')}
                            </p>
                            <p className="text-2xl font-bold text-green-900 dark:text-green-100">
                                {stats.system.wp_debug ? __('Active', 'debug-suite') : __('Inactive', 'debug-suite')}
                            </p>
                        </div>
                        <Activity className="h-8 w-8 text-green-500" />
                    </div>
                </Card>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {/* System Configuration */}
                <Card className="p-6">
                    <div className="mb-4 flex items-center gap-2">
                        <Settings className="text-primary h-5 w-5" />
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                            {__('System Configuration', 'debug-suite')}
                        </h3>
                    </div>
                    <div className="space-y-4">
                        <div className="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                            <div className="flex items-center gap-2">
                                {getStatusIcon(stats.system.wp_debug)}
                                <span className="text-sm font-medium">{__('WP Debug', 'debug-suite')}</span>
                            </div>
                            <Badge variant={stats.system.wp_debug ? 'success' : 'default'}>
                                {stats.system.wp_debug ? __('Enabled', 'debug-suite') : __('Disabled', 'debug-suite')}
                            </Badge>
                        </div>
                        <div className="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                            <div className="flex items-center gap-2">
                                {getStatusIcon(stats.system.wp_debug_log)}
                                <span className="text-sm font-medium">{__('WP Debug Log', 'debug-suite')}</span>
                            </div>
                            <Badge variant={stats.system.wp_debug_log ? 'success' : 'default'}>
                                {stats.system.wp_debug_log
                                    ? __('Enabled', 'debug-suite')
                                    : __('Disabled', 'debug-suite')}
                            </Badge>
                        </div>
                        <div className="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                            <div className="flex items-center gap-2">
                                {getStatusIcon(stats.system.wp_debug_display)}
                                <span className="text-sm font-medium">{__('WP Debug Display', 'debug-suite')}</span>
                            </div>
                            <Badge variant={stats.system.wp_debug_display ? 'warning' : 'default'}>
                                {stats.system.wp_debug_display
                                    ? __('Enabled', 'debug-suite')
                                    : __('Disabled', 'debug-suite')}
                            </Badge>
                        </div>
                        <div className="border-t border-gray-200 pt-4 dark:border-gray-700">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-gray-600 dark:text-gray-400">
                                    {__('PHP Version', 'debug-suite')}
                                </span>
                                <Badge variant="info">{stats.system.php_version}</Badge>
                            </div>
                            <div className="mt-2 flex items-center justify-between text-sm">
                                <span className="text-gray-600 dark:text-gray-400">
                                    {__('WordPress Version', 'debug-suite')}
                                </span>
                                <Badge variant="info">{stats.system.wp_version}</Badge>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Top Errors */}
                <Card className="p-6">
                    <div className="mb-4 flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Bug className="text-primary h-5 w-5" />
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                {__('Top Recent Errors', 'debug-suite')}
                            </h3>
                        </div>
                        <Button size="sm" className="flex items-center gap-1">
                            <ExternalLink className="h-3 w-3" />
                            {__('View All', 'debug-suite')}
                        </Button>
                    </div>
                    <div className="space-y-3">
                        {topErrors.length > 0 ? (
                            topErrors.map((error, index) => (
                                <div key={index} className="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium text-gray-900 dark:text-white">
                                                {error.message}
                                            </p>
                                            <div className="mt-1 flex items-center gap-2">
                                                <Badge className={`text-xs ${getErrorLevelColor(error.level)}`}>
                                                    {error.level.toUpperCase()}
                                                </Badge>
                                                <span className="text-xs text-gray-500 dark:text-gray-400">
                                                    {__('Count:', 'debug-suite')} {error.count}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="py-8 text-center">
                                <CheckCircle className="mx-auto mb-3 h-12 w-12 text-green-500" />
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    {__('No recent errors found', 'debug-suite')}
                                </p>
                            </div>
                        )}
                    </div>
                </Card>
            </div>

            {/* Top 5 Slowest Queries */}
            <Card className="hidden p-6">
                <div className="mb-4 flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Timer className="text-primary h-5 w-5" />
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                            {__('Top 5 Slowest Queries', 'debug-suite')}
                        </h3>
                    </div>
                    <Button className="flex items-center gap-1 text-sm">
                        <ExternalLink className="h-3 w-3" />
                        {__('View All', 'debug-suite')}
                    </Button>
                </div>
                <div className="space-y-3">
                    {slowQueries.length > 0 ? (
                        slowQueries.map((query, index) => (
                            <div key={index} className="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0 flex-1">
                                        <div className="mb-2 flex items-center gap-2">
                                            <span className="bg-primary flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold text-white">
                                                {index + 1}
                                            </span>
                                            <Badge
                                                variant={
                                                    query.execution_time > 2
                                                        ? 'danger'
                                                        : query.execution_time > 1
                                                          ? 'warning'
                                                          : 'success'
                                                }
                                                className="text-xs"
                                            >
                                                {query.execution_time.toFixed(2)}s
                                            </Badge>
                                            <span className="text-xs text-gray-500 dark:text-gray-400">
                                                {query.calls} {__('calls', 'debug-suite')}
                                            </span>
                                        </div>
                                        <code className="block truncate font-mono text-sm text-gray-800 dark:text-gray-200">
                                            {query.query}
                                        </code>
                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {__('Source:', 'debug-suite')} {query.source}
                                        </p>
                                    </div>
                                    <div className="flex flex-col items-end text-right">
                                        <div className="flex items-center gap-1 text-orange-600 dark:text-orange-400">
                                            <Clock className="h-3 w-3" />
                                            <span className="text-sm font-medium">
                                                {(query.execution_time * query.calls).toFixed(2)}s
                                            </span>
                                        </div>
                                        <span className="text-xs text-gray-500 dark:text-gray-400">
                                            {__('total time', 'debug-suite')}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="py-8 text-center">
                            <CheckCircle className="mx-auto mb-3 h-12 w-12 text-green-500" />
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                {__('No slow queries detected', 'debug-suite')}
                            </p>
                        </div>
                    )}
                </div>
            </Card>

            {/* Quick Actions */}
            <Card className="p-6">
                <div className="mb-4 flex items-center gap-2">
                    <Zap className="text-primary h-5 w-5" />
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                        {__('Quick Actions', 'debug-suite')}
                    </h3>
                </div>
                <div className="flex flex-wrap gap-4">
                    <Button as={Link} to="/debug-log">
                        <FileText className="h-5 w-5" />
                        <span>{__('View Debug Logs', 'debug-suite')}</span>
                    </Button>
                    <Button as={Link} to="/settings">
                        <Settings className="h-5 w-5" />
                        <span>{__('Debug Settings', 'debug-suite')}</span>
                    </Button>
                    <Button as={Link} to="/file-manager">
                        <Database className="h-5 w-5" />
                        <span>{__('File Manager', 'debug-suite')}</span>
                    </Button>
                </div>
            </Card>

            {/* Help & Resources */}
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card className="p-6">
                    <div className="mb-4 flex items-center gap-2">
                        <BookOpen className="text-primary h-5 w-5" />
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                            {__('Documentation', 'debug-suite')}
                        </h3>
                    </div>
                    <div className="space-y-3">
                        <a
                            href="#"
                            className="flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700"
                        >
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400">
                                    <BookOpen className="h-4 w-4" />
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium text-gray-900 dark:text-white">
                                        {__('Getting Started Guide', 'debug-suite')}
                                    </h4>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        {__('Learn the basics of Debug Suite', 'debug-suite')}
                                    </p>
                                </div>
                            </div>
                            <ExternalLink className="h-4 w-4 text-gray-400" />
                        </a>

                        <a
                            href="#"
                            className="flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700"
                        >
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400">
                                    <CheckCircle className="h-4 w-4" />
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium text-gray-900 dark:text-white">
                                        {__('Best Practices', 'debug-suite')}
                                    </h4>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        {__('WordPress debugging tips and tricks', 'debug-suite')}
                                    </p>
                                </div>
                            </div>
                            <ExternalLink className="h-4 w-4 text-gray-400" />
                        </a>

                        <a
                            href="#"
                            className="flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700"
                        >
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-400">
                                    <Zap className="h-4 w-4" />
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium text-gray-900 dark:text-white">
                                        {__('Performance Optimization', 'debug-suite')}
                                    </h4>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        {__('Speed up your WordPress site', 'debug-suite')}
                                    </p>
                                </div>
                            </div>
                            <ExternalLink className="h-4 w-4 text-gray-400" />
                        </a>
                    </div>
                </Card>

                <Card className="p-6">
                    <div className="mb-4 flex items-center gap-2">
                        <HelpCircle className="text-primary h-5 w-5" />
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                            {__('Support & Community', 'debug-suite')}
                        </h3>
                    </div>
                    <div className="space-y-3">
                        <a
                            href="#"
                            className="flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700"
                        >
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-400">
                                    <MessageSquare className="h-4 w-4" />
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium text-gray-900 dark:text-white">
                                        {__('Community Forum', 'debug-suite')}
                                    </h4>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        {__('Get help from the community', 'debug-suite')}
                                    </p>
                                </div>
                            </div>
                            <ExternalLink className="h-4 w-4 text-gray-400" />
                        </a>

                        <a
                            href="#"
                            className="flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700"
                        >
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400">
                                    <AlertTriangle className="h-4 w-4" />
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium text-gray-900 dark:text-white">
                                        {__('Report Bug', 'debug-suite')}
                                    </h4>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        {__('Found an issue? Let us know', 'debug-suite')}
                                    </p>
                                </div>
                            </div>
                            <ExternalLink className="h-4 w-4 text-gray-400" />
                        </a>

                        <a
                            href="#"
                            className="flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700"
                        >
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded bg-indigo-100 text-indigo-600 dark:bg-indigo-900 dark:text-indigo-400">
                                    <Zap className="h-4 w-4" />
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium text-gray-900 dark:text-white">
                                        {__('Feature Request', 'debug-suite')}
                                    </h4>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        {__('Suggest new features', 'debug-suite')}
                                    </p>
                                </div>
                            </div>
                            <ExternalLink className="h-4 w-4 text-gray-400" />
                        </a>
                    </div>
                </Card>
            </div>
        </div>
    );
};

export default Overview;
