import { RouterProvider, createHashRouter } from 'react-router-dom';
import Settings from './components/Settings';
import FileLogs from './components/FileLogs';
import { withRouter } from './routing';

const routes = [
    { id: 'settings', path: '/', element: <Settings /> },
    {
        id: 'file-logs',
        path: '/file-logs',
        element: <FileLogs />
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

    const router = createHashRouter(mappedRoutes, {
    });
    return <RouterProvider router={router} />;
};

export default App;
