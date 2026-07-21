import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import type { DialogModalProps, DialogType, DialogTypeProps } from '@/types';
import { classNames } from '@/utils';
import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from 'react';

const typeMap: Record<DialogType, DialogTypeProps> = {
    success: {
        icon: (
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="size-6">
                <path
                    fillRule="evenodd"
                    d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                    clipRule="evenodd"
                />
            </svg>
        ),
        defaultTitle: 'Success',
        iconClass: 'bg-green-100 text-green-600',
        buttonClass: 'bg-green-600 hover:bg-green-500'
    },
    error: {
        icon: (
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                strokeWidth={1.5}
                stroke="currentColor"
                className="size-6">
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
                />
            </svg>
        ),
        defaultTitle: 'Error occurred',
        iconClass: 'bg-red-100 text-red-600',
        buttonClass: 'bg-red-600 hover:bg-red-500'
    },
    warning: {
        icon: (
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                strokeWidth={1.5}
                stroke="currentColor"
                className="size-6">
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
                />
            </svg>
        ),
        defaultTitle: 'Warning',
        iconClass: 'bg-yellow-100 text-yellow-600',
        buttonClass: 'bg-yellow-600 hover:bg-yellow-500'
    },
    confirm: {
        icon: (
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                strokeWidth={1.5}
                stroke="currentColor"
                className="size-6">
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"
                />
            </svg>
        ),
        defaultTitle: 'Are you sure?',
        iconClass: 'bg-red-100 text-red-600',
        buttonClass: 'bg-red-600 hover:bg-red-500'
    }
};

const DialogModal: React.FC<DialogModalProps> = ({ title, message, open, options, onClose }) => {
    const [hovered, setHovered] = useState(false);
    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const {
        type = 'confirm',
        okText = __('Continue', 'debug-suite'),
        autoHideDelay = 2000,
        showOk = true,
        showCancel = true,
        cancelText = __('Cancel', 'debug-suite'),
        allowOutsideClick = true
    } = options;

    useEffect(() => {
        if (type !== 'confirm' && open && !hovered) {
            if (timerRef.current) clearTimeout(timerRef.current);
            timerRef.current = setTimeout(() => {
                onClose(true);
            }, autoHideDelay);
        }
        return () => {
            if (timerRef.current) clearTimeout(timerRef.current);
        };
    }, [open, hovered, autoHideDelay, type, onClose]);

    const currentIcon = typeMap[type].icon;
    const currentTitle = title || typeMap[type].defaultTitle;
    const iconClassName = typeMap[type].iconClass || '';
    const buttonClassName = typeMap[type].buttonClass || '';

    const handleClose = (isOpen: boolean) => {
        if (!isOpen) {
            if (!allowOutsideClick) return;
            onClose(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent showCloseButton={false} className="max-w-md p-6">
                <div onMouseEnter={() => setHovered(true)} onMouseLeave={() => setHovered(false)}>
                    <div className="sm:flex sm:items-start">
                        <div
                            className={classNames(
                                'mx-auto flex size-12 shrink-0 items-center justify-center rounded-full sm:mx-0 sm:size-10',
                                iconClassName
                            )}>
                            {currentIcon}
                        </div>
                        <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <DialogTitle className="text-base font-semibold text-gray-900">{currentTitle}</DialogTitle>
                            <DialogDescription className="mt-2">
                                <p className="text-sm text-gray-500">{message}</p>
                            </DialogDescription>
                        </div>
                    </div>
                    <DialogFooter className="mt-4">
                        <div className="sm:flex sm:flex-row-reverse">
                            {showOk && (
                                <button
                                    type="button"
                                    onClick={() => onClose(true)}
                                    className={classNames(
                                        'inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-xs sm:ml-3 sm:w-auto',
                                        buttonClassName
                                    )}>
                                    {okText}
                                </button>
                            )}
                            {showCancel && type === 'confirm' && (
                                <button
                                    type="button"
                                    data-autofocus
                                    onClick={() => onClose(false)}
                                    className="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50 sm:mt-0 sm:w-auto">
                                    {cancelText}
                                </button>
                            )}
                        </div>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    );
};

export default DialogModal;
