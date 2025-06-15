import { cloneElement, createElement, isValidElement } from '@wordpress/element';
import {
    createSearchParams,
    redirect,
    replace,
    useLocation,
    useMatches,
    useNavigate,
    useNavigation,
    useParams
} from 'react-router-dom';

export function withRouter(Component: any) {
    return (props: any) => {
        const navigate = useNavigate();
        const params = useParams();
        const location = useLocation();
        const matches = useMatches();
        const navigation = useNavigation();

        const routerProps = {
            navigate,
            params,
            location,
            redirect,
            replace,
            matches,
            navigation,
            createSearchParams
        };

        // Check if Component is a valid element
        if (isValidElement(Component)) {
            // If it's a valid element, clone it and pass the router props
            return cloneElement(Component, { ...props, ...routerProps });
        }

        // If it's a function component, render it with the router props
        return createElement(Component, {
            ...props,
            ...routerProps
        });
    };
}
