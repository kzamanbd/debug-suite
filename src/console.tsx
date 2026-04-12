import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import DebugLog from '@/pages/debug-log';
import { Slot, SlotFillProvider } from '@wordpress/components';
import domReady from '@wordpress/dom-ready';
import { createRoot, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Bug, SquareTerminal, X as XIcon } from 'lucide-react';
import QueryConsole from './pages/query-console';

const ConsoleApp = () => {
    const [openModal, setOpenModal] = useState(false);
    const [activeTab, setActiveTab] = useState<'query-console' | 'logs'>('logs');
    const barClickHandler = () => {
        setOpenModal(true);
    };

    const ContentRenderer = () => {
        if (activeTab === 'query-console') {
            return <QueryConsole />;
        }
        return <DebugLog />;
    };

    return (
        <SlotFillProvider>
            <div role="button" onClick={barClickHandler} className="ab-item ab-empty-item">
                {__('Debug', 'debug-suite')}
            </div>

            <Dialog open={openModal} onOpenChange={setOpenModal}>
                <DialogContent fullScreen showCloseButton={false} className="bg-background overflow-hidden">
                    <DialogHeader className="border-border flex shrink-0 flex-row items-center justify-between gap-4 border-b bg-white p-4">
                        <DialogTitle className="flex items-center gap-4">
                            <Button
                                variant={activeTab === 'logs' ? 'secondary' : 'ghost'}
                                onClick={() => setActiveTab('logs')}>
                                <Bug size={20} />
                                <span>Debug Log</span>
                            </Button>
                            <Button
                                variant={activeTab === 'query-console' ? 'secondary' : 'ghost'}
                                onClick={() => setActiveTab('query-console')}>
                                <SquareTerminal size={20} />
                                <span>Console</span>
                            </Button>
                        </DialogTitle>
                        <div className="flex items-center gap-4">
                            <div className="flex flex-wrap items-center gap-2">
                                <Slot name="console-logs-actions" />
                            </div>
                            <DialogClose render={<Button variant="ghost" className="bg-secondary" size="icon-sm" />}>
                                <XIcon size={16} />
                                <span className="sr-only">Close</span>
                            </DialogClose>
                        </div>
                    </DialogHeader>
                    <div className="bg-background min-h-0 flex-1 overflow-y-auto p-4">
                        {openModal && <ContentRenderer />}
                    </div>
                </DialogContent>
            </Dialog>
        </SlotFillProvider>
    );
};

domReady(() => {
    const consoleContainer = document.getElementById('wp-admin-bar-debug-suite');
    if (consoleContainer) {
        // mount the console app
        const root = createRoot(consoleContainer);
        root.render(<ConsoleApp />);
    }
});
