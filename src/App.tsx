import { SlotFillProvider } from '@wordpress/components';
import { createHashRouter, RouterProvider } from 'react-router-dom';
import { ToastProvider } from './components/base/toast';
import { DialogProvider } from './components/dialog-provider';
import Layout from './components/layout';
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
        <DialogProvider>
            <SlotFillProvider>
                <ToastProvider>
                    <RouterProvider router={router} />
                </ToastProvider>
            </SlotFillProvider>
        </DialogProvider>
    );
};

export default App;
