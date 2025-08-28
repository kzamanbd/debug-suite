import { useEffect, useState } from '@wordpress/element';
import { Modal } from './components/base';

const ConsoleApp = () => {
    const [openModal, setOpenModal] = useState(false);
    const barClickHandler = () => {
        setOpenModal(true);
    };

    const onClose = () => {
        setOpenModal(false);
    };

    useEffect(() => {
        const barElement = document.getElementById('wp-admin-bar-debug-suite');
        barElement?.addEventListener('click', barClickHandler);

        return () => {
            barElement?.removeEventListener('click', barClickHandler);
        };
    }, []);

    return (
        <Modal open={openModal} onClose={onClose} fullScreen>
            <h2>Debug Console</h2>
        </Modal>
    );
};

export default ConsoleApp;
