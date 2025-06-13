import React, { useState } from 'react';

interface LogEntry {
    id: number;
    timestamp: string;
    level: 'error' | 'warning' | 'info' | 'debug';
    message: string;
    file?: string;
    line?: number;
}

const ViewLogs: React.FC = () => {
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

    const filteredLogs = logs.filter(log => {
        const matchesLevel = selectedLevel === 'all' || log.level === selectedLevel;
        const matchesSearch = searchTerm === '' || 
            log.message.toLowerCase().includes(searchTerm.toLowerCase()) ||
            log.file?.toLowerCase().includes(searchTerm.toLowerCase());
        return matchesLevel && matchesSearch;
    });

    const getLevelBadgeClass = (level: string) => {
        switch (level) {
            case 'error':
                return 'notice notice-error inline';
            case 'warning':
                return 'notice notice-warning inline';
            case 'info':
                return 'notice notice-info inline';
            case 'debug':
                return 'notice notice-success inline';
            default:
                return 'notice inline';
        }
    };

    return (
        <div className="wrap">
            <h1>View File Logs</h1>
            <p>View and search through your application's log files.</p>
            
            <div style={{ marginBottom: '20px' }}>
                <a 
                    href="#/file-logs" 
                    className="button" 
                    style={{ marginRight: '10px' }}
                    onClick={(e) => {
                        e.preventDefault();
                        window.location.hash = '#/file-logs';
                    }}
                >
                    ← Back to Overview
                </a>
                <a 
                    href="#/file-logs/manage" 
                    className="button button-secondary"
                    onClick={(e) => {
                        e.preventDefault();
                        window.location.hash = '#/file-logs/manage';
                    }}
                >
                    ⚙️ Manage Logs
                </a>
            </div>
            
            <div style={{ display: 'flex', gap: '15px', marginBottom: '20px', alignItems: 'center' }}>
                <div>
                    <label htmlFor="log-level-filter" style={{ marginRight: '8px' }}>Filter by Level:</label>
                    <select 
                        id="log-level-filter"
                        value={selectedLevel} 
                        onChange={(e) => setSelectedLevel(e.target.value)}
                        style={{ padding: '5px' }}
                    >
                        <option value="all">All Levels</option>
                        <option value="error">Error</option>
                        <option value="warning">Warning</option>
                        <option value="info">Info</option>
                        <option value="debug">Debug</option>
                    </select>
                </div>
                
                <div>
                    <label htmlFor="search-logs" style={{ marginRight: '8px' }}>Search:</label>
                    <input
                        id="search-logs"
                        type="text"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        placeholder="Search logs..."
                        style={{ padding: '5px', width: '200px' }}
                    />
                </div>
            </div>

            <div className="wp-list-table widefat fixed striped">
                <table className="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style={{ width: '140px' }}>Timestamp</th>
                            <th style={{ width: '80px' }}>Level</th>
                            <th>Message</th>
                            <th style={{ width: '200px' }}>File</th>
                            <th style={{ width: '60px' }}>Line</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredLogs.length === 0 ? (
                            <tr>
                                <td colSpan={5} style={{ textAlign: 'center', padding: '20px' }}>
                                    No logs found matching your criteria.
                                </td>
                            </tr>
                        ) : (
                            filteredLogs.map((log) => (
                                <tr key={log.id}>
                                    <td>{log.timestamp}</td>
                                    <td>
                                        <span className={getLevelBadgeClass(log.level)} style={{ padding: '2px 8px', fontSize: '11px' }}>
                                            {log.level.toUpperCase()}
                                        </span>
                                    </td>
                                    <td>{log.message}</td>
                                    <td>{log.file || '-'}</td>
                                    <td>{log.line || '-'}</td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            <div style={{ marginTop: '20px' }}>
                <p><strong>Total logs:</strong> {filteredLogs.length} of {logs.length}</p>
            </div>
        </div>
    );
};

export default ViewLogs;
