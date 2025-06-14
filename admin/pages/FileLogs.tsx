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

    const filteredLogs = selectedLevel === 'all' ? logs : logs.filter((log) => log.level === selectedLevel);

    const getLevelColor = (level: string) => {
        switch (level) {
            case 'error':
                return 'text-red-600 bg-red-50';
            case 'warning':
                return 'text-yellow-600 bg-yellow-50';
            case 'info':
                return 'text-blue-600 bg-blue-50';
            case 'debug':
                return 'text-gray-600 bg-gray-50';
            default:
                return 'text-gray-600 bg-gray-50';
        }
    };

    return (
        <div className="wrap">
            <h1>File Logs Overview</h1>
            <p>Welcome to the File Logs section. Choose an option below to get started.</p>

            <div style={{ display: 'flex', gap: '20px', marginBottom: '30px' }}>
                <a
                    href="#/file-logs/view"
                    className="button button-primary button-large"
                    style={{ textDecoration: 'none', padding: '10px 20px' }}
                    onClick={(e) => {
                        e.preventDefault();
                        window.location.hash = '#/file-logs/view';
                    }}
                >
                    📄 View Logs
                </a>
                <a
                    href="#/file-logs/manage"
                    className="button button-secondary button-large"
                    style={{ textDecoration: 'none', padding: '10px 20px' }}
                    onClick={(e) => {
                        e.preventDefault();
                        window.location.hash = '#/file-logs/manage';
                    }}
                >
                    ⚙️ Manage Logs
                </a>
            </div>

            <div className="flex justify-between items-center mb-6">
                <h2>Recent Log Entries</h2>
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
                    <button className="button">Clear Logs</button>
                </div>
            </div>

            <div className="wp-list-table widefat fixed striped" style={{ marginTop: '20px' }}>
                <table className="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style={{ width: '140px' }}>Timestamp</th>
                            <th style={{ width: '80px' }}>Level</th>
                            <th>Message</th>
                            <th style={{ width: '200px' }}>File</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredLogs.map((log) => (
                            <tr key={log.id}>
                                <td>{log.timestamp}</td>
                                <td>
                                    <span
                                        className={`notice inline`}
                                        style={{
                                            padding: '2px 8px',
                                            fontSize: '11px',
                                            backgroundColor:
                                                log.level === 'error'
                                                    ? '#dc3545'
                                                    : log.level === 'warning'
                                                      ? '#ffc107'
                                                      : log.level === 'info'
                                                        ? '#17a2b8'
                                                        : '#6c757d',
                                            color: 'white',
                                            borderRadius: '3px'
                                        }}
                                    >
                                        {log.level.toUpperCase()}
                                    </span>
                                </td>
                                <td>{log.message}</td>
                                <td>
                                    {log.file && (
                                        <span>
                                            <code>{log.file}</code>
                                            {log.line && <span style={{ color: '#666' }}>:{log.line}</span>}
                                        </span>
                                    )}
                                </td>
                            </tr>
                        ))}
                        {filteredLogs.length === 0 && (
                            <tr>
                                <td
                                    colSpan={4}
                                    style={{
                                        textAlign: 'center',
                                        padding: '20px',
                                        color: '#666'
                                    }}
                                >
                                    No logs found for the selected level.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default FileLogs;
