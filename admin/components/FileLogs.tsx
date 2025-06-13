import React, { useState } from 'react';

interface LogEntry {
    id: number;
    timestamp: string;
    level: 'error' | 'warning' | 'info' | 'debug';
    message: string;
    file?: string;
    line?: number;
}

const FileLogs: React.FC = () => {
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

    const filteredLogs = selectedLevel === 'all' 
        ? logs 
        : logs.filter(log => log.level === selectedLevel);

    const getLevelColor = (level: string) => {
        switch (level) {
            case 'error': return 'text-red-600 bg-red-50';
            case 'warning': return 'text-yellow-600 bg-yellow-50';
            case 'info': return 'text-blue-600 bg-blue-50';
            case 'debug': return 'text-gray-600 bg-gray-50';
            default: return 'text-gray-600 bg-gray-50';
        }
    };

    return (
        <div className="p-6">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">File Logs</h1>
                <div className="flex space-x-4">
                    <select 
                        value={selectedLevel} 
                        onChange={(e) => setSelectedLevel(e.target.value)}
                        className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="all">All Levels</option>
                        <option value="error">Error</option>
                        <option value="warning">Warning</option>
                        <option value="info">Info</option>
                        <option value="debug">Debug</option>
                    </select>
                    <button className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        Clear Logs
                    </button>
                </div>
            </div>

            <div className="bg-white rounded-lg shadow-md overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Timestamp
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Level
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Message
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    File
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {filteredLogs.map((log) => (
                                <tr key={log.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {log.timestamp}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getLevelColor(log.level)}`}>
                                            {log.level.toUpperCase()}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-sm text-gray-900">
                                        {log.message}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {log.file && (
                                            <span>
                                                {log.file}
                                                {log.line && <span className="text-gray-400">:{log.line}</span>}
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {filteredLogs.length === 0 && (
                        <div className="text-center py-8 text-gray-500">
                            No logs found for the selected level.
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default FileLogs;
