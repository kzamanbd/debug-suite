import CustomSwitch from '@/components/base/switch';
import { useToast } from '@/components/base/toast';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { AlertTriangle, Bug, CheckCircle2, Eye, EyeOff, FileText, Loader2, Shield, Zap } from 'lucide-react';

interface DebugSettings {
    debug: boolean;
    debug_log: boolean;
    debug_display: boolean;
}

interface SettingsResponse {
    success: boolean;
    settings: DebugSettings;
    message?: string;
}

const DebugConfig = () => {
    const toast = useToast();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [settings, setSettings] = useState<DebugSettings>({
        debug: false,
        debug_log: false,
        debug_display: false
    });

    useEffect(() => {
        const fetchSettings = async () => {
            try {
                const response = await apiFetch<{
                    WP_DEBUG: boolean;
                    WP_DEBUG_LOG: boolean;
                    WP_DEBUG_DISPLAY: boolean;
                }>({
                    path: '/debug-suite/v1/settings',
                    method: 'GET'
                });

                setSettings({
                    debug: response.WP_DEBUG,
                    debug_log: response.WP_DEBUG_LOG,
                    debug_display: response.WP_DEBUG_DISPLAY
                });
            } catch (error) {
                console.error('Error fetching settings:', error);
            } finally {
                setLoading(false);
            }
        };

        void fetchSettings();
    }, []);

    const updateSetting = async (values: Partial<DebugSettings>) => {
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
                toast.success(response.message || __('Setting updated successfully!', 'debug-suite'));
            } else {
                toast.error(__('Failed to update setting.', 'debug-suite'));
            }
        } catch (error) {
            console.error('Error updating setting:', error);
            toast.error(__('Failed to update setting.', 'debug-suite'));
        } finally {
            setSaving(false);
        }
    };

    const changeHandler = (event: React.ChangeEvent<HTMLInputElement>) => {
        const key = event.target.name as keyof DebugSettings;
        const value = event.target.checked;
        setSettings((prev) => ({
            ...prev,
            [key]: value
        }));
        void updateSetting({ [key]: value });
    };

    const updateEnv = (mode: boolean) => {
        void updateSetting({
            ...settings,
            debug: mode,
            debug_log: mode
        });
    };

    if (loading) {
        return (
            <div className="flex min-h-96 items-center justify-center">
                <div className="text-center">
                    <Loader2 className="text-primary mx-auto h-8 w-8 animate-spin" />
                    <p className="mt-2 text-sm text-gray-600">{__('Loading debug settings...', 'debug-suite')}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {/* Compact Configuration Cards */}
            <div className="grid gap-4 md:grid-cols-3">
                {/* Debug Mode Card */}
                <div
                    className={`rounded-lg border transition-all duration-200 ${
                        settings.debug
                            ? 'border-primary/40 bg-primary/5 shadow-primary/5 shadow-md'
                            : 'hover:border-primary/30 border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800'
                    }`}>
                    <div className="p-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div
                                    className={`rounded-lg p-2 transition-colors duration-200 ${
                                        settings.debug
                                            ? 'bg-primary/20 text-primary'
                                            : 'bg-gray-100 text-gray-400 dark:bg-gray-700'
                                    }`}>
                                    <Bug className="h-4 w-4" />
                                </div>
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h3 className="font-medium text-gray-900 dark:text-white">
                                            {__('Debug Mode', 'debug-suite')}
                                        </h3>
                                        {settings.debug && <CheckCircle2 className="text-primary h-4 w-4" />}
                                    </div>
                                    <p className="text-xs text-gray-600 dark:text-gray-300">
                                        {settings.debug
                                            ? __('PHP notices and warnings tracked', 'debug-suite')
                                            : __('Enable to show PHP errors', 'debug-suite')}
                                    </p>
                                </div>
                            </div>
                            <CustomSwitch
                                name="debug"
                                checked={Boolean(settings.debug)}
                                onChange={changeHandler}
                                disabled={saving}
                            />
                        </div>
                    </div>
                </div>

                {/* Debug Log Card */}
                <div
                    className={`rounded-lg border transition-all duration-200 ${
                        settings.debug_log
                            ? 'border-primary/40 bg-primary/5 shadow-primary/5 shadow-md'
                            : 'hover:border-primary/30 border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800'
                    }`}>
                    <div className="p-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div
                                    className={`rounded-lg p-2 transition-colors duration-200 ${
                                        settings.debug_log
                                            ? 'bg-primary/20 text-primary'
                                            : 'bg-gray-100 text-gray-400 dark:bg-gray-700'
                                    }`}>
                                    <FileText className="h-4 w-4" />
                                </div>
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h3 className="font-medium text-gray-900 dark:text-white">
                                            {__('Error Logging', 'debug-suite')}
                                        </h3>
                                        {settings.debug_log && <CheckCircle2 className="text-primary h-4 w-4" />}
                                    </div>
                                    <p className="text-xs text-gray-600 dark:text-gray-300">
                                        {settings.debug_log
                                            ? __('Errors saved to debug.log', 'debug-suite')
                                            : __('Enable to log errors to file', 'debug-suite')}
                                    </p>
                                </div>
                            </div>
                            <CustomSwitch
                                name="debug_log"
                                checked={settings.debug_log}
                                onChange={changeHandler}
                                disabled={saving}
                            />
                        </div>
                    </div>
                </div>

                {/* Debug Display Card */}
                <div
                    className={`rounded-lg border transition-all duration-200 ${
                        settings.debug_display
                            ? 'border-amber-400/40 bg-amber-50/50 shadow-md shadow-amber-500/5 dark:bg-amber-900/10'
                            : 'border-gray-200 bg-white hover:border-amber-400/30 dark:border-gray-700 dark:bg-gray-800'
                    }`}>
                    <div className="p-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div
                                    className={`rounded-lg p-2 transition-colors duration-200 ${
                                        settings.debug_display
                                            ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400'
                                            : 'bg-gray-100 text-gray-400 dark:bg-gray-700'
                                    }`}>
                                    {settings.debug_display ? (
                                        <Eye className="h-4 w-4" />
                                    ) : (
                                        <EyeOff className="h-4 w-4" />
                                    )}
                                </div>
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h3 className="font-medium text-gray-900 dark:text-white">
                                            {__('Error Display', 'debug-suite')}
                                        </h3>
                                        {settings.debug_display && <AlertTriangle className="h-4 w-4 text-amber-500" />}
                                    </div>
                                    <p className="text-xs text-gray-600 dark:text-gray-300">
                                        {settings.debug_display
                                            ? __('⚠️ Errors visible to visitors', 'debug-suite')
                                            : __('Errors hidden from visitors', 'debug-suite')}
                                    </p>
                                </div>
                            </div>
                            <CustomSwitch
                                name="debug_display"
                                checked={Boolean(settings.debug_display)}
                                onChange={changeHandler}
                                disabled={saving}
                            />
                        </div>
                    </div>
                </div>
            </div>

            {/* Compact Quick Actions */}
            <div className="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div className="mb-3 flex items-center gap-2">
                    <Zap className="text-primary h-4 w-4" />
                    <h3 className="font-medium text-gray-900 dark:text-white">{__('Quick Actions', 'debug-suite')}</h3>
                </div>

                <div className="grid grid-cols-2 gap-2">
                    <button
                        onClick={() => updateEnv(true)}
                        disabled={saving}
                        className="border-primary/20 bg-primary/5 hover:bg-primary/10 flex items-center gap-2 rounded-md border p-2 text-left text-sm transition-colors duration-200 disabled:opacity-50">
                        <CheckCircle2 className="text-primary h-3 w-3 flex-shrink-0" />
                        <div>
                            <div className="text-xs font-medium text-gray-900 dark:text-white">
                                {__('Development', 'debug-suite')}
                            </div>
                            <div className="text-xs text-gray-600 dark:text-gray-300">
                                {__('Debug + Log', 'debug-suite')}
                            </div>
                        </div>
                    </button>

                    <button
                        onClick={() => updateEnv(false)}
                        disabled={saving}
                        className="flex items-center gap-2 rounded-md border border-gray-200 p-2 text-left text-sm transition-colors duration-200 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:hover:bg-gray-700">
                        <Shield className="h-3 w-3 flex-shrink-0 text-gray-500" />
                        <div>
                            <div className="text-xs font-medium text-gray-900 dark:text-white">
                                {__('Production', 'debug-suite')}
                            </div>
                            <div className="text-xs text-gray-600 dark:text-gray-300">
                                {__('Disable all', 'debug-suite')}
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            {/* Compact Saving Indicator */}
            {saving && (
                <div className="fixed right-4 bottom-4 z-50 flex items-center gap-2 rounded-md border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <Loader2 className="text-primary h-4 w-4 animate-spin" />
                    <span className="text-sm text-gray-900 dark:text-white">{__('Saving...', 'debug-suite')}</span>
                </div>
            )}
        </div>
    );
};

export default DebugConfig;
