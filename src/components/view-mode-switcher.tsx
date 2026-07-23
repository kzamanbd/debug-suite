import { Button } from '@/components/ui';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { Maximize, Minimize } from 'lucide-react';

const ViewModeSwitcher = () => {
    // Simple check - are we on frontend?
    const isMaximize = document.body.classList.contains('debug-suite-full-view');

    const toggleFullScreen = async () => {
        document.body.classList.toggle('debug-suite-full-view');
        try {
            await apiFetch<{
                success: boolean;
            }>({
                path: '/debug-suite/v1/settings/full-view',
                method: 'POST'
            });
        } catch (error) {
            console.error('Error toggling full screen mode:', error);
        }
    };

    return (
        <Button variant="outline" size="icon" onClick={toggleFullScreen}>
            {isMaximize ? (
                <div title={__('Minimize', 'debug-suite')}>
                    <Minimize className="size-4" />
                </div>
            ) : (
                <div title={__('Maximize', 'debug-suite')}>
                    <Maximize className="size-4" />
                </div>
            )}
        </Button>
    );
};

export default ViewModeSwitcher;
