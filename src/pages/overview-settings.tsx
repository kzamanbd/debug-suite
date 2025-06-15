/**
 * Settings page for Debug Suite plugin.
 *
 * Modernized design with improved layout, spacing, and accessibility.
 *
 * @since 1.0.0
 */
import SettingsSkeleton from '@/components/settings-skeleton';
import Button from '@/components/ui/button';
import Card from '@/components/ui/card';
import ContentTabs from '@/components/ui/content-tabs';
import CustomSwitch from '@/components/ui/custom-switch';
import InputField from '@/components/ui/input-field';
import RadioButton from '@/components/ui/radio-button';
import SearchableSelect from '@/components/ui/searchable-select';

import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { FolderOpen, Settings as SettingsIcon } from 'lucide-react';
import { toast } from 'react-toastify';

interface SettingsState {
    fileManagerAccess: string;
    publicRootPath: string;
    filesUrl: string;
    defaultViewType: string;
    enableTrash: boolean;
    hideHtaccess: boolean;
    logQueries: boolean;
    logErrors: boolean;
    wpDebug: boolean;
    wpDebugLog: boolean;
    wpDebugDisplay: boolean;
    [key: string]: string | boolean | Record<string, { name: string }>;
}

type SettingsResponse = {
    roles: Record<string, { name: string }>;
} & SettingsState;

const defaultSettings: SettingsState = {
    fileManagerAccess: 'administrator',
    publicRootPath: '/wp-content/uploads/',
    filesUrl: 'https://example.com/wp-content/uploads/',
    defaultViewType: 'grid',
    enableTrash: true,
    hideHtaccess: true,
    logQueries: false,
    logErrors: true,
    wpDebug: false,
    wpDebugLog: false,
    wpDebugDisplay: false
};

const Settings = () => {
    const [settings, setSettings] = useState<SettingsState>(defaultSettings);
    const [hasChanges, setHasChanges] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [isLoading, setIsLoading] = useState(true);
    const [fileManagerAccessOptions, setFileManagerAccessOptions] = useState<
        {
            label: string;
            value: string;
        }[]
    >([]);

    const fetchSettings = async () => {
        try {
            setIsLoading(true);
            const response = await apiFetch<SettingsResponse>({ path: '/debug-suite/v1/settings' });
            if (response) {
                setSettings((prev) => ({
                    ...prev,
                    ...response
                }));
                const roles = Object.keys(response.roles).map((role) => ({
                    label: response.roles[role].name,
                    value: role
                }));
                setFileManagerAccessOptions(roles);
            }
        } catch (error) {
            toast.error(__('Failed to fetch settings.', 'debug-suite'));
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        fetchSettings();
    }, []);

    const handleInputChange = (field: keyof SettingsState, value: string | boolean) => {
        setSettings((prevSettings) => ({
            ...prevSettings,
            [field]: value
        }));
        setHasChanges(true);
    };

    const handleSave = async () => {
        if (!hasChanges) {
            toast.info(__('No changes to save.', 'debug-suite'));
            return;
        }
        try {
            setIsSaving(true);
            await apiFetch({
                path: '/debug-suite/v1/settings',
                method: 'POST',
                data: {
                    debug: settings.wpDebug.toString(),
                    debug_log: settings.wpDebugLog.toString(),
                    debug_display: settings.wpDebugDisplay.toString()
                }
            });
            toast.success(__('Settings saved successfully!'));
        } catch (error) {
            toast.error(__('Failed to save settings. Please try again.', 'debug-suite'));
        } finally {
            setHasChanges(false);
            setIsSaving(false);
        }
    };

    const handleReset = () => {
        setSettings(defaultSettings);
        setHasChanges(false);
    };

    if (isLoading) {
        return <SettingsSkeleton />;
    }

    // Compact File Manager Tab
    const fileManagerTab = (
        <Card className="rounded-lg border-0 bg-white/90 p-0 shadow-md dark:bg-gray-900/80">
            <div className="border-primary-100 from-primary-100 via-primary-50 rounded-t-lg border-b bg-gradient-to-r to-white px-4 py-3">
                <h2 className="text-primary-900 dark:text-primary-200 flex items-center gap-2 text-lg font-semibold">
                    <FolderOpen className="text-primary-600 h-5 w-5" />
                    {__('File Manager Configuration', 'debug-suite')}
                </h2>
                <p className="text-primary-700 dark:text-primary-300 mt-1 text-xs">
                    {__('Control access and behavior of the file manager', 'debug-suite')}
                </p>
            </div>
            <div className="space-y-4 px-4 py-4">
                {/* File Manager Access */}
                <div className="grid grid-cols-1 items-start gap-3 md:grid-cols-3">
                    <div>
                        <label className="mb-0.5 block text-sm font-medium text-gray-900 dark:text-gray-100">
                            {__('Who can access File Manager?', 'debug-suite')}
                        </label>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {__('Select user roles that can access the file manager', 'debug-suite')}
                        </p>
                    </div>
                    <div className="md:col-span-2">
                        <SearchableSelect
                            options={fileManagerAccessOptions}
                            value={
                                fileManagerAccessOptions.find((opt) => opt.value === settings.fileManagerAccess) ||
                                fileManagerAccessOptions[0]
                            }
                            onChange={(option) =>
                                handleInputChange('fileManagerAccess', option?.value || 'administrator')
                            }
                        />
                    </div>
                </div>
                {/* Public Root Path */}
                <div className="grid grid-cols-1 items-start gap-3 md:grid-cols-3">
                    <div>
                        <label className="mb-0.5 block text-sm font-medium text-gray-900 dark:text-gray-100">
                            {__('Public Root Path', 'debug-suite')}
                        </label>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {__('The root directory for file operations', 'debug-suite')}
                        </p>
                    </div>
                    <div className="md:col-span-2">
                        <InputField
                            type="text"
                            value={settings.publicRootPath}
                            onChange={(e) => handleInputChange('publicRootPath', e.target.value)}
                            placeholder={__('/wp-content/uploads/', 'debug-suite')}
                        />
                    </div>
                </div>
                {/* Files URL */}
                <div className="grid grid-cols-1 items-start gap-3 md:grid-cols-3">
                    <div>
                        <label className="mb-0.5 block text-sm font-medium text-gray-900 dark:text-gray-100">
                            {__('Files URL', 'debug-suite')}
                        </label>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {__('Base URL for accessing uploaded files', 'debug-suite')}
                        </p>
                    </div>
                    <div className="md:col-span-2">
                        <InputField
                            type="url"
                            value={settings.filesUrl}
                            onChange={(e) => handleInputChange('filesUrl', e.target.value)}
                            placeholder={__('https://example.com/wp-content/uploads/', 'debug-suite')}
                        />
                    </div>
                </div>
                {/* Default View Type */}
                <div className="grid grid-cols-1 items-start gap-3 md:grid-cols-3">
                    <div>
                        <label className="mb-0.5 block text-sm font-medium text-gray-900 dark:text-gray-100">
                            {__('Default View Type', 'debug-suite')}
                        </label>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {__('How files are displayed by default', 'debug-suite')}
                        </p>
                    </div>
                    <div className="flex gap-3 md:col-span-2">
                        <RadioButton
                            label={__('Grid View', 'debug-suite')}
                            name="viewType"
                            value="grid"
                            checked={settings.defaultViewType === 'grid'}
                            onChange={(e) => handleInputChange('defaultViewType', e.target.value)}
                        />
                        <RadioButton
                            label={__('List View', 'debug-suite')}
                            name="viewType"
                            value="list"
                            checked={settings.defaultViewType === 'list'}
                            onChange={(e) => handleInputChange('defaultViewType', e.target.value)}
                        />
                    </div>
                </div>
                {/* Toggle Options */}
                <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div className="space-y-3">
                        <h4 className="mb-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                            {__('File Operations', 'debug-suite')}
                        </h4>
                        <label className="flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">
                            <div>
                                <span className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {__('Enable Trash', 'debug-suite')}
                                </span>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    {__('Move deleted files to trash instead of permanent deletion', 'debug-suite')}
                                </p>
                            </div>
                            <CustomSwitch
                                checked={settings.enableTrash}
                                onChange={(e) => handleInputChange('enableTrash', e.currentTarget.checked)}
                                id="custom_switch_checkbox_enableTrash"
                            />
                        </label>
                        <label className="flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">
                            <div>
                                <span className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {__('Hide .htaccess Files', 'debug-suite')}
                                </span>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    {__('Hide system files from the file manager', 'debug-suite')}
                                </p>
                            </div>
                            <CustomSwitch
                                checked={settings.hideHtaccess}
                                onChange={(e) => handleInputChange('hideHtaccess', e.currentTarget.checked)}
                                id="custom_switch_checkbox_hideHtaccess"
                            />
                        </label>
                    </div>
                </div>
            </div>
        </Card>
    );

    // Compact Debug Tab
    const debugTab = (
        <Card className="rounded-lg border-0 bg-white/90 p-0 shadow-md dark:bg-gray-900/80">
            <div className="border-primary-100 from-primary-100 via-primary-50 rounded-t-lg border-b bg-gradient-to-r to-white px-4 py-3">
                <h2 className="text-primary-900 dark:text-primary-200 flex items-center gap-2 text-lg font-semibold">
                    <SettingsIcon className="text-primary-600 h-5 w-5" />
                    {__('Debug Configuration', 'debug-suite')}
                </h2>
                <p className="text-primary-700 dark:text-primary-300 mt-1 text-xs">
                    {__('Configure debugging and logging options', 'debug-suite')}
                </p>
            </div>
            <div className="space-y-4 px-4 py-4">
                <label className="flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">
                    <div>
                        <span className="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {__('Enable WP Debug', 'debug-suite')}
                        </span>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {__('Enable WordPress debug mode', 'debug-suite')}
                        </p>
                    </div>
                    <CustomSwitch
                        checked={settings.wpDebug}
                        onChange={(e) => handleInputChange('wpDebug', e.currentTarget.checked)}
                        id="custom_switch_checkbox_wpDebug"
                    />
                </label>
                <label className="flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">
                    <div>
                        <span className="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {__('Enable WP Debug Log', 'debug-suite')}
                        </span>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {__('Enable WordPress debug log', 'debug-suite')}
                        </p>
                    </div>
                    <CustomSwitch
                        checked={settings.wpDebugLog}
                        onChange={(e) => handleInputChange('wpDebugLog', e.currentTarget.checked)}
                        id="custom_switch_checkbox_wpDebugLog"
                    />
                </label>
                <label className="flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">
                    <div>
                        <span className="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {__('Enable WP Debug Display', 'debug-suite')}
                        </span>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {__('Enable WordPress debug mode', 'debug-suite')}
                        </p>
                    </div>
                    <CustomSwitch
                        checked={settings.wpDebugDisplay}
                        onChange={(e) => handleInputChange('wpDebugDisplay', e.currentTarget.checked)}
                        id="custom_switch_checkbox_wpDebugDisplay"
                    />
                </label>
                <label className="flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">
                    <div>
                        <span className="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {__('Log Database Queries', 'debug-suite')}
                        </span>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {__('Record all database queries for analysis', 'debug-suite')}
                        </p>
                    </div>
                    <CustomSwitch
                        checked={settings.logQueries}
                        onChange={(e) => handleInputChange('logQueries', e.currentTarget.checked)}
                        id="custom_switch_checkbox_logQueries"
                    />
                </label>
                <label className="flex items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">
                    <div>
                        <span className="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {__('Log PHP Errors', 'debug-suite')}
                        </span>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {__('Capture and log PHP errors and warnings', 'debug-suite')}
                        </p>
                    </div>
                    <CustomSwitch
                        checked={settings.logErrors}
                        onChange={(e) => handleInputChange('logErrors', e.currentTarget.checked)}
                        id="custom_switch_checkbox_logErrors"
                    />
                </label>
            </div>
        </Card>
    );

    return (
        <>
            {/* Header */}
            <div className="mb-4">
                <p className="text-sm text-gray-600 dark:text-gray-300">
                    {__('Configure your debug suite and file manager preferences', 'debug-suite')}
                </p>
            </div>
            <ContentTabs
                tabs={[
                    { key: 'file', label: __('File Manager', 'debug-suite'), content: fileManagerTab },
                    { key: 'debug', label: __('Debug', 'debug-suite'), content: debugTab }
                ]}
            />
            {/* Floating Action Button */}
            <div className="fixed bottom-6 left-1/2 z-50 flex -translate-x-1/2 items-center justify-center">
                <div className="flex gap-2 rounded-full bg-white/90 p-2 shadow-lg dark:bg-gray-900/90">
                    <Button onClick={handleReset} variant="light" className="rounded-full">
                        {__('Reset to Defaults', 'debug-suite')}
                    </Button>
                    <Button
                        onClick={handleSave}
                        disabled={!hasChanges || isSaving}
                        variant="primary"
                        className="rounded-full"
                    >
                        {__('Save Changes', 'debug-suite')}
                    </Button>
                </div>
            </div>
        </>
    );
};

export default Settings;
