import { RouterProvider, createHashRouter } from 'react-router-dom';
import { Slide, ToastContainer } from 'react-toastify';
import { withRouter } from './routing';
import routes from './routing/routes';

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

    return (
        <>
            <RouterProvider router={router} />
            <ToastContainer
                position="bottom-center"
                autoClose={5000}
                hideProgressBar
                newestOnTop={false}
                closeOnClick={false}
                rtl={false}
                pauseOnFocusLoss
                draggable
                pauseOnHover
                theme="light"
                transition={Slide}
            />
        </>
    );
};

export default App;
