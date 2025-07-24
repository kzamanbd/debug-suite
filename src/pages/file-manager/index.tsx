/**
 * FileManager page component.
 *
 * Modern file manager UI with i18n, brand color, and Tailwind v4.
 *
 * @since 1.0.0
 */
import Badge from '@/components/base/badge';
import Button from '@/components/base/button';
import Card from '@/components/base/card';
import CustomCheckbox from '@/components/base/checkbox';
import TextInput from '@/components/base/text-input';
import type { ItemTree } from '@/types';
import { classNames } from '@/utils';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { FolderPlus, HardDrive, MoreVertical, Upload } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import SimpleBar from 'simplebar-react';
import FileDetailSkeleton from './components/detail-skeleton';
import FileEditor from './components/file-editor';
import FileIcon from './components/file-icon';
import FileTree from './components/file-tree';
import FileTreeSkeleton from './components/tree-skeleton';

const FileManager = () => {
    const [files, setFiles] = useState<ItemTree[]>([]);
    const [openEditor, setOpenEditor] = useState(false);
    const [selectedFiles, setSelectedFiles] = useState<ItemTree[]>([]);
    const [initialLoading, setInitialLoading] = useState(false);
    const [detailLoading, setDetailLoading] = useState(false);
    const [breadcrumb, setBreadcrumb] = useState<string[]>([]);
    const [selectedFile, setSelectedFile] = useState<ItemTree>();

    const fetchFiles = async (path = '') => {
        // Fetch files from the server
        const apiPath = addQueryArgs('/debug-suite/v1/files', {
            path
        });

        return apiFetch<{
            tree: ItemTree[];
        }>({
            path: apiPath
        });
    };

    const fetchNestedFiles = async (file: ItemTree) => {
        if (file.type === 'file') {
            setSelectedFile(file);
            setOpenEditor(true);
            return;
        }
        const data = file.path.split('/').map((item) => {
            return item.trim();
        });
        setBreadcrumb(data);
        if (file.children?.length) {
            setSelectedFiles(file.children);
            return;
        }
        try {
            setDetailLoading(true);
            const response = await fetchFiles(file.path);
            file.children?.push(...response.tree);
            setSelectedFiles(file.children || []);
            setDetailLoading(false);
        } catch (err) {
            console.error(err);
        }
    };

    const breadcrumbClickHandler = (index?: number) => {
        let path = '';
        if (index) {
            const data = breadcrumb.slice(0, index + 1);
            setBreadcrumb(data);
            path = data.map((item) => item).join('\\');
        } else {
            setBreadcrumb([]);
            setInitialLoading(true);
        }
        void fetchInitialFile(path);
        setDetailLoading(true);
    };

    const fetchInitialFile = useCallback(async (path?: string) => {
        try {
            const response = await fetchFiles(path);
            if (!path) {
                setFiles(response.tree);
            }
            setSelectedFiles(response.tree);
            setDetailLoading(false);
            setInitialLoading(false);
        } catch (err) {
            console.error(err);
        }
    }, []);

    const toggleEditor = () => {
        setOpenEditor(!openEditor);
        if (!openEditor) {
            setSelectedFile(undefined);
        }
    };

    const handleEditorSaveSuccess = () => {
        // Optionally refresh the file list or update UI after successful save
        void fetchInitialFile(breadcrumb.join('\\'));
    };

    const allSelected = selectedFiles.every((file) => file.checked);

    const checkedItems = selectedFiles.filter((file) => file.checked);

    const checkedItem = (file: ItemTree, e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedFiles(
            selectedFiles.map((item) => {
                if (item.path === file.path) {
                    return { ...item, checked: e.target.checked };
                }
                return item;
            })
        );
    };

    const checkedAllItems = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedFiles(selectedFiles.map((file) => ({ ...file, checked: e.target.checked })));
    };

    useEffect(() => {
        setInitialLoading(true);
        void fetchInitialFile();
        setDetailLoading(true);
    }, [fetchInitialFile]);

    return (
        <Card className={classNames('p-4 shadow-xs dark:bg-gray-900')}>
            {/* <!-- Search and Action Buttons --> */}
            <div className="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <TextInput
                    type="text"
                    placeholder={__('Search Files & Folders', 'debug-suite')}
                    className="md:w-1/3 dark:bg-gray-800 dark:text-white"
                />
                <div className="flex flex-wrap gap-2">
                    <Button aria-label={__('Create new folder', 'debug-suite')}>
                        <FolderPlus size={16} />
                        <span>{__('New Folder', 'debug-suite')}</span>
                    </Button>
                    <Button variant="primary" aria-label={__('Upload files', 'debug-suite')}>
                        <Upload size={16} />
                        <span>{__('Upload Files', 'debug-suite')}</span>
                    </Button>
                </div>
            </div>
            {/* <!-- Breadcrumb --> */}
            <div className="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div className="flex items-center whitespace-nowrap">
                    <div className="inline-flex items-center">
                        <button
                            type="button"
                            className="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:text-blue-600 focus:outline-hidden dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500"
                            onClick={() => breadcrumbClickHandler()}>
                            <HardDrive size={16} className="mr-2" />
                            {__('root', 'debug-suite')}
                        </button>
                        {breadcrumb.length > 0 && (
                            <svg
                                className="size-5 shrink-0 text-gray-400 dark:text-neutral-600"
                                width="16"
                                height="16"
                                viewBox="0 0 16 16"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                                aria-hidden="true">
                                <path d="M6 13L10 3" stroke="currentColor" strokeLinecap="round"></path>
                            </svg>
                        )}
                    </div>
                    {breadcrumb.map((item, index) => (
                        <div key={index} className="inline-flex items-center">
                            <button
                                type="button"
                                className="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:text-blue-600 focus:outline-hidden dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500"
                                onClick={() => breadcrumbClickHandler(index)}>
                                {item}
                                {index < breadcrumb.length - 1 && (
                                    <svg
                                        className="size-5 shrink-0 text-gray-400 dark:text-neutral-600"
                                        width="16"
                                        height="16"
                                        viewBox="0 0 16 16"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        aria-hidden="true">
                                        <path d="M6 13L10 3" stroke="currentColor" strokeLinecap="round"></path>
                                    </svg>
                                )}
                            </button>
                        </div>
                    ))}
                </div>

                <Badge variant="primary">
                    {selectedFiles.length} {__('items', 'debug-suite')}
                </Badge>
            </div>
            {/* <!-- File List --> */}
            <div className="grid grid-cols-7 overflow-auto rounded-lg border dark:border-gray-800">
                <div className="col-span-2">
                    {initialLoading ? (
                        <div className="p-4">
                            {Array.from({ length: 5 }).map((_, index) => (
                                <FileTreeSkeleton key={index} />
                            ))}
                        </div>
                    ) : (
                        <SimpleBar className="h-[600px] overflow-y-auto">
                            <ul className="p-4">
                                {files.map((file) => (
                                    <FileTree key={file.path} file={file} action={fetchNestedFiles} />
                                ))}
                            </ul>
                        </SimpleBar>
                    )}
                </div>
                <div className="col-span-5 border-l dark:border-gray-800">
                    {detailLoading ? <FileDetailSkeleton /> : null}
                    {selectedFiles.length && !detailLoading ? (
                        <SimpleBar className="h-[600px] overflow-y-auto">
                            <table className="w-full overflow-hidden rounded-lg border border-gray-200 text-left dark:border-gray-700">
                                <thead>
                                    <tr className="bg-gray-50 text-xs font-semibold text-gray-500 uppercase dark:bg-gray-800 dark:text-gray-300">
                                        <td className="sticky top-0 z-50 w-10 rounded-tl-lg border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700">
                                            <CustomCheckbox checked={allSelected} onChange={checkedAllItems} />
                                        </td>
                                        <th className="sticky top-0 z-50 border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700">
                                            {__('Name', 'debug-suite')}
                                        </th>
                                        <th className="sticky top-0 z-50 border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700">
                                            {__('Size', 'debug-suite')}
                                        </th>
                                        <th className="sticky top-0 z-50 border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700">
                                            {__('Last Modified', 'debug-suite')}
                                        </th>
                                        <th className="sticky top-0 z-50 rounded-tr-lg border-b border-gray-200 bg-white px-4 py-3 text-center dark:border-gray-700">
                                            {__('Actions', 'debug-suite')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="text-sm text-gray-700 dark:text-gray-100">
                                    {checkedItems.length ? (
                                        <tr className="on-parent-hover-show bg-primary-50/40 dark:bg-primary-900/10">
                                            <td
                                                colSpan={5}
                                                className="rounded-lg border-b border-gray-200 px-4 py-3 text-center dark:border-gray-700">
                                                {__('You have selected', 'debug-suite')}{' '}
                                                <strong>{checkedItems.length}</strong> {__('users.', 'debug-suite')}
                                                <button
                                                    className="mx-1 text-red-500 hover:text-red-700"
                                                    onClick={() =>
                                                        confirm('Are you sure you want to delete all selected files?')
                                                    }>
                                                    {__('Delete', 'debug-suite')}
                                                </button>
                                                {__('them?', 'debug-suite')}
                                            </td>
                                        </tr>
                                    ) : null}

                                    {selectedFiles.map((file) => (
                                        <tr
                                            key={file.path}
                                            className={classNames(
                                                'group transition-colors',
                                                file.checked
                                                    ? 'bg-primary-50 dark:bg-primary-900/10'
                                                    : 'hover:bg-gray-50 dark:hover:bg-gray-800/40'
                                            )}>
                                            <td className="w-10 border-b border-gray-200 px-4 py-3 align-middle dark:border-gray-700">
                                                <CustomCheckbox
                                                    onChange={checkedItem.bind(null, file)}
                                                    checked={file.checked}
                                                />
                                            </td>
                                            <td className="border-b border-gray-200 px-4 py-3 align-middle dark:border-gray-700">
                                                <div
                                                    onClick={fetchNestedFiles.bind(null, file)}
                                                    className="flex cursor-pointer items-center gap-2">
                                                    <FileIcon type={file.type} />
                                                    <span className="ml-2 truncate font-medium text-gray-900 dark:text-white">
                                                        {file.name}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="border-b border-gray-200 px-4 py-3 align-middle text-gray-500 dark:border-gray-700 dark:text-gray-300">
                                                {file.size}
                                            </td>
                                            <td className="border-b border-gray-200 px-4 py-3 align-middle text-gray-500 dark:border-gray-700 dark:text-gray-300">
                                                {file.modified_at}
                                            </td>
                                            <td className="border-b border-gray-200 px-4 py-3 text-center align-middle dark:border-gray-700">
                                                <button className="hover:bg-primary-100 hover:text-primary-700 dark:hover:bg-primary-900/20 dark:hover:text-primary-300 rounded p-1 text-gray-500 transition-colors">
                                                    <MoreVertical size={16} />
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </SimpleBar>
                    ) : null}
                </div>
            </div>
            <FileEditor
                open={openEditor}
                toggle={toggleEditor}
                fileName={selectedFile?.name || ''}
                filePath={selectedFile?.path || ''}
                onSaveSuccess={handleEditorSaveSuccess}
            />
        </Card>
    );
};
export default FileManager;
