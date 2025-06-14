import { useState } from '@wordpress/element';

interface SettingsState {
    fileManagerAccess: string;
    publicRootPath: string;
    filesUrl: string;
    defaultViewType: string;
    enableTrash: boolean;
    hideHtaccess: boolean;
    enableDebug: boolean;
    logQueries: boolean;
    logErrors: boolean;
}

const Settings = () => {
    const [settings, setSettings] = useState<SettingsState>({
        fileManagerAccess: 'administrator',
        publicRootPath: '/wp-content/uploads/',
        filesUrl: 'https://example.com/wp-content/uploads/',
        defaultViewType: 'grid',
        enableTrash: true,
        hideHtaccess: true,
        enableDebug: false,
        logQueries: false,
        logErrors: true
    });

    const [hasChanges, setHasChanges] = useState(false);

    const handleInputChange = (field: keyof SettingsState, value: string | boolean) => {
        setSettings((prev) => ({ ...prev, [field]: value }));
        setHasChanges(true);
    };

    const handleSave = () => {
        // Save logic here
        console.log('Saving settings:', settings);
        setHasChanges(false);
    };

    const handleReset = () => {
        // Reset to default values
        setSettings({
            fileManagerAccess: 'administrator',
            publicRootPath: '/wp-content/uploads/',
            filesUrl: 'https://example.com/wp-content/uploads/',
            defaultViewType: 'grid',
            enableTrash: true,
            hideHtaccess: true,
            enableDebug: false,
            logQueries: false,
            logErrors: true
        });
        setHasChanges(false);
    };

    return (
        <div className="min-h-screen bg-gray-50 p-4 sm:p-6">
            <div className="max-w-4xl mx-auto">
                {/* Header */}
                <div className="mb-6 sm:mb-8">
                    <h1 className="text-3xl sm:text-4xl font-bold text-gray-900">Settings</h1>
                    <p className="text-gray-600 mt-2 text-sm sm:text-base">
                        Configure your debug suite and file manager preferences
                    </p>
                </div>

                {/* Settings Form */}
                <div className="space-y-4 sm:space-y-6">
                    {/* File Manager Settings */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div className="border-l-4 border-l-blue-500 px-4 sm:px-6 py-3 sm:py-4 bg-blue-50">
                            <h2 className="text-xl font-semibold text-gray-900 flex items-center">
                                <svg
                                    className="w-6 h-6 mr-3 text-blue-600"
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
                                File Manager Configuration
                            </h2>
                            <p className="text-sm text-blue-700 mt-1">
                                Control access and behavior of the file manager
                            </p>
                        </div>

                        <div className="p-4 sm:p-6 space-y-4 sm:space-y-6">
                            {/* File Manager Access */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                                <div>
                                    <label className="block text-sm font-medium text-gray-900 mb-1">
                                        Who can access File Manager?
                                    </label>
                                    <p className="text-xs text-gray-500">
                                        Select user roles that can access the file manager
                                    </p>
                                </div>
                                <div className="md:col-span-2">
                                    <select
                                        value={settings.fileManagerAccess}
                                        onChange={(e) => handleInputChange('fileManagerAccess', e.target.value)}
                                        className="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    >
                                        <option value="administrator">Administrator Only</option>
                                        <option value="editor">Editor and Above</option>
                                        <option value="author">Author and Above</option>
                                        <option value="contributor">Contributor and Above</option>
                                        <option value="subscriber">All Users</option>
                                    </select>
                                </div>
                            </div>

                            {/* Public Root Path */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                                <div>
                                    <label className="block text-sm font-medium text-gray-900 mb-1">
                                        Public Root Path
                                    </label>
                                    <p className="text-xs text-gray-500">The root directory for file operations</p>
                                </div>
                                <div className="md:col-span-2">
                                    <div className="relative">
                                        <input
                                            type="text"
                                            value={settings.publicRootPath}
                                            onChange={(e) => handleInputChange('publicRootPath', e.target.value)}
                                            className="w-full px-4 py-2 sm:py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                            placeholder="/wp-content/uploads/"
                                        />
                                        <svg
                                            className="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
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
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {/* Files URL */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                                <div>
                                    <label className="block text-sm font-medium text-gray-900 mb-1">Files URL</label>
                                    <p className="text-xs text-gray-500">Base URL for accessing uploaded files</p>
                                </div>
                                <div className="md:col-span-2">
                                    <div className="relative">
                                        <input
                                            type="url"
                                            value={settings.filesUrl}
                                            onChange={(e) => handleInputChange('filesUrl', e.target.value)}
                                            className="w-full px-4 py-2 sm:py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                            placeholder="https://example.com/wp-content/uploads/"
                                        />
                                        <svg
                                            className="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {/* Default View Type */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                                <div>
                                    <label className="block text-sm font-medium text-gray-900 mb-1">
                                        Default View Type
                                    </label>
                                    <p className="text-xs text-gray-500">How files are displayed by default</p>
                                </div>
                                <div className="md:col-span-2">
                                    <div className="flex space-x-4">
                                        <label className="flex items-center cursor-pointer">
                                            <input
                                                type="radio"
                                                name="viewType"
                                                value="grid"
                                                checked={settings.defaultViewType === 'grid'}
                                                onChange={(e) => handleInputChange('defaultViewType', e.target.value)}
                                                className="sr-only"
                                            />
                                            <div
                                                className={`flex items-center px-4 py-2 sm:py-3 rounded-lg border-2 transition-all ${
                                                    settings.defaultViewType === 'grid'
                                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                                        : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400'
                                                }`}
                                            >
                                                <svg
                                                    className="w-5 h-5 mr-2"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        strokeWidth={2}
                                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                                                    />
                                                </svg>
                                                Grid View
                                            </div>
                                        </label>
                                        <label className="flex items-center cursor-pointer">
                                            <input
                                                type="radio"
                                                name="viewType"
                                                value="list"
                                                checked={settings.defaultViewType === 'list'}
                                                onChange={(e) => handleInputChange('defaultViewType', e.target.value)}
                                                className="sr-only"
                                            />
                                            <div
                                                className={`flex items-center px-4 py-2 sm:py-3 rounded-lg border-2 transition-all ${
                                                    settings.defaultViewType === 'list'
                                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                                        : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400'
                                                }`}
                                            >
                                                <svg
                                                    className="w-5 h-5 mr-2"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        strokeWidth={2}
                                                        d="M4 6h16M4 10h16M4 14h16M4 18h16"
                                                    />
                                                </svg>
                                                List View
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {/* Toggle Options */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <div className="space-y-4">
                                    <h4 className="font-medium text-gray-900">File Operations</h4>

                                    <label className="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                        <div>
                                            <span className="text-sm font-medium text-gray-900">Enable Trash</span>
                                            <p className="text-xs text-gray-500">
                                                Move deleted files to trash instead of permanent deletion
                                            </p>
                                        </div>
                                        <div className="relative">
                                            <input
                                                type="checkbox"
                                                checked={settings.enableTrash}
                                                onChange={(e) => handleInputChange('enableTrash', e.target.checked)}
                                                className="sr-only"
                                            />
                                            <div
                                                className={`w-12 h-6 rounded-full transition-colors ${
                                                    settings.enableTrash ? 'bg-blue-600' : 'bg-gray-300'
                                                }`}
                                            >
                                                <div
                                                    className={`w-5 h-5 bg-white rounded-full shadow transform transition-transform ${
                                                        settings.enableTrash ? 'translate-x-6' : 'translate-x-0.5'
                                                    } mt-0.5`}
                                                />
                                            </div>
                                        </div>
                                    </label>

                                    <label className="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                        <div>
                                            <span className="text-sm font-medium text-gray-900">
                                                Hide .htaccess Files
                                            </span>
                                            <p className="text-xs text-gray-500">
                                                Hide system files from the file manager
                                            </p>
                                        </div>
                                        <div className="relative">
                                            <input
                                                type="checkbox"
                                                checked={settings.hideHtaccess}
                                                onChange={(e) => handleInputChange('hideHtaccess', e.target.checked)}
                                                className="sr-only"
                                            />
                                            <div
                                                className={`w-12 h-6 rounded-full transition-colors ${
                                                    settings.hideHtaccess ? 'bg-blue-600' : 'bg-gray-300'
                                                }`}
                                            >
                                                <div
                                                    className={`w-5 h-5 bg-white rounded-full shadow transform transition-transform ${
                                                        settings.hideHtaccess ? 'translate-x-6' : 'translate-x-0.5'
                                                    } mt-0.5`}
                                                />
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Debug Settings */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div className="border-l-4 border-l-green-500 px-4 sm:px-6 py-3 sm:py-4 bg-green-50">
                            <h2 className="text-xl font-semibold text-gray-900 flex items-center">
                                <svg
                                    className="w-6 h-6 mr-3 text-green-600"
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
                                Debug Configuration
                            </h2>
                            <p className="text-sm text-green-700 mt-1">Configure debugging and logging options</p>
                        </div>

                        <div className="p-4 sm:p-6 space-y-4">
                            <label className="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                <div>
                                    <span className="text-sm font-medium text-gray-900">Enable Debug Mode</span>
                                    <p className="text-xs text-gray-500">Enable WordPress debug mode</p>
                                </div>
                                <div className="relative">
                                    <input
                                        type="checkbox"
                                        checked={settings.enableDebug}
                                        onChange={(e) => handleInputChange('enableDebug', e.target.checked)}
                                        className="sr-only"
                                    />
                                    <div
                                        className={`w-12 h-6 rounded-full transition-colors ${
                                            settings.enableDebug ? 'bg-green-600' : 'bg-gray-300'
                                        }`}
                                    >
                                        <div
                                            className={`w-5 h-5 bg-white rounded-full shadow transform transition-transform ${
                                                settings.enableDebug ? 'translate-x-6' : 'translate-x-0.5'
                                            } mt-0.5`}
                                        />
                                    </div>
                                </div>
                            </label>

                            <label className="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                <div>
                                    <span className="text-sm font-medium text-gray-900">Log Database Queries</span>
                                    <p className="text-xs text-gray-500">Record all database queries for analysis</p>
                                </div>
                                <div className="relative">
                                    <input
                                        type="checkbox"
                                        checked={settings.logQueries}
                                        onChange={(e) => handleInputChange('logQueries', e.target.checked)}
                                        className="sr-only"
                                    />
                                    <div
                                        className={`w-12 h-6 rounded-full transition-colors ${
                                            settings.logQueries ? 'bg-green-600' : 'bg-gray-300'
                                        }`}
                                    >
                                        <div
                                            className={`w-5 h-5 bg-white rounded-full shadow transform transition-transform ${
                                                settings.logQueries ? 'translate-x-6' : 'translate-x-0.5'
                                            } mt-0.5`}
                                        />
                                    </div>
                                </div>
                            </label>

                            <label className="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                <div>
                                    <span className="text-sm font-medium text-gray-900">Log PHP Errors</span>
                                    <p className="text-xs text-gray-500">Capture and log PHP errors and warnings</p>
                                </div>
                                <div className="relative">
                                    <input
                                        type="checkbox"
                                        checked={settings.logErrors}
                                        onChange={(e) => handleInputChange('logErrors', e.target.checked)}
                                        className="sr-only"
                                    />
                                    <div
                                        className={`w-12 h-6 rounded-full transition-colors ${
                                            settings.logErrors ? 'bg-green-600' : 'bg-gray-300'
                                        }`}
                                    >
                                        <div
                                            className={`w-5 h-5 bg-white rounded-full shadow transform transition-transform ${
                                                settings.logErrors ? 'translate-x-6' : 'translate-x-0.5'
                                            } mt-0.5`}
                                        />
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-3 sm:space-y-0">
                            <div>
                                {hasChanges && (
                                    <p className="text-sm text-amber-600 flex items-center">
                                        <svg
                                            className="w-4 h-4 mr-2"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                                            />
                                        </svg>
                                        You have unsaved changes
                                    </p>
                                )}
                            </div>
                            <div className="flex space-x-3">
                                <button
                                    onClick={handleReset}
                                    className="px-4 py-2 sm:px-6 sm:py-3 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors font-medium"
                                >
                                    Reset to Defaults
                                </button>
                                <button
                                    onClick={handleSave}
                                    disabled={!hasChanges}
                                    className={`px-4 py-2 sm:px-8 sm:py-3 rounded-lg font-medium transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 ${
                                        hasChanges
                                            ? 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 shadow-lg shadow-blue-500/25'
                                            : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                    }`}
                                >
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Settings;
