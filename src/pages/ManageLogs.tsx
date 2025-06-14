import { useState } from '@wordpress/element';

interface LogFile {
    id: number;
    name: string;
    path: string;
    size: string;
    lastModified: string;
    entries: number;
}

const ManageLogs = () => {
    const [logFiles] = useState<LogFile[]>([
        {
            id: 1,
            name: 'debug.log',
            path: '/wp-content/debug.log',
            size: '2.3 MB',
            lastModified: '2025-06-14 10:30:15',
            entries: 1547
        },
        {
            id: 2,
            name: 'error.log',
            path: '/wp-content/plugins/debug-suite/logs/error.log',
            size: '856 KB',
            lastModified: '2025-06-14 09:45:22',
            entries: 423
        },
        {
            id: 3,
            name: 'query.log',
            path: '/wp-content/plugins/debug-suite/logs/query.log',
            size: '4.1 MB',
            lastModified: '2025-06-14 10:28:01',
            entries: 2891
        }
    ]);

    const [selectedFiles, setSelectedFiles] = useState<number[]>([]);
    const [isProcessing, setIsProcessing] = useState(false);

    const handleSelectFile = (fileId: number, checked: boolean) => {
        if (checked) {
            setSelectedFiles([...selectedFiles, fileId]);
        } else {
            setSelectedFiles(selectedFiles.filter((id) => id !== fileId));
        }
    };

    const handleSelectAll = (checked: boolean) => {
        if (checked) {
            setSelectedFiles(logFiles.map((file) => file.id));
        } else {
            setSelectedFiles([]);
        }
    };

    const handleClearLogs = async () => {
        if (selectedFiles.length === 0) {
            alert('Please select at least one log file to clear.');
            return;
        }

        if (
            !confirm(
                `Are you sure you want to clear ${selectedFiles.length} log file(s)? This action cannot be undone.`
            )
        ) {
            return;
        }

        setIsProcessing(true);
        // Simulate API call
        setTimeout(() => {
            alert(`Successfully cleared ${selectedFiles.length} log file(s).`);
            setSelectedFiles([]);
            setIsProcessing(false);
        }, 1500);
    };

    const handleDownloadLogs = async () => {
        if (selectedFiles.length === 0) {
            alert('Please select at least one log file to download.');
            return;
        }

        setIsProcessing(true);
        // Simulate download
        setTimeout(() => {
            alert(`Preparing download for ${selectedFiles.length} log file(s)...`);
            setIsProcessing(false);
        }, 1000);
    };

    const handleArchiveLogs = async () => {
        if (selectedFiles.length === 0) {
            alert('Please select at least one log file to archive.');
            return;
        }

        setIsProcessing(true);
        // Simulate archiving
        setTimeout(() => {
            alert(`Successfully archived ${selectedFiles.length} log file(s).`);
            setSelectedFiles([]);
            setIsProcessing(false);
        }, 2000);
    };

    return (
        <>
            <h1 className="text-2xl font-bold text-gray-900 mb-1">Manage Log Files</h1>
            <p className="text-gray-600 mb-6">
                Manage your application's log files - clear, download, or archive them.
            </p>

            <div className="flex gap-3 mb-6">
                <a
                    href="#/file-logs"
                    className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-100 text-gray-800 font-medium hover:bg-gray-200 transition-colors"
                    onClick={(e) => {
                        e.preventDefault();
                        window.location.hash = '#/file-logs';
                    }}
                >
                    ← Back to Overview
                </a>
                <a
                    href="#/file-logs"
                    className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-50 text-blue-700 font-medium hover:bg-blue-100 transition-colors"
                    onClick={(e) => {
                        e.preventDefault();
                        window.location.hash = '#/file-logs';
                    }}
                >
                    📄 View Logs
                </a>
            </div>

            <div className="flex flex-wrap gap-3 mb-4">
                <button
                    className="px-4 py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    onClick={handleClearLogs}
                    disabled={isProcessing || selectedFiles.length === 0}
                >
                    {isProcessing ? 'Processing...' : 'Clear Selected'}
                </button>
                <button
                    className="px-4 py-2 rounded-md bg-gray-200 text-gray-700 font-medium hover:bg-gray-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    onClick={handleDownloadLogs}
                    disabled={isProcessing || selectedFiles.length === 0}
                >
                    Download Selected
                </button>
                <button
                    className="px-4 py-2 rounded-md bg-gray-200 text-gray-700 font-medium hover:bg-gray-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    onClick={handleArchiveLogs}
                    disabled={isProcessing || selectedFiles.length === 0}
                >
                    Archive Selected
                </button>
            </div>
            <p className="text-sm text-gray-500 mb-6">
                Selected: {selectedFiles.length} of {logFiles.length} files
            </p>

            <div className="overflow-x-auto bg-white rounded-xl shadow border border-gray-200 mb-8">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-4 py-3">
                                <input
                                    type="checkbox"
                                    checked={selectedFiles.length === logFiles.length && logFiles.length > 0}
                                    onChange={(e) => handleSelectAll(e.target.checked)}
                                    className="accent-blue-600 w-4 h-4 rounded border-gray-300 focus:ring-2 focus:ring-blue-500"
                                />
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Log File</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Path</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Size</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Last Modified</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Entries</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {logFiles.map((file) => (
                            <tr key={file.id} className="hover:bg-gray-50 transition-colors">
                                <td className="px-4 py-2 text-center">
                                    <input
                                        type="checkbox"
                                        checked={selectedFiles.indexOf(file.id) !== -1}
                                        onChange={(e) => handleSelectFile(file.id, e.target.checked)}
                                        className="accent-blue-600 w-4 h-4 rounded border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    />
                                </td>
                                <td className="px-4 py-2 font-semibold text-gray-900">{file.name}</td>
                                <td className="px-4 py-2 text-xs text-gray-600">
                                    <code>{file.path}</code>
                                </td>
                                <td className="px-4 py-2 text-sm text-gray-700">{file.size}</td>
                                <td className="px-4 py-2 text-sm text-gray-700">{file.lastModified}</td>
                                <td className="px-4 py-2 text-sm text-gray-700">{file.entries.toLocaleString()}</td>
                                <td className="px-4 py-2">
                                    <div className="flex gap-2">
                                        <button
                                            className="px-3 py-1 rounded bg-gray-100 text-gray-700 text-xs font-medium hover:bg-gray-200 transition-colors"
                                            onClick={() => alert(`Viewing ${file.name}...`)}
                                        >
                                            View
                                        </button>
                                        <button
                                            className="px-3 py-1 rounded bg-gray-100 text-gray-700 text-xs font-medium hover:bg-gray-200 transition-colors"
                                            onClick={() => alert(`Downloading ${file.name}...`)}
                                        >
                                            Download
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="bg-gray-50 rounded-xl border border-gray-200 p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Log Management Settings</h3>
                <form>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label className="flex items-center gap-2 text-sm font-medium text-gray-900 mb-2">
                                <input
                                    type="checkbox"
                                    className="accent-blue-600 w-4 h-4 rounded border-gray-300 focus:ring-2 focus:ring-blue-500"
                                />
                                Archive logs older than
                                <select className="ml-2 px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm">
                                    <option value="7">7 days</option>
                                    <option value="30">30 days</option>
                                    <option value="90">90 days</option>
                                </select>
                            </label>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-1">Max Log File Size</label>
                            <div className="flex items-center gap-2">
                                <input
                                    type="number"
                                    value="10"
                                    className="w-20 px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm"
                                />
                                <span className="text-gray-700">MB</span>
                            </div>
                            <p className="text-xs text-gray-500 mt-1">
                                Automatically rotate logs when they exceed this size.
                            </p>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-1">Log Retention</label>
                            <select className="px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm">
                                <option value="30">30 days</option>
                                <option value="60">60 days</option>
                                <option value="90">90 days</option>
                                <option value="180">180 days</option>
                                <option value="365">1 year</option>
                            </select>
                            <p className="text-xs text-gray-500 mt-1">
                                How long to keep log files before permanent deletion.
                            </p>
                        </div>
                    </div>
                    <div className="mt-6">
                        <button
                            type="submit"
                            className="px-6 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors"
                        >
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
};

export default ManageLogs;
