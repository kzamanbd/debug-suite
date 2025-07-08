import { cloneElement, createElement, isValidElement } from '@wordpress/element';
import type { ComponentType, ReactElement } from 'react';
import type { Location, NavigateFunction, Navigation, Params, RedirectFunction } from 'react-router-dom';
import { redirect, replace, useLocation, useMatches, useNavigate, useNavigation, useParams } from 'react-router-dom';

/**
 * Router props interface containing all router-related properties
 */
interface RouterProps {
    navigate: NavigateFunction;
    params: Readonly<Params<string>>;
    location: Location;
    redirect: RedirectFunction;
    replace: RedirectFunction;
    matches: ReturnType<typeof useMatches>;
    navigation: Navigation;
}

/**
 * Higher-order component that injects router props into the wrapped component
 *
 * @template P - Props type of the wrapped component
 * @param Component - The component to wrap with router props
 * @returns A new component with router props injected
 */
export function withRouter<P extends object>(
    Component: ComponentType<P & RouterProps> | ReactElement<P & RouterProps>
) {
    return (props: P) => {
        const routerProps: RouterProps = {
            redirect,
            replace,
            navigate: useNavigate(),
            params: useParams(),
            location: useLocation(),
            matches: useMatches(),
            navigation: useNavigation()
        };

        // Check if Component is a valid element that needs to be cloned
        if (isValidElement(Component)) {
            return cloneElement(Component, {
                ...props,
                ...routerProps
            });
        }

        // Otherwise treat it as a function component
        return createElement(Component, {
            ...props,
            ...routerProps
        });
    };
}
