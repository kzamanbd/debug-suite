import React from 'react';

const Settings: React.FC = () => {
    return (
        <div className="p-6">
            <h1 className="text-2xl font-bold mb-6">Debug Suite Settings</h1>
            <div className="bg-white rounded-lg shadow-md p-6">
                <h2 className="text-lg font-semibold mb-4">General Settings</h2>
                <div className="space-y-4">
                    <div className="flex items-center">
                        <input 
                            type="checkbox" 
                            id="enable-debug" 
                            className="mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label htmlFor="enable-debug" className="text-sm font-medium text-gray-700">
                            Enable Debug Mode
                        </label>
                    </div>
                    <div className="flex items-center">
                        <input 
                            type="checkbox" 
                            id="log-queries" 
                            className="mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label htmlFor="log-queries" className="text-sm font-medium text-gray-700">
                            Log Database Queries
                        </label>
                    </div>
                    <div className="flex items-center">
                        <input 
                            type="checkbox" 
                            id="log-errors" 
                            className="mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label htmlFor="log-errors" className="text-sm font-medium text-gray-700">
                            Log PHP Errors
                        </label>
                    </div>
                </div>
                <div className="mt-6">
                    <button className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Save Settings
                    </button>
                </div>
            </div>
        </div>
    );
};

export default Settings;
