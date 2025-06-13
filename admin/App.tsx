import React from 'react';
import { RouterProvider, createHashRouter } from 'react-router-dom';
import Settings from './components/Settings';
import FileLogs from './components/FileLogs';
import ViewLogs from './components/ViewLogs';
import ManageLogs from './components/ManageLogs';
import { withRouter } from './routing';

const routes = [
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

const App: React.FC = () => {
    const mappedRoutes = routes.map((route) => {
        const WithRouterComponent = withRouter(route.element);

        return {
            path: route.path,
            element: <WithRouterComponent />
        };
    });

    const router = createHashRouter(mappedRoutes);

    return <RouterProvider router={router} />;
};

export default App;
