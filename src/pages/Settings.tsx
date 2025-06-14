import CustomSwitch from '@/components/CustomSwitch';
import SettingsSkeleton from '@/components/SettingsSkeleton';
import Button from '@/components/ui/Button';
import Card from '@/components/ui/Card';
import InputField from '@/components/ui/InputField';
import RadioButton from '@/components/ui/RadioButton';
import SearchableSelect from '@/components/ui/SearchableSelect';
import { cn } from '@/utils/cn';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
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
}

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

const fileManagerAccessOptions = [
    { value: 'administrator', label: __('Administrator Only', 'debug-suite') },
    { value: 'editor', label: __('Editor and Above', 'debug-suite') },
    { value: 'author', label: __('Author and Above', 'debug-suite') },
    { value: 'contributor', label: __('Contributor and Above', 'debug-suite') },
    { value: 'subscriber', label: __('All Users', 'debug-suite') }
];

const Settings = () => {
    const [settings, setSettings] = useState<SettingsState>(defaultSettings);
    const [hasChanges, setHasChanges] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [isLoading, setIsLoading] = useState(true);

    const fetchSettings = async () => {
        try {
            setIsLoading(true);
            const fetched = await apiFetch({ path: '/debug-suite/v1/settings' });
            if (fetched) {
                setSettings((prev) => ({
                    ...prev,
                    ...fetched
                }));
            }
        } catch (error) {
            toast.error(__('Failed to fetch settings.', 'debug-suite'));
        } finally {
            setIsLoading(false);
        }
    };

    // Fetch settings on component mount
    useEffect(() => {
        fetchSettings();
    }, []);

    // Accepts: (field: keyof SettingsState, value: string | boolean)
    const handleInputChange = (field: keyof SettingsState, value: string | boolean) => {
        setSettings((prevSettings) => ({
            ...prevSettings,
            [field]: value
        }));
        setHasChanges(true);
    };

    const handleSave = async () => {
        // Save logic here
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
        // Reset to default values
        setSettings(defaultSettings);
        setHasChanges(false);
    };

    if (isLoading) {
        return <SettingsSkeleton />;
    }

    return (
        <div>
            {/* Header */}
            <div className="mb-6 sm:mb-8">
                <p className="mt-2 text-sm text-gray-600 sm:text-base">
                    {__('Configure your debug suite and file manager preferences', 'debug-suite')}
                </p>
            </div>

            {/* Settings Form */}
            <div className="space-y-4 sm:space-y-6">
                {/* File Manager Settings */}
                <Card>
                    <div className="border-l-4 border-l-blue-500 bg-blue-50 px-4 py-3 sm:px-6 sm:py-4">
                        <h2 className="flex items-center text-xl font-semibold text-gray-900">
                            <svg
                                className="mr-3 h-6 w-6 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"
                                />
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M8 5a2 2 0 012-2h4a2 2 0 012 2v1H8V5z"
                                />
                            </svg>
                            {__('File Manager Configuration', 'debug-suite')}
                        </h2>
                        <p className="mt-1 text-sm text-blue-700">
                            {__('Control access and behavior of the file manager', 'debug-suite')}
                        </p>
                    </div>

                    <div className="space-y-4 p-4 sm:space-y-6 sm:p-6">
                        {/* File Manager Access */}
                        <div className="grid grid-cols-1 items-start gap-4 md:grid-cols-3">
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-900">
                                    {__('Who can access File Manager?', 'debug-suite')}
                                </label>
                                <p className="text-xs text-gray-500">
                                    {__('Select user roles that can access the file manager', 'debug-suite')}
                                </p>
                            </div>
                            <div className="md:col-span-2">
                                <SearchableSelect
                                    options={fileManagerAccessOptions}
                                    value={
                                        fileManagerAccessOptions.find(
                                            (opt) => opt.value === settings.fileManagerAccess
                                        ) || fileManagerAccessOptions[0]
                                    }
                                    onChange={(option) =>
                                        handleInputChange('fileManagerAccess', option?.value || 'administrator')
                                    }
                                />
                            </div>
                        </div>

                        {/* Public Root Path */}
                        <div className="grid grid-cols-1 items-start gap-4 md:grid-cols-3">
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-900">
                                    {__('Public Root Path', 'debug-suite')}
                                </label>
                                <p className="text-xs text-gray-500">
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
                        <div className="grid grid-cols-1 items-start gap-4 md:grid-cols-3">
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-900">
                                    {__('Files URL', 'debug-suite')}
                                </label>
                                <p className="text-xs text-gray-500">
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
                        <div className="grid grid-cols-1 items-start gap-4 md:grid-cols-3">
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-900">
                                    {__('Default View Type', 'debug-suite')}
                                </label>
                                <p className="text-xs text-gray-500">
                                    {__('How files are displayed by default', 'debug-suite')}
                                </p>
                            </div>
                            <div className="flex gap-4 md:col-span-2">
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
                        <div className="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-2">
                            <div className="space-y-4">
                                <h4 className="font-medium text-gray-900">{__('File Operations', 'debug-suite')}</h4>

                                <label className="flex cursor-pointer items-center justify-between rounded-lg bg-gray-50 p-4 transition-colors hover:bg-gray-100">
                                    <div>
                                        <span className="text-sm font-medium text-gray-900">
                                            {__('Enable Trash', 'debug-suite')}
                                        </span>
                                        <p className="text-xs text-gray-500">
                                            {__(
                                                'Move deleted files to trash instead of permanent deletion',
                                                'debug-suite'
                                            )}
                                        </p>
                                    </div>
                                    <div className="relative">
                                        <CustomSwitch
                                            checked={settings.enableTrash}
                                            onChange={(e) => handleInputChange('enableTrash', e.currentTarget.checked)}
                                            id="custom_switch_checkbox_enableTrash"
                                        />
                                    </div>
                                </label>

                                <label className="flex cursor-pointer items-center justify-between rounded-lg bg-gray-50 p-4 transition-colors hover:bg-gray-100">
                                    <div>
                                        <span className="text-sm font-medium text-gray-900">
                                            {__('Hide .htaccess Files', 'debug-suite')}
                                        </span>
                                        <p className="text-xs text-gray-500">
                                            {__('Hide system files from the file manager', 'debug-suite')}
                                        </p>
                                    </div>
                                    <div className="relative">
                                        <CustomSwitch
                                            checked={settings.hideHtaccess}
                                            onChange={(e) => handleInputChange('hideHtaccess', e.currentTarget.checked)}
                                            id="custom_switch_checkbox_hideHtaccess"
                                        />
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Debug Settings */}
                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="border-l-4 border-l-green-500 bg-green-50 px-4 py-3 sm:px-6 sm:py-4">
                        <h2 className="flex items-center text-xl font-semibold text-gray-900">
                            <svg
                                className="mr-3 h-6 w-6 text-green-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            {__('Debug Configuration', 'debug-suite')}
                        </h2>
                        <p className="mt-1 text-sm text-green-700">
                            {__('Configure debugging and logging options', 'debug-suite')}
                        </p>
                    </div>

                    <div className="space-y-4 p-4 sm:p-6">
                        <label className="flex cursor-pointer items-center justify-between rounded-lg bg-gray-50 p-4 transition-colors hover:bg-gray-100">
                            <div>
                                <span className="text-sm font-medium text-gray-900">
                                    {__('Enable WP Debug', 'debug-suite')}
                                </span>
                                <p className="text-xs text-gray-500">
                                    {__('Enable WordPress debug mode', 'debug-suite')}
                                </p>
                            </div>
                            <div className="relative">
                                <CustomSwitch
                                    checked={settings.wpDebug}
                                    onChange={(e) => handleInputChange('wpDebug', e.currentTarget.checked)}
                                    id="custom_switch_checkbox_wpDebug"
                                />
                            </div>
                        </label>

                        <label className="flex cursor-pointer items-center justify-between rounded-lg bg-gray-50 p-4 transition-colors hover:bg-gray-100">
                            <div>
                                <span className="text-sm font-medium text-gray-900">
                                    {__('Enable WP Debug Log', 'debug-suite')}
                                </span>
                                <p className="text-xs text-gray-500">
                                    {__('Enable WordPress debug log', 'debug-suite')}
                                </p>
                            </div>
                            <div className="relative">
                                <CustomSwitch
                                    checked={settings.wpDebugLog}
                                    onChange={(e) => handleInputChange('wpDebugLog', e.currentTarget.checked)}
                                    id="custom_switch_checkbox_wpDebugLog"
                                />
                            </div>
                        </label>

                        <label className="flex cursor-pointer items-center justify-between rounded-lg bg-gray-50 p-4 transition-colors hover:bg-gray-100">
                            <div>
                                <span className="text-sm font-medium text-gray-900">
                                    {__('Enable WP Debug Display', 'debug-suite')}
                                </span>
                                <p className="text-xs text-gray-500">
                                    {__('Enable WordPress debug mode', 'debug-suite')}
                                </p>
                            </div>
                            <div className="relative">
                                <CustomSwitch
                                    checked={settings.wpDebugDisplay}
                                    onChange={(e) => handleInputChange('wpDebugDisplay', e.currentTarget.checked)}
                                    id="custom_switch_checkbox_wpDebugDisplay"
                                />
                            </div>
                        </label>

                        <label className="flex cursor-pointer items-center justify-between rounded-lg bg-gray-50 p-4 transition-colors hover:bg-gray-100">
                            <div>
                                <span className="text-sm font-medium text-gray-900">
                                    {__('Log Database Queries', 'debug-suite')}
                                </span>
                                <p className="text-xs text-gray-500">
                                    {__('Record all database queries for analysis', 'debug-suite')}
                                </p>
                            </div>
                            <div className="relative">
                                <CustomSwitch
                                    checked={settings.logQueries}
                                    onChange={(e) => handleInputChange('logQueries', e.currentTarget.checked)}
                                    id="custom_switch_checkbox_logQueries"
                                />
                            </div>
                        </label>

                        <label className="flex cursor-pointer items-center justify-between rounded-lg bg-gray-50 p-4 transition-colors hover:bg-gray-100">
                            <div>
                                <span className="text-sm font-medium text-gray-900">
                                    {__('Log PHP Errors', 'debug-suite')}
                                </span>
                                <p className="text-xs text-gray-500">
                                    {__('Capture and log PHP errors and warnings', 'debug-suite')}
                                </p>
                            </div>
                            <div className="relative">
                                <CustomSwitch
                                    checked={settings.logErrors}
                                    onChange={(e) => handleInputChange('logErrors', e.currentTarget.checked)}
                                    id="custom_switch_checkbox_logErrors"
                                />
                            </div>
                        </label>
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                    <div className="flex flex-col items-start justify-between space-y-3 sm:flex-row sm:items-center sm:space-y-0">
                        <div>
                            {hasChanges && (
                                <p className="flex items-center text-sm text-amber-600">
                                    <svg className="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                                        />
                                    </svg>
                                    {__('You have unsaved changes', 'debug-suite')}
                                </p>
                            )}
                        </div>
                        <div className="flex space-x-3">
                            <Button onClick={handleReset} variant="light">
                                {__('Reset to Defaults', 'debug-suite')}
                            </Button>
                            <Button
                                onClick={handleSave}
                                disabled={!hasChanges || isSaving}
                                className={cn(
                                    'rounded-lg px-4 py-2 font-medium transition-all focus:ring-2 focus:ring-offset-2 focus:outline-none sm:px-8 sm:py-3',
                                    hasChanges
                                        ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25 hover:bg-blue-700 focus:ring-blue-500'
                                        : 'cursor-not-allowed bg-gray-300 text-gray-500'
                                )}
                            >
                                {__('Save Changes', 'debug-suite')}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Settings;
