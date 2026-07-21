import Card from '@/components/base/card';
import CustomSwitch from '@/components/base/switch';
import { DebugState } from '@/types';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { AlertTriangle, Bug, CheckCircle2, Eye, EyeOff, FileText, Settings } from 'lucide-react';
import type React from 'react';
import { toast } from 'sonner';

interface SettingsResponse {
    success: boolean;
    settings: DebugState;
    message?: string;
}

const DebugConfig = () => {
    const [settings, setSettings] = useState({
        debug: window.debugSuite.wp_debug || false,
        debug_log: window.debugSuite.wp_debug_log || false,
        debug_display: window.debugSuite.wp_debug_display || false
    });
    const [saving, setSaving] = useState(false);

    const updateSetting = async (values: Partial<DebugState>) => {
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

    return (
        <div className="mx-auto max-w-4xl space-y-6">
            {/* Settings List */}
            <Card>
                <Card.Header>
                    <div className="flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                            <Settings className="h-4 w-4 text-gray-600 dark:text-gray-300" />
                        </div>
                        <div>
                            <Card.Title>{__('Configuration', 'debug-suite')}</Card.Title>
                            <Card.Subtitle>{__('Manage your WordPress debug constants', 'debug-suite')}</Card.Subtitle>
                        </div>
                    </div>
                    {/* Toggle Env */}
                    <div className="flex items-center gap-2">
                        <CustomSwitch
                            name="debug"
                            checked={Boolean(settings.debug)}
                            onChange={(e) => updateEnv(e.target.checked)}
                            disabled={saving}
                        />
                        <span className="text-sm text-gray-500 dark:text-gray-400">
                            {settings.debug
                                ? __('Debug Mode Enabled', 'debug-suite')
                                : __('Debug Mode Disabled', 'debug-suite')}
                        </span>
                    </div>
                </Card.Header>
                <Card.Body className="divide-y divide-gray-100 dark:divide-gray-700">
                    {/* Debug Mode */}
                    <div className="flex items-start justify-between py-4 first:pt-0">
                        <div className="flex gap-4">
                            <div
                                className={`mt-1 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg transition-colors ${settings.debug ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-400 dark:bg-gray-800'}`}>
                                <Bug className="h-5 w-5" />
                            </div>
                            <div>
                                <h3 className="font-medium text-gray-900 dark:text-white">
                                    {__('WP_DEBUG', 'debug-suite')}
                                </h3>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {__(
                                        'Enable the built-in WordPress debug mode to track PHP errors, notices, and warnings.',
                                        'debug-suite'
                                    )}
                                </p>
                                {settings.debug && (
                                    <div className="mt-2 flex items-center gap-1 text-xs font-medium text-green-600 dark:text-green-400">
                                        <CheckCircle2 className="h-3 w-3" />
                                        {__('Active', 'debug-suite')}
                                    </div>
                                )}
                            </div>
                        </div>
                        <CustomSwitch
                            name="debug"
                            checked={Boolean(settings.debug)}
                            onChange={changeHandler}
                            disabled={saving}
                            className="mt-1"
                        />
                    </div>

                    {/* Debug Log */}
                    <div className="flex items-start justify-between py-4">
                        <div className="flex gap-4">
                            <div
                                className={`mt-1 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg transition-colors ${settings.debug_log ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-400 dark:bg-gray-800'}`}>
                                <FileText className="h-5 w-5" />
                            </div>
                            <div>
                                <h3 className="font-medium text-gray-900 dark:text-white">
                                    {__('WP_DEBUG_LOG', 'debug-suite')}
                                </h3>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {__(
                                        'Save all errors to a debug.log file for later analysis. Recommended for production sites.',
                                        'debug-suite'
                                    )}
                                </p>
                            </div>
                        </div>
                        <CustomSwitch
                            name="debug_log"
                            checked={Boolean(settings.debug_log)}
                            onChange={changeHandler}
                            disabled={saving}
                            className="mt-1"
                        />
                    </div>

                    {/* Debug Display */}
                    <div className="flex items-start justify-between py-4">
                        <div className="flex gap-4">
                            <div
                                className={`mt-1 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg transition-colors ${settings.debug_display ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30' : 'bg-gray-100 text-gray-400 dark:bg-gray-800'}`}>
                                {settings.debug_display ? <Eye className="h-5 w-5" /> : <EyeOff className="h-5 w-5" />}
                            </div>
                            <div>
                                <div className="flex items-center gap-2">
                                    <h3 className="font-medium text-gray-900 dark:text-white">
                                        {__('WP_DEBUG_DISPLAY', 'debug-suite')}
                                    </h3>
                                    {settings.debug_display && (
                                        <span className="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                            <AlertTriangle className="mr-1 h-3 w-3" />
                                            {__('Unsafe', 'debug-suite')}
                                        </span>
                                    )}
                                </div>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {__(
                                        'Display errors directly on the screen. This should be disabled on production sites to prevent information leakage.',
                                        'debug-suite'
                                    )}
                                </p>
                            </div>
                        </div>
                        <CustomSwitch
                            name="debug_display"
                            checked={Boolean(settings.debug_display)}
                            onChange={changeHandler}
                            disabled={saving}
                            className="mt-1"
                        />
                    </div>
                </Card.Body>
            </Card>
        </div>
    );
};

export default DebugConfig;
