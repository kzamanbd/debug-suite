import { SlotFillProvider } from '@wordpress/components';
import { createHashRouter, RouterProvider } from 'react-router-dom';
import { ToastProvider } from './components/base/toast';
import ConfirmDialog from './components/confirm-dialog';
import Layout from './components/layout';
import './index.css';
import { withRouter } from './routing';
import type { DebugSuiteRoute } from './routing/routes';
import routes from './routing/routes';

const App = () => {
    // Map the routes to include withRouter for each route element
    const mappedRoutes = routes.map((route: DebugSuiteRoute) => {
        const ResolvedComponent = withRouter(route.element);

        return {
            path: route.path,
            element: (
                <Layout route={route}>
                    <ResolvedComponent />
                </Layout>
            )
        };
    });

    const router = createHashRouter(mappedRoutes);

    return (
        <>
            <SlotFillProvider>
                <ToastProvider>
                    <RouterProvider router={router} />
                </ToastProvider>
            </SlotFillProvider>
            <ConfirmDialog />
        </>
    );
};

export default App;
