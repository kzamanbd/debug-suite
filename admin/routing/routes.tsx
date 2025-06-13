import Settings from '../components/Settings';
import FileLogs from '../components/FileLogs';
import ViewLogs from '../components/ViewLogs';
import ManageLogs from '../components/ManageLogs';

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
