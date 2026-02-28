import { SlotFillProvider } from '@wordpress/components';
import { createHashRouter, RouterProvider } from 'react-router-dom';
import { Toaster } from 'sonner';
import ConfirmDialog from './components/confirm-dialog';
import Layout from './components/layout';
import './index.css';
import { withRouter } from './routing';
import { getRoutes } from './routing/routes';

const App = () => {
    // Map the routes to include withRouter for each route element
    const mappedRoutes = getRoutes().map((route) => {
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
                <RouterProvider router={router} />
                <Toaster position="bottom-center" richColors />
            </SlotFillProvider>
            <ConfirmDialog />
        </>
    );
};

export default App;
