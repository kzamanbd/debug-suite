import { ToastProvider } from '@/components/base/toast';
import { SlotFillProvider } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { createHashRouter, RouterProvider } from 'react-router-dom';
import ConfirmDialog from './components/confirm-dialog';
import Layout from './components/layout';
import './index.css';
import { withRouter } from './routing';
import type { DebugSuiteRoute } from './routing/routes';
import routes from './routing/routes';

const App = () => {
    const filteredRoutes = applyFilters('debug-suite-routes', routes) as DebugSuiteRoute[];
    // Map the routes to include withRouter for each route element
    const mappedRoutes = filteredRoutes.map((route: DebugSuiteRoute) => {
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
