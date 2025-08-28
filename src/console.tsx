import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Modal } from './components/base';

const ConsoleApp = () => {
    const [openModal, setOpenModal] = useState(false);
    const barClickHandler = () => {
        setOpenModal(true);
    };

    const onClose = () => {
        setOpenModal(false);
    };

    return (
        <>
            <div role="button" onClick={barClickHandler} className="ab-item ab-empty-item">
                {__('Debug', 'debug-suite')}
            </div>

            <Modal open={openModal} onClose={onClose} fullScreen>
                <h2>{__('Debug Console', 'debug-suite')}</h2>
            </Modal>
        </>
    );
};

export default ConsoleApp;
