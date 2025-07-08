/**
 * FileTree component.
 *
 * Renders a recursive file/folder tree with modern design and i18n support.
 *
 * @since 1.0.0
 */
import type { ItemTree } from '@/types';
import { classNames } from '@/utils';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/react';
import FileIcon from './file-icon';

interface TreeProps {
    file: ItemTree;
    action: (file: ItemTree) => void;
    className?: string;
}

const FileTree = ({ file, action, className = '' }: TreeProps) => {
    const isDirectory = file.type === 'directory';
    const hasChildren = file.children && file.children.length > 0;

    // For files (non-directories), render without Disclosure
    if (!isDirectory) {
        return (
            <li className={classNames('mb-1', className)}>
                <div
                    onClick={() => action(file)}
                    className={classNames(
                        'group flex items-center gap-2 rounded-lg px-2 py-1 transition-colors',
                        'hover:bg-gray-50 active:bg-gray-100 dark:hover:bg-gray-800/50 dark:active:bg-gray-700/60',
                        'cursor-pointer select-none'
                    )}
                >
                    <FileIcon
                        type="file"
                        className="group-hover:text-primary-400 dark:group-hover:text-primary-300 text-gray-400 transition-colors dark:text-gray-500"
                    />
                    <span className="truncate text-left text-gray-800 dark:text-gray-100" title={file.name}>
                        {file.name}
                    </span>
                </div>
            </li>
        );
    }

    // For directories, render with Disclosure only if they have children
    if (!hasChildren) {
        return (
            <li className={classNames('mb-1', className)}>
                <div
                    onClick={() => action(file)}
                    className={classNames(
                        'group flex items-center gap-2 rounded-lg px-2 py-1 transition-colors',
                        'hover:bg-primary-50 dark:hover:bg-primary-900/20 active:bg-primary-100 dark:active:bg-primary-900/40',
                        'cursor-pointer select-none'
                    )}
                >
                    <FileIcon type="directory" className="text-primary-600 dark:text-primary-300 transition-colors" />
                    <span
                        className="text-primary-800 dark:text-primary-200 truncate text-left font-semibold"
                        title={file.name}
                    >
                        {file.name}
                    </span>
                </div>
            </li>
        );
    }

    // For directories with children, render with Disclosure
    return (
        <Disclosure as="li" className={classNames('mb-1', className)} defaultOpen={hasChildren}>
            {({ open }) => (
                <>
                    <DisclosureButton className="w-full focus:outline-none">
                        <div
                            className={classNames(
                                'group flex items-center gap-2 rounded-lg px-2 py-1 transition-colors',
                                'cursor-pointer select-none',
                                open
                                    ? 'bg-primary-100 dark:bg-primary-900/30 hover:bg-primary-150 dark:hover:bg-primary-900/40'
                                    : 'hover:bg-primary-50 dark:hover:bg-primary-900/20 active:bg-primary-100 dark:active:bg-primary-900/40'
                            )}
                            onClick={() => action(file)}
                        >
                            <FileIcon
                                type="directory"
                                className={classNames(
                                    'transition-colors',
                                    open
                                        ? 'text-primary-700 dark:text-primary-200'
                                        : 'text-primary-600 dark:text-primary-300'
                                )}
                                isOpen={open}
                            />
                            <span
                                className={classNames(
                                    'truncate text-left font-semibold transition-colors',
                                    open
                                        ? 'text-primary-900 dark:text-primary-100'
                                        : 'text-primary-800 dark:text-primary-200'
                                )}
                                title={file.name}
                            >
                                {file.name}
                            </span>
                        </div>
                    </DisclosureButton>
                    <DisclosurePanel
                        as="ul"
                        className="mt-2 ml-2 origin-top border-l border-gray-100 pl-4 transition-all duration-200 ease-out data-[closed]:-translate-y-4 data-[closed]:opacity-0 dark:border-gray-800"
                    >
                        {file.children?.map((child) => (
                            <FileTree key={child.path} file={child} action={action} />
                        ))}
                    </DisclosurePanel>
                </>
            )}
        </Disclosure>
    );
};

export default FileTree;
