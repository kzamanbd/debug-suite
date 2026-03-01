import DebugLog from '@/pages/debug-log';
import { Slot, SlotFillProvider } from '@wordpress/components';
import domReady from '@wordpress/dom-ready';
import { createRoot, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Bug, SquareTerminal } from 'lucide-react';
import { Button, Modal } from './components/base';
import QueryConsole from './pages/query-console';
import { classNames } from './utils';

const ConsoleApp = () => {
    const [openModal, setOpenModal] = useState(false);
    const [activeTab, setActiveTab] = useState<'query-console' | 'logs'>('query-console');
    const barClickHandler = () => {
        setOpenModal(true);
    };

    const onClose = () => {
        setOpenModal(false);
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

            <Modal open={openModal} onClose={onClose} fullScreen>
                <Modal.Title className="flex items-center justify-between gap-4 bg-white">
                    <div className="flex items-center gap-4">
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
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Slot name="console-logs-actions" />
                    </div>
                </Modal.Title>
                <Modal.Content className="px-4 py-0">{openModal && <Renderer />}</Modal.Content>
            </Modal>
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
