import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom';

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
            <p className="mb-6 text-gray-600">
                {__("Manage your application's log files - clear, download, or archive them.", 'debug-suite')}
            </p>

            <div className="flex justify-between">
                <div className="flex flex-wrap gap-3">
                    <button
                        className="rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        onClick={handleClearLogs}
                        disabled={isProcessing || selectedFiles.length === 0}
                    >
                        {isProcessing ? __('Processing...', 'debug-suite') : __('Clear Selected', 'debug-suite')}
                    </button>
                    <button
                        className="rounded-md bg-gray-200 px-4 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-300 disabled:cursor-not-allowed disabled:opacity-50"
                        onClick={handleDownloadLogs}
                        disabled={isProcessing || selectedFiles.length === 0}
                    >
                        {__('Download Selected', 'debug-suite')}
                    </button>
                    <button
                        className="rounded-md bg-gray-200 px-4 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-300 disabled:cursor-not-allowed disabled:opacity-50"
                        onClick={handleArchiveLogs}
                        disabled={isProcessing || selectedFiles.length === 0}
                    >
                        {__('Archive Selected', 'debug-suite')}
                    </button>
                </div>
                <div className="flex gap-3">
                    <Link
                        to="/"
                        className="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 font-medium text-gray-800 transition-colors hover:bg-gray-200"
                    >
                        {'\u2190'} {__('Back to Overview', 'debug-suite')}
                    </Link>
                    <Link
                        to="/file-logs"
                        className="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-4 py-2 font-medium text-blue-700 transition-colors hover:bg-blue-100"
                    >
                        {'\ud83d\udcc4'} {__('View Logs', 'debug-suite')}
                    </Link>
                </div>
            </div>
            <p className="mb-6 text-sm text-gray-500">
                {__('Selected:', 'debug-suite')} {selectedFiles.length} {__('of', 'debug-suite')} {logFiles.length}{' '}
                {__('files', 'debug-suite')}
            </p>

            <div className="mb-8 overflow-x-auto rounded-xl border border-gray-200 bg-white shadow">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-4 py-3">
                                <input
                                    type="checkbox"
                                    checked={selectedFiles.length === logFiles.length && logFiles.length > 0}
                                    onChange={(e) => handleSelectAll(e.target.checked)}
                                    className="h-4 w-4 rounded border-gray-300 accent-blue-600 focus:ring-2 focus:ring-blue-500"
                                />
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                                {__('Log File', 'debug-suite')}
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                                {__('Path', 'debug-suite')}
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                                {__('Size', 'debug-suite')}
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                                {__('Last Modified', 'debug-suite')}
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                                {__('Entries', 'debug-suite')}
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                                {__('Actions', 'debug-suite')}
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {logFiles.map((file) => (
                            <tr key={file.id} className="transition-colors hover:bg-gray-50">
                                <td className="px-4 py-2 text-center">
                                    <input
                                        type="checkbox"
                                        checked={selectedFiles.indexOf(file.id) !== -1}
                                        onChange={(e) => handleSelectFile(file.id, e.target.checked)}
                                        className="h-4 w-4 rounded border-gray-300 accent-blue-600 focus:ring-2 focus:ring-blue-500"
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
                                            className="rounded bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200"
                                            onClick={() => alert(`Viewing ${file.name}...`)}
                                        >
                                            {__('View', 'debug-suite')}
                                        </button>
                                        <button
                                            className="rounded bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200"
                                            onClick={() => alert(`Downloading ${file.name}...`)}
                                        >
                                            {__('Download', 'debug-suite')}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="rounded-xl border border-gray-200 bg-gray-50 p-6">
                <h3 className="mb-4 text-lg font-semibold text-gray-900">
                    {__('Log Management Settings', 'debug-suite')}
                </h3>
                <form>
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label className="mb-2 flex items-center gap-2 text-sm font-medium text-gray-900">
                                <input
                                    type="checkbox"
                                    className="h-4 w-4 rounded border-gray-300 accent-blue-600 focus:ring-2 focus:ring-blue-500"
                                />
                                {__('Archive logs older than', 'debug-suite')}
                                <select className="ml-2 rounded-md border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500">
                                    <option value="7">{__('7 days', 'debug-suite')}</option>
                                    <option value="30">{__('30 days', 'debug-suite')}</option>
                                    <option value="90">{__('90 days', 'debug-suite')}</option>
                                </select>
                            </label>
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-900">
                                {__('Max Log File Size', 'debug-suite')}
                            </label>
                            <div className="flex items-center gap-2">
                                <input
                                    type="number"
                                    value="10"
                                    className="w-20 rounded-md border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500"
                                />
                                <span className="text-gray-700">{__('MB', 'debug-suite')}</span>
                            </div>
                            <p className="mt-1 text-xs text-gray-500">
                                {__('Automatically rotate logs when they exceed this size.', 'debug-suite')}
                            </p>
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-900">
                                {__('Log Retention', 'debug-suite')}
                            </label>
                            <select className="rounded-md border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="30">{__('30 days', 'debug-suite')}</option>
                                <option value="60">{__('60 days', 'debug-suite')}</option>
                                <option value="90">{__('90 days', 'debug-suite')}</option>
                                <option value="180">{__('180 days', 'debug-suite')}</option>
                                <option value="365">{__('1 year', 'debug-suite')}</option>
                            </select>
                            <p className="mt-1 text-xs text-gray-500">
                                {__('How long to keep log files before permanent deletion.', 'debug-suite')}
                            </p>
                        </div>
                    </div>
                    <div className="mt-6">
                        <button
                            type="submit"
                            className="rounded-lg bg-blue-600 px-6 py-2 font-medium text-white transition-colors hover:bg-blue-700"
                        >
                            {__('Save Settings', 'debug-suite')}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
};

export default ManageLogs;
