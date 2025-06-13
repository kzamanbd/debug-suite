import React, { useState } from 'react';

interface LogFile {
    id: number;
    name: string;
    path: string;
    size: string;
    lastModified: string;
    entries: number;
}

const ManageLogs: React.FC = () => {
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
            setSelectedFiles(selectedFiles.filter(id => id !== fileId));
        }
    };

    const handleSelectAll = (checked: boolean) => {
        if (checked) {
            setSelectedFiles(logFiles.map(file => file.id));
        } else {
            setSelectedFiles([]);
        }
    };

    const handleClearLogs = async () => {
        if (selectedFiles.length === 0) {
            alert('Please select at least one log file to clear.');
            return;
        }

        if (!confirm(`Are you sure you want to clear ${selectedFiles.length} log file(s)? This action cannot be undone.`)) {
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
        <div className="wrap">
            <h1>Manage Log Files</h1>
            <p>Manage your application's log files - clear, download, or archive them.</p>
            
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
                    href="#/file-logs/view" 
                    className="button button-secondary"
                    onClick={(e) => {
                        e.preventDefault();
                        window.location.hash = '#/file-logs/view';
                    }}
                >
                    📄 View Logs
                </a>
            </div>
            
            <div style={{ marginBottom: '20px' }}>
                <div style={{ display: 'flex', gap: '10px', marginBottom: '15px' }}>
                    <button 
                        className="button button-primary"
                        onClick={handleClearLogs}
                        disabled={isProcessing || selectedFiles.length === 0}
                    >
                        {isProcessing ? 'Processing...' : 'Clear Selected'}
                    </button>
                    <button 
                        className="button"
                        onClick={handleDownloadLogs}
                        disabled={isProcessing || selectedFiles.length === 0}
                    >
                        Download Selected
                    </button>
                    <button 
                        className="button"
                        onClick={handleArchiveLogs}
                        disabled={isProcessing || selectedFiles.length === 0}
                    >
                        Archive Selected
                    </button>
                </div>
                
                <p style={{ fontSize: '13px', color: '#666' }}>
                    Selected: {selectedFiles.length} of {logFiles.length} files
                </p>
            </div>

            <div className="wp-list-table widefat fixed striped">
                <table className="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td className="manage-column column-cb check-column">
                                <input
                                    type="checkbox"
                                    checked={selectedFiles.length === logFiles.length && logFiles.length > 0}
                                    onChange={(e) => handleSelectAll(e.target.checked)}
                                />
                            </td>
                            <th>Log File</th>
                            <th>Path</th>
                            <th style={{ width: '100px' }}>Size</th>
                            <th style={{ width: '140px' }}>Last Modified</th>
                            <th style={{ width: '80px' }}>Entries</th>
                            <th style={{ width: '120px' }}>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {logFiles.map((file) => (
                            <tr key={file.id}>
                                <th className="check-column">
                                    <input
                                        type="checkbox"
                                        checked={selectedFiles.indexOf(file.id) !== -1}
                                        onChange={(e) => handleSelectFile(file.id, e.target.checked)}
                                    />
                                </th>
                                <td><strong>{file.name}</strong></td>
                                <td><code>{file.path}</code></td>
                                <td>{file.size}</td>
                                <td>{file.lastModified}</td>
                                <td>{file.entries.toLocaleString()}</td>
                                <td>
                                    <div style={{ display: 'flex', gap: '5px' }}>
                                        <button 
                                            className="button button-small"
                                            onClick={() => alert(`Viewing ${file.name}...`)}
                                        >
                                            View
                                        </button>
                                        <button 
                                            className="button button-small"
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

            <div style={{ marginTop: '20px', padding: '15px', backgroundColor: '#f9f9f9', border: '1px solid #ddd' }}>
                <h3>Log Management Settings</h3>
                <form>
                    <table className="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">Auto-Archive Logs</th>
                                <td>
                                    <fieldset>
                                        <legend className="screen-reader-text">Auto-Archive Options</legend>
                                        <label>
                                            <input type="checkbox" /> Archive logs older than 
                                            <select style={{ margin: '0 5px' }}>
                                                <option value="7">7 days</option>
                                                <option value="30">30 days</option>
                                                <option value="90">90 days</option>
                                            </select>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Max Log File Size</th>
                                <td>
                                    <input type="number" value="10" style={{ width: '80px' }} /> MB
                                    <p className="description">Automatically rotate logs when they exceed this size.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Log Retention</th>
                                <td>
                                    <select>
                                        <option value="30">30 days</option>
                                        <option value="60">60 days</option>
                                        <option value="90">90 days</option>
                                        <option value="180">180 days</option>
                                        <option value="365">1 year</option>
                                    </select>
                                    <p className="description">How long to keep log files before permanent deletion.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p className="submit">
                        <button type="submit" className="button button-primary">Save Settings</button>
                    </p>
                </form>
            </div>
        </div>
    );
};

export default ManageLogs;
