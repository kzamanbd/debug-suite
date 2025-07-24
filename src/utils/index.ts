import type { ClassValue } from 'clsx';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * mutationObserver hook.
 *
 * @since 1.0.0
 *
 * @param {Node}                 targetNode       Target node
 * @param {MutationCallback}     mutationCallback Callback function
 * @param {MutationObserverInit} config           (Optional) MutationObserverInit
 */
export const mutationObserver = (
    targetNode: Node,
    mutationCallback: MutationCallback,
    config?: MutationObserverInit
) => {
    const observer = new MutationObserver(mutationCallback);
    observer.observe(targetNode, config);

    return observer;
};

export const classNames = (...inputs: ClassValue[]) => {
    return twMerge(clsx(inputs));
};
