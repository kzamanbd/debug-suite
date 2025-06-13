import Settings from '../pages/Settings';
import FileLogs from '../pages/FileLogs';
import ViewLogs from '../pages/ViewLogs';
import ManageLogs from '../pages/ManageLogs';

export default [
    { id: 'settings', path: '/', element: <Settings /> },
    {
        id: 'file-logs',
        path: '/file-logs',
        element: <FileLogs />
    },
    {
        id: 'file-logs-view',
        path: '/file-logs/view',
        element: <ViewLogs />
    },
    {
        id: 'file-logs-manage',
        path: '/file-logs/manage',
        element: <ManageLogs />
    }
];
