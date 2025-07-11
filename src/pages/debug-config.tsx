import CustomSwitch from '@/components/base/switch';
import { useToast } from '@/components/base/toast';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Loader2 } from 'lucide-react';

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

    const updateSetting = async (key: keyof DebugSettings, value: boolean) => {
        setSaving(true);

        const updatedSettings = {
            ...settings,
            [key]: value
        };

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
            <div className="space-y-3">
                {/* Debug Mode Card */}
                <div
                    className={`relative rounded-md p-3 pl-8 ${
                        settings.debug
                            ? 'cursor-not-allowed bg-[#e83f94]/5 opacity-80 dark:bg-[#e83f94]/10'
                            : 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50'
                    }`}
                >
                    <div className="absolute top-3 left-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            className={`lucide lucide-check-circle2 size-5 ${
                                settings.debug ? 'text-[#e83f94]' : 'text-muted-foreground/30'
                            }`}
                        >
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>
                    <div className="ml-8 flex items-center justify-between">
                        <div>
                            <h2 className="text-lg font-semibold">WordPress Debug Mode</h2>
                            <p className="text-muted-foreground text-sm">
                                {settings.debug
                                    ? 'Debug mode is enabled. This helps identify issues by showing PHP notices and warnings.'
                                    : 'Debug mode is disabled. Enable to show PHP notices and warnings.'}
                            </p>
                            {settings.debug && (
                                <p className="mt-1 text-xs font-medium text-[#e83f94]">Required for viewer</p>
                            )}
                        </div>
                        <CustomSwitch
                            checked={Boolean(settings.debug)}
                            onChange={(event) => {
                                const target = event.target as HTMLInputElement;
                                void updateSetting('debug', target.checked);
                            }}
                            disabled={saving}
                        />
                    </div>
                </div>

                {/* Debug Log Card */}
                <div
                    className={`relative rounded-md p-3 pl-8 ${
                        settings.debug_log
                            ? 'cursor-not-allowed bg-[#e83f94]/5 opacity-80 dark:bg-[#e83f94]/10'
                            : 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50'
                    }`}
                >
                    <div className="absolute top-3 left-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            className={`lucide lucide-check-circle2 size-5 ${
                                settings.debug_log ? 'text-[#e83f94]' : 'text-muted-foreground/30'
                            }`}
                        >
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>
                    <div className="ml-8 flex items-center justify-between">
                        <div>
                            <h2 className="text-lg font-semibold">Error Logging</h2>
                            <p className="text-muted-foreground text-sm">
                                {settings.debug_log
                                    ? 'Error logging is enabled. All errors will be saved to debug.log for review.'
                                    : 'Error logging is disabled. Enable to save errors to debug.log for review.'}
                            </p>
                            {settings.debug_log && (
                                <p className="mt-1 text-xs font-medium text-[#e83f94]">Required for viewer</p>
                            )}
                        </div>
                        <CustomSwitch
                            checked={settings.debug_log}
                            onChange={(event) => {
                                const target = event.target as HTMLInputElement;
                                void updateSetting('debug_log', target.checked);
                            }}
                            disabled={saving}
                        />
                    </div>
                </div>

                {/* Debug Display Card */}
                <div
                    className={`relative rounded-md p-3 pl-8 ${
                        settings.debug_display
                            ? 'cursor-not-allowed bg-[#e83f94]/5 opacity-80 dark:bg-[#e83f94]/10'
                            : 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50'
                    }`}
                >
                    <div className="absolute top-3 left-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            className={`lucide lucide-check-circle2 size-5 ${
                                settings.debug_display ? 'text-[#e83f94]' : 'text-muted-foreground/30'
                            }`}
                        >
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>
                    <div className="ml-8 flex items-center justify-between">
                        <div>
                            <h2 className="text-lg font-semibold">Error Display</h2>
                            <p className="text-muted-foreground text-sm">
                                {settings.debug_display
                                    ? 'Error display is enabled. Errors will be visible to visitors.'
                                    : 'Error display is disabled. Errors will be hidden from visitors.'}
                            </p>
                            {settings.debug_display && (
                                <p className="mt-1 text-xs font-medium text-yellow-600">
                                    ⚠️ Errors visible to all visitors
                                </p>
                            )}
                        </div>
                        <CustomSwitch
                            checked={Boolean(settings.debug_display)}
                            onChange={(event) => {
                                const target = event.target as HTMLInputElement;
                                void updateSetting('debug_display', target.checked);
                            }}
                            disabled={saving}
                        />
                    </div>
                </div>

                {/* Log Viewer Card */}
                <div className="relative rounded-md p-3 pl-8">
                    <div className="absolute top-3 left-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            className="lucide lucide-check-circle2 size-5 text-[#e83f94]"
                        >
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>
                    <div className="ml-8">
                        <h2 className="text-lg font-semibold">Log Viewer</h2>
                        <p className="text-muted-foreground mb-4 text-sm">
                            Log viewer is installed and ready to use. You can access it using the button below.
                        </p>
                        <div className="my-4 border-t border-gray-200 pt-2 dark:border-gray-800"></div>
                    </div>
                </div>
            </div>

            {/* Saving indicator */}
            {saving && (
                <div className="rounded-lg bg-blue-50 p-4">
                    <div className="flex items-center space-x-3">
                        <Loader2 className="h-5 w-5 animate-spin text-blue-600" />
                        <p className="text-sm font-medium text-blue-900">
                            {__('Saving configuration...', 'debug-suite')}
                        </p>
                    </div>
                </div>
            )}
        </div>
    );
};

export default DebugConfig;
