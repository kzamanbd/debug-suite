/**
 * MyModal component.
 *
 * A reusable modal dialog using Headless UI, with full accessibility and brand styling.
 *
 * @since 1.0.0
 */
import { cn } from '@/utils/cn';
import { Dialog, DialogBackdrop, DialogPanel, DialogTitle, Transition, TransitionChild } from '@headlessui/react';
import { Fragment, ReactNode } from 'react';

interface MyModalProps {
    open: boolean;
    onClose: () => void;
    title?: ReactNode;
    children: ReactNode;
    className?: string;
}

const Modal = ({ open, onClose, title, children, className = '' }: MyModalProps): JSX.Element => {
    return (
        <Transition show={open} as={Fragment}>
            <Dialog as="div" className="relative z-[99999]" onClose={onClose}>
                <DialogBackdrop onClick={onClose} className={cn('fixed inset-0 bg-black/30 backdrop-blur-sm')} />
                <TransitionChild
                    as={Fragment}
                    enter="ease-out duration-200"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-150"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="fixed inset-0 bg-black/30 backdrop-blur-sm" />
                </TransitionChild>
                <div className="fixed inset-0 z-[99999] flex items-center justify-center p-4">
                    <TransitionChild
                        as={Fragment}
                        enter="ease-out duration-200"
                        enterFrom="opacity-0 scale-95"
                        enterTo="opacity-100 scale-100"
                        leave="ease-in duration-150"
                        leaveFrom="opacity-100 scale-100"
                        leaveTo="opacity-0 scale-95"
                    >
                        <DialogPanel
                            className={cn(
                                'w-full max-w-lg rounded bg-white shadow-2xl ring-1 ring-black/10 dark:bg-gray-900',
                                'transition-all',
                                className
                            )}
                        >
                            {title && (
                                <DialogTitle className="mb-4 flex items-center justify-between rounded-t border-b bg-gray-50 p-4">
                                    <div className="text-primary-700 dark:text-primary-300 text-lg font-semibold">
                                        {title}
                                    </div>
                                    <button type="button" onClick={onClose} aria-label="Close">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                        >
                                            <g fill="none" stroke="currentColor" stroke-width="1.5">
                                                <circle cx="12" cy="12" r="10" opacity="0.5" />
                                                <path stroke-linecap="round" d="m14.5 9.5l-5 5m0-5l5 5" />
                                            </g>
                                        </svg>
                                    </button>
                                </DialogTitle>
                            )}
                            <div className="p-4">{children}</div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </Transition>
    );
};

export default Modal;
