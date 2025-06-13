import { RouterProvider, createHashRouter } from 'react-router-dom';
import routes from './routing/routes';
import { withRouter } from './routing';

const App = () => {
    // Map the routes to include withRouter for each route element
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
