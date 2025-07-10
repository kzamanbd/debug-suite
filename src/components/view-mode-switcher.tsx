import Button from '@/components/ui/button';
import { classNames } from '@/utils';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Fullscreen } from 'lucide-react';
interface ViewModeSwitcherProps {
    className?: string;
}

const ViewModeSwitcher = ({ className = '' }: ViewModeSwitcherProps): JSX.Element => {
    // Simple check - are we on frontend?
    const isFullScreen = document.body.classList.contains('debug-suite-full-view');
    const [isLoading, setIsLoading] = useState(false);

    const toggleFullScreen = async () => {
        try {
            setIsLoading(true);
            const response = await apiFetch<{
                success: boolean;
            }>({
                path: '/debug-suite/v1/settings/full-view',
                method: 'POST'
            });
            if (response.success) {
                document.body.classList.toggle('debug-suite-full-view');
            }
        } catch (error) {
            console.error('Error toggling full screen mode:', error);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className={classNames(className, 'flex items-center space-x-3')}>
            <Button loading={isLoading} onClick={toggleFullScreen} className="flex items-center">
                {isFullScreen ? (
                    <>
                        <Fullscreen className="mr-2 h-4 w-4" />
                        {__('Exit Fullscreen', 'debug-suite')}
                    </>
                ) : (
                    <>
                        <Fullscreen className="mr-2 h-4 w-4" />
                        {__('View Fullscreen', 'debug-suite')}
                    </>
                )}
            </Button>
        </div>
    );
};

export default ViewModeSwitcher;
