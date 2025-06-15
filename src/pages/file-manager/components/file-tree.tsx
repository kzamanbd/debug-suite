/**
 * FileTree component.
 *
 * Renders a recursive file/folder tree with modern design and i18n support.
 *
 * @since 1.0.0
 */
import { IFile } from '@/types';
import { classNames } from '@/utils';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/react';
import FileIcon from './file-icon';

interface TreeProps {
    file: IFile;
    action: (file: IFile) => void;
    className?: string;
}

const FileTree = ({ file, action, className = '' }: TreeProps) => {
    const isDirectory = file.type === 'directory';
    return (
        <Disclosure as="li" className={classNames('mb-1', className)}>
            {({ open }) => (
                <>
                    <DisclosureButton className="w-full focus:outline-none">
                        <div
                            onClick={() => action(file)}
                            className={classNames(
                                'group flex items-center gap-2 rounded-lg px-2 py-1 transition-colors',
                                isDirectory
                                    ? 'hover:bg-primary-50 dark:hover:bg-primary-900/20 active:bg-primary-100 dark:active:bg-primary-900/40'
                                    : 'hover:bg-gray-50 active:bg-gray-100 dark:hover:bg-gray-800/50 dark:active:bg-gray-700/60',
                                'cursor-pointer select-none'
                            )}
                        >
                            <FileIcon
                                type={isDirectory ? 'directory' : 'file'}
                                className={classNames(
                                    'transition-colors',
                                    isDirectory
                                        ? 'text-primary-600 dark:text-primary-300'
                                        : 'group-hover:text-primary-400 dark:group-hover:text-primary-300 text-gray-400 dark:text-gray-500'
                                )}
                            />
                            <span
                                className={classNames(
                                    'truncate text-left',
                                    isDirectory
                                        ? 'text-primary-800 dark:text-primary-200 font-semibold'
                                        : 'text-gray-800 dark:text-gray-100'
                                )}
                                title={file.name}
                            >
                                {file.name}
                            </span>
                        </div>
                    </DisclosureButton>
                    {file.children?.length ? (
                        <DisclosurePanel
                            static
                            as="ul"
                            className="ml-2 origin-top border-l border-gray-100 pl-4 transition-all duration-100 ease-out data-closed:-translate-y-4 data-closed:opacity-0 dark:border-gray-800"
                        >
                            {file.children.map((child) => (
                                <FileTree key={child.path} file={child} action={action} />
                            ))}
                        </DisclosurePanel>
                    ) : null}
                </>
            )}
        </Disclosure>
    );
};

export default FileTree;
