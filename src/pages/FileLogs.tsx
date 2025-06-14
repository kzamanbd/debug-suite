import { useState } from '@wordpress/element';
import { Link } from 'react-router-dom';

interface LogEntry {
    id: number;
    timestamp: string;
    level: 'error' | 'warning' | 'info' | 'debug';
    message: string;
    file?: string;
    line?: number;
}

const levelColors: Record<string, string> = {
    error: 'bg-red-100 text-red-700',
    warning: 'bg-yellow-100 text-yellow-800',
    info: 'bg-blue-100 text-blue-700',
    debug: 'bg-gray-100 text-gray-700'
};

const FileLogs = () => {
    const [selectedLevel, setSelectedLevel] = useState<string>('all');

    // Sample log data - in a real app, this would come from your backend
    const [logs] = useState<LogEntry[]>([
        {
            id: 1,
            timestamp: '2025-06-14 10:30:15',
            level: 'error',
            message: 'Database connection failed',
            file: 'wp-config.php',
            line: 45
        },
        {
            id: 2,
            timestamp: '2025-06-14 10:25:32',
            level: 'warning',
            message: 'Plugin compatibility issue detected',
            file: 'debug-suite.php',
            line: 120
        },
        {
            id: 3,
            timestamp: '2025-06-14 10:20:18',
            level: 'info',
            message: 'Debug suite activated successfully',
            file: 'includes/Core/Activator.php',
            line: 25
        },
        {
            id: 4,
            timestamp: '2025-06-14 10:15:45',
            level: 'debug',
            message: 'Query execution time: 0.045s',
            file: 'includes/Providers/AbstractDebugProvider.php',
            line: 78
        }
    ]);

    const filteredLogs = selectedLevel === 'all' ? logs : logs.filter((log) => log.level === selectedLevel);

    return (
        <>
            <h1 className="text-2xl font-bold text-gray-900 mb-1">File Logs Overview</h1>
            <p className="text-gray-600 mb-6">
                Welcome to the File Logs section. Choose an option below to get started.
            </p>

            <div className="flex flex-wrap gap-4 mb-8">
                <Link
                    to="/file-logs"
                    className="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-blue-600 text-white font-medium shadow hover:bg-blue-700 transition-colors"
                >
                    <span role="img" aria-label="View">
                        📄
                    </span>{' '}
                    View Logs
                </Link>
                <Link
                    to="/file-logs/manage"
                    className="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gray-100 text-gray-800 font-medium shadow hover:bg-gray-200 transition-colors"
                >
                    <span role="img" aria-label="Manage">
                        ⚙️
                    </span>{' '}
                    Manage Logs
                </Link>
            </div>

            <div className="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                <h2 className="text-lg font-semibold text-gray-900">Recent Log Entries</h2>
                <div className="flex gap-3 items-center">
                    <select
                        value={selectedLevel}
                        onChange={(e) => setSelectedLevel(e.target.value)}
                        className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-sm"
                    >
                        <option value="all">All Levels</option>
                        <option value="error">Error</option>
                        <option value="warning">Warning</option>
                        <option value="info">Info</option>
                        <option value="debug">Debug</option>
                    </select>
                    <button className="px-4 py-2 rounded-md bg-gray-200 text-gray-700 font-medium hover:bg-gray-300 transition-colors text-sm">
                        Clear Logs
                    </button>
                </div>
            </div>

            <div className="overflow-x-auto bg-white rounded-xl shadow border border-gray-200">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Timestamp</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Level</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Message</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">File</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {filteredLogs.length === 0 ? (
                            <tr>
                                <td colSpan={4} className="text-center py-8 text-gray-400 text-sm">
                                    No logs found for the selected level.
                                </td>
                            </tr>
                        ) : (
                            filteredLogs.map((log) => (
                                <tr key={log.id} className="hover:bg-gray-50 transition-colors">
                                    <td className="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                        {log.timestamp}
                                    </td>
                                    <td className="px-4 py-2 whitespace-nowrap">
                                        <span
                                            className={`inline-block px-2 py-0.5 rounded text-xs font-semibold ${levelColors[log.level]}`}
                                        >
                                            {log.level.toUpperCase()}
                                        </span>
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-800">{log.message}</td>
                                    <td className="px-4 py-2 text-sm text-gray-600">
                                        {log.file && (
                                            <span>
                                                <code className="bg-gray-100 px-1 rounded text-xs">{log.file}</code>
                                                {log.line && <span className="text-gray-400 ml-1">:{log.line}</span>}
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </>
    );
};

export default FileLogs;
