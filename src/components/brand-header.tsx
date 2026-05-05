import { __ } from '@wordpress/i18n';
import { Info } from 'lucide-react';
import { Button } from './base';

type HeaderProps = {
    logo?: string;
};

function Header({ logo }: HeaderProps) {
    return (
        <div className="debug-suite-header sticky top-8 z-10 mb-4 w-full border-b border-gray-200 bg-white shadow-sm">
            <div className="flex items-center justify-between px-5 py-3">
                <div className="flex items-center gap-3">
                    {logo ? <img src={logo} alt={__('Debug Suite', 'debug-suite')} className="h-8 w-auto" /> : null}
                    <span className="inline-flex items-center rounded-full border border-gray-200 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                        v1.0.0
                    </span>
                </div>
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

export default Header;
