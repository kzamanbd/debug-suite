import DebugLog from '@/pages/debug-log';
import { useState } from '@wordpress/element';
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
        return <DebugLog />;
    };

    return (
        <>
            <div role="button" onClick={barClickHandler} className="ab-item ab-empty-item">
                {__('Debug', 'debug-suite')}
            </div>

            <Modal open={openModal} onClose={onClose} fullScreen>
                <Modal.Title className="flex items-center justify-between gap-4">
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
                    {activeTab === 'logs' && (
                        <Button className="mr-8" variant="text">
                            Clear
                        </Button>
                    )}
                </Modal.Title>
                <Modal.Content>{openModal && <Renderer />}</Modal.Content>
            </Modal>
        </>
    );
};

export default ConsoleApp;
