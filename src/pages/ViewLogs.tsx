import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

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

const ViewLogs = () => {
    const [selectedLevel, setSelectedLevel] = useState<string>('all');
    const [searchTerm, setSearchTerm] = useState<string>('');

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

    const filteredLogs = logs.filter((log) => {
        const matchesLevel = selectedLevel === 'all' || log.level === selectedLevel;
        const matchesSearch =
            searchTerm === '' ||
            log.message.toLowerCase().includes(searchTerm.toLowerCase()) ||
            log.file?.toLowerCase().includes(searchTerm.toLowerCase());
        return matchesLevel && matchesSearch;
    });

    return (
        <>
            <h1 className="text-2xl font-bold text-gray-900 mb-1">{__('View File Logs', 'debug-suite')}</h1>
            <p className="text-gray-600 mb-6">
                {__("View and search through your application's log files.", 'debug-suite')}
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
                    {'\u2190'} {__('Back to Overview', 'debug-suite')}
                </a>
                <a
                    href="#/file-logs/manage"
                    className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-50 text-blue-700 font-medium hover:bg-blue-100 transition-colors"
                    onClick={(e) => {
                        e.preventDefault();
                        window.location.hash = '#/file-logs/manage';
                    }}
                >
                    {'\u2699\ufe0f'} {__('Manage Logs', 'debug-suite')}
                </a>
            </div>

            <div className="flex flex-col sm:flex-row sm:items-center gap-4 mb-6">
                <div className="flex items-center gap-2">
                    <label htmlFor="log-level-filter" className="text-sm font-medium text-gray-700">
                        {__('Filter by Level:', 'debug-suite')}
                    </label>
                    <select
                        id="log-level-filter"
                        value={selectedLevel}
                        onChange={(e) => setSelectedLevel(e.target.value)}
                        className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-sm"
                    >
                        <option value="all">{__('All Levels', 'debug-suite')}</option>
                        <option value="error">{__('Error', 'debug-suite')}</option>
                        <option value="warning">{__('Warning', 'debug-suite')}</option>
                        <option value="info">{__('Info', 'debug-suite')}</option>
                        <option value="debug">{__('Debug', 'debug-suite')}</option>
                    </select>
                </div>
                <div className="flex items-center gap-2">
                    <label htmlFor="search-logs" className="text-sm font-medium text-gray-700">
                        {__('Search:', 'debug-suite')}
                    </label>
                    <input
                        id="search-logs"
                        type="text"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        placeholder={__('Search logs...', 'debug-suite')}
                        className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm w-56"
                    />
                </div>
            </div>

            <div className="overflow-x-auto bg-white rounded-xl shadow border border-gray-200">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                                {__('Timestamp', 'debug-suite')}
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                                {__('Level', 'debug-suite')}
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                                {__('Message', 'debug-suite')}
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                                {__('File', 'debug-suite')}
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                                {__('Line', 'debug-suite')}
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {filteredLogs.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="text-center py-8 text-gray-400 text-sm">
                                    {__('No logs found matching your criteria.', 'debug-suite')}
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
                                    <td className="px-4 py-2 text-sm text-gray-600">{log.file || '-'}</td>
                                    <td className="px-4 py-2 text-sm text-gray-600">{log.line || '-'}</td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            <div className="mt-6">
                <p className="text-sm text-gray-700">
                    <strong>{__('Total logs:', 'debug-suite')}</strong> {filteredLogs.length} {__('of', 'debug-suite')}{' '}
                    {logs.length}
                </p>
            </div>
        </>
    );
};

export default ViewLogs;
