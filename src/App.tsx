import { createHashRouter, RouterProvider } from 'react-router-dom';
import { DialogProvider } from './components/dialog-provider';
import Layout from './components/layout';
import { ToastProvider } from './components/ui/toast';
import { withRouter } from './routing';
import type { DebugSuiteRoute } from './routing/routes';
import routes from './routing/routes';
import { mutationObserver } from './utils';

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

    mutationObserver(
        document.body,
        (mutations) => {
            for (const mutation of mutations) {
                if (mutation.type !== 'childList') {
                    continue;
                }
                for (const node of mutation.addedNodes) {
                    if (node instanceof HTMLElement && node.id === 'headlessui-portal-root') {
                        node.classList.add('debug-suite-root-app');
                        node.style.display = 'block';
                    }
                }
            }
        },
        { childList: true }
    );

    const router = createHashRouter(mappedRoutes);

    return (
        <DialogProvider>
            <ToastProvider>
                <RouterProvider router={router} />
            </ToastProvider>
        </DialogProvider>
    );
};

export default App;
