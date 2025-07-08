import { classNames } from '@/utils';
import { __ } from '@wordpress/i18n';
import { ExternalLink, Monitor } from 'lucide-react';
import { useLocation } from 'react-router-dom';
interface ViewModeSwitcherProps {
    className?: string;
}

const ViewModeSwitcher = ({ className = '' }: ViewModeSwitcherProps): JSX.Element => {
    // Simple check - are we on frontend?
    const isFrontend = document.body.classList.contains('debug-suite-frontend');
    const location = useLocation();

    // Simple URL construction
    const adminUrl = '/wp-admin/admin.php?page=debug-suite';
    const frontendUrl = '/debug-suite';
    const redirectUrl = isFrontend ? adminUrl : frontendUrl;
    const redirectPath = `${redirectUrl}#${location.pathname}`.replace('#/', '#');

    return (
        <div className={classNames(className, 'flex items-center space-x-3')}>
            <div className="flex items-center text-sm text-gray-600">
                <Monitor className="mr-2 h-4 w-4" />
                <span>{isFrontend ? __('Frontend', 'debug-suite') : __('Admin', 'debug-suite')}</span>
            </div>
            <a
                href={redirectPath}
                rel="noopener noreferrer"
                className="border-primary/20 text-primary hover:bg-primary inline-flex items-center rounded-md border px-3 py-1.5 text-sm font-medium transition-colors hover:text-white"
            >
                <ExternalLink className="mr-2 h-4 w-4" />
                {isFrontend ? __('Admin View', 'debug-suite') : __('Frontend View', 'debug-suite')}
            </a>
        </div>
    );
};

export default ViewModeSwitcher;
