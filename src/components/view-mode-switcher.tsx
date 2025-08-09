import Button from '@/components/base/button';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Maximize, Minimize } from 'lucide-react';

const ViewModeSwitcher = () => {
    // Simple check - are we on frontend?
    const isMaximize = document.body.classList.contains('debug-suite-full-view');
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
        <Button loading={isLoading} onClick={toggleFullScreen} className="p-2">
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
