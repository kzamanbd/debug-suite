import { getRoutes } from '@/routing/routes';
import { classNames } from '@/utils';
import { __ } from '@wordpress/i18n';
import { ExternalLink, Info } from 'lucide-react';
import { NavLink } from 'react-router-dom';
import { Button } from './ui/button';

type NavProps = {
    logo?: string;
};

const linkBase = 'rounded-full px-3 py-1.5 text-sm font-medium transition-colors';
const linkActive = 'bg-gray-900 text-white';
const linkIdle = 'text-gray-600 hover:bg-gray-100 hover:text-gray-900';

const navLinkClass = ({ isActive }: { isActive: boolean }) => classNames(linkBase, isActive ? linkActive : linkIdle);

function Navigation({ logo }: NavProps) {
    // Email Log registers its route via the `debugSuite.routes` filter only when
    // its bundle loads (feature enabled), so surface the link conditionally.
    const hasEmailLog = getRoutes().some((route) => route.id === 'email-log');
    const docsUrl = window.debugSuite?.openapi_docs_url;
    const brandLogo = logo || window.debugSuite?.logo_url || '';

    return (
        <div className="debug-suite-header mb-4 w-full border-b border-gray-200 bg-white shadow-sm">
            <div className="flex items-center justify-between px-5 py-3">
                <div className="flex items-center gap-3">
                    {brandLogo ? (
                        <img src={brandLogo} alt={__('Debug Suite', 'debug-suite')} className="h-8 w-auto" />
                    ) : null}
                    <span className="inline-flex items-center rounded-full border border-gray-200 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                        v{window.debugSuite?.version || '1.0.0'}
                    </span>
                </div>

                <nav className="flex items-center gap-1">
                    {hasEmailLog && (
                        <NavLink to="/email-log" className={navLinkClass}>
                            {__('Email Log', 'debug-suite')}
                        </NavLink>
                    )}
                    {docsUrl && (
                        <a
                            href={docsUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className={classNames(linkBase, linkIdle, 'inline-flex items-center gap-1')}>
                            {__('API Docs', 'debug-suite')}
                            <ExternalLink className="h-3.5 w-3.5" />
                        </a>
                    )}
                    <NavLink to="/" end className={navLinkClass}>
                        {__('Settings', 'debug-suite')}
                    </NavLink>
                </nav>

                <Button
                    variant="default"
                    className="size-9 rounded-full border border-gray-200 bg-white text-gray-700 hover:border-gray-900 hover:bg-gray-900 hover:text-white aria-expanded:border-gray-900 aria-expanded:bg-gray-900 aria-expanded:text-white"
                    title={__('Help', 'debug-suite')}
                    aria-label={__('Help', 'debug-suite')}>
                    <Info className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}

export default Navigation;
