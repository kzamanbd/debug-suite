import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import DebugLog from '@/pages/debug-log';
import { Slot, SlotFillProvider } from '@wordpress/components';
import domReady from '@wordpress/dom-ready';
import { createRoot, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Bug, SquareTerminal } from 'lucide-react';
import { Button } from './components/base';
import QueryConsole from './pages/query-console';
import { classNames } from './utils';

const ConsoleApp = () => {
    const [openModal, setOpenModal] = useState(false);
    const [activeTab, setActiveTab] = useState<'query-console' | 'logs'>('query-console');
    const barClickHandler = () => {
        setOpenModal(true);
    };

    const Renderer = () => {
        if (activeTab === 'query-console') {
            return <QueryConsole />;
        }
        return <DebugLog className="[&>div]:rounded-none [&>div]:border-t-transparent" />;
    };

    return (
        <SlotFillProvider>
            <div role="button" onClick={barClickHandler} className="ab-item ab-empty-item">
                {__('Debug', 'debug-suite')}
            </div>

            <Dialog open={openModal} onOpenChange={setOpenModal}>
                <DialogContent fullScreen className="bg-background">
                    <DialogHeader className="flex flex-row items-center justify-between gap-4 border-b bg-white p-4">
                        <DialogTitle className="flex items-center gap-4">
                            <Button
                                variant="text"
                                onClick={() => setActiveTab('query-console')}
                                className={classNames({ 'text-primary': activeTab === 'query-console' })}>
                                <SquareTerminal size={20} />
                                <span>Console</span>
                            </Button>
                            <Button
                                variant="text"
                                onClick={() => setActiveTab('logs')}
                                className={classNames({ 'text-primary': activeTab === 'logs' })}>
                                <Bug size={20} />
                                <span>Debug Log</span>
                            </Button>
                        </DialogTitle>
                        <div className="flex flex-wrap items-center gap-2 pr-10">
                            <Slot name="console-logs-actions" />
                        </div>
                    </DialogHeader>
                    <div className="bg-background flex-1 overflow-y-auto">{openModal && <Renderer />}</div>
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
