/**
 * FileTree component.
 *
 * Renders a recursive file/folder tree with modern design and i18n support.
 *
 * @since 1.0.0
 */
import { IFile } from '@/types';
import { cn } from '@/utils/cn';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/react';
import FileIcon from './FileIcon';

interface TreeProps {
    file: IFile;
    action: (file: IFile) => void;
    className?: string;
}

const FileTree = ({ file, action, className = '' }: TreeProps) => {
    return (
        <Disclosure as="li" className={cn('mb-2', className)}>
            <DisclosureButton className="w-full cursor-pointer focus:outline-none">
                {file.type === 'directory' ? (
                    <div
                        onClick={() => action(file)}
                        className="hover:bg-primary-50 dark:hover:bg-primary-900/20 flex items-center gap-2 rounded-lg px-2 py-1 transition-colors"
                    >
                        <FileIcon type="directory" />
                        <span className="text-primary-700 dark:text-primary-300 text-left font-bold">{file.name}</span>
                    </div>
                ) : (
                    <div
                        onClick={() => action(file)}
                        className="flex items-center gap-2 rounded-lg px-2 py-1 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50"
                    >
                        <FileIcon type="file" />
                        <span className="text-gray-800 dark:text-gray-100">{file.name}</span>
                    </div>
                )}
            </DisclosureButton>
            {file.children?.length ? (
                <DisclosurePanel
                    as="ul"
                    className="origin-top pl-4 transition-all duration-100 ease-out data-closed:-translate-y-4 data-closed:opacity-0"
                >
                    {file.children.map((child) => (
                        <FileTree key={child.path} file={child} action={action} />
                    ))}
                </DisclosurePanel>
            ) : null}
        </Disclosure>
    );
};

export default FileTree;
