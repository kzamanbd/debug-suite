/**
 * FileManager page component.
 *
 * Modern file manager UI with i18n, brand color, and Tailwind v4.
 *
 * @since 1.0.0
 */
import Badge from '@/components/ui/badge';
import Button from '@/components/ui/button';
import Card from '@/components/ui/card';
import InputField from '@/components/ui/input-field';
import { IFile } from '@/types';
import { cn } from '@/utils/cn';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from 'react';
import FileDetailSkeleton from './components/detail-skeleton';
import FileEditor from './components/file-editor';
import FileIcon from './components/file-icon';
import FileTree from './components/file-tree';
import FileTreeSkeleton from './components/tree-skeleton';

const FileManager = () => {
    const [files, setFiles] = useState<IFile[]>([]);
    const [openEditor, setOpenEditor] = useState(false);
    const [fileContent, setFileContent] = useState('');
    const [fileName, setFileName] = useState('');
    const [selectedFiles, setSelectedFiles] = useState<IFile[]>([]);
    const [initialLoading, setInitialLoading] = useState(false);
    const [detailLoading, setDetailLoading] = useState(false);
    const [breadcrumb, setBreadcrumb] = useState<Record<string, string>[]>([
        {
            name: '',
            separator: '/'
        }
    ]);

    const fetchFiles = async (path?: string) => {
        // Fetch files from the server
        return apiFetch<{
            files: IFile[];
        }>({
            path: `/debug-suite/v1/files?path=${encodeURIComponent(path || '')}`
        });
    };

    const fetchFileContent = async (path: string) => {
        return apiFetch<{
            contents: string;
            extension: string;
        }>({
            path: `/debug-suite/v1/files/content?path=${encodeURIComponent(path || '')}`
        });
    };

    const fetchNestedFiles = async (file: IFile) => {
        if (file.type === 'file') {
            fileEditHandler(file);
            return;
        }
        const data = file.path.split('\\').map((item) => {
            return {
                name: item.trim(),
                separator: '/'
            };
        });
        setBreadcrumb(data);
        if (file.children?.length) {
            setSelectedFiles(file.children);
            return;
        }
        try {
            setDetailLoading(true);
            const response = await fetchFiles(file.path);
            file.children.push(...response.files);
            setSelectedFiles(file.children);
            setDetailLoading(false);
        } catch (err) {
            console.error(err);
        }
    };

    const breadcrumbClickHandler = async (index: number) => {
        const data = breadcrumb.slice(0, index + 1);
        setBreadcrumb(data);
        const path = data.map((item) => item.name).join('\\');
        fetchInitialFile(path);
        setDetailLoading(true);
    };

    const fetchInitialFile = useCallback(async (path?: string) => {
        try {
            const response = await fetchFiles(path);
            if (!path) {
                setFiles(response.files);
            }
            setSelectedFiles(response.files);
            setDetailLoading(false);
            setInitialLoading(false);
        } catch (err) {
            console.error(err);
        }
    }, []);

    const fileEditHandler = async (file: IFile) => {
        if (file.type === 'file') {
            toggleEditor();
            setFileName(file.name);
            const response = await fetchFileContent(file.path);
            setFileContent(response.contents);
        }
    };

    const toggleEditor = () => {
        setOpenEditor(!openEditor);
        if (!openEditor) {
            setFileContent('');
        }
    };

    const allSelected = selectedFiles.every((file) => file.checked);

    const checkedItems = selectedFiles.filter((file) => file.checked);

    const checkedItem = (file: IFile, e: React.ChangeEvent<HTMLInputElement>) => {
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
        fetchInitialFile();
    }, []);

    return (
        <Card className={cn('p-4 shadow-xs dark:bg-gray-900')}>
            {/* <!-- Search and Action Buttons --> */}
            <div className="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <InputField
                    type="text"
                    placeholder={__('Search Files & Folders', 'debug-suite')}
                    className="md:w-1/3 dark:bg-gray-800 dark:text-white"
                />
                <div className="flex flex-wrap gap-2 md:space-x-4">
                    <Button variant="light" aria-label={__('Create new folder', 'debug-suite')}>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            fill="currentColor"
                            className="bi bi-cloud-arrow-up"
                            viewBox="0 0 16 16"
                        >
                            <path
                                fillRule="evenodd"
                                d="M7.646 5.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708z"
                            />
                            <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383m.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z" />
                        </svg>
                        <span>{__('New Folder', 'debug-suite')}</span>
                    </Button>
                    <Button variant="primary" aria-label={__('Upload files', 'debug-suite')}>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            fill="currentColor"
                            className="bi bi-cloud-arrow-up"
                            viewBox="0 0 16 16"
                        >
                            <path
                                fillRule="evenodd"
                                d="M7.646 5.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708z"
                            />
                            <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383m.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z" />
                        </svg>
                        <span>{__('Upload Files', 'debug-suite')}</span>
                    </Button>
                </div>
            </div>
            {/* <!-- Breadcrumb --> */}
            <div className="mb-4 flex flex-col gap-2 text-sm font-semibold text-gray-500 md:flex-row md:items-center md:justify-between dark:text-gray-300">
                <div className="bg-primary-100 dark:bg-primary-900/20 flex w-max items-center gap-2 rounded-lg px-2 py-1">
                    <span className="text-primary-500">
                        <svg className="size-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g id="SVGRepo_bgCarrier" strokeWidth="0"></g>
                            <g id="SVGRepo_tracerCarrier" strokeLinecap="round" strokeLinejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <path
                                    d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z"
                                    className="stroke-primary"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeDasharray="4 4"
                                ></path>
                            </g>
                        </svg>
                    </span>
                    <div className="flex gap-2">
                        {breadcrumb.map((item, index) => (
                            <div key={index} className="text-primary-500">
                                <span
                                    onClick={breadcrumbClickHandler.bind(null, index)}
                                    className="cursor-pointer underline"
                                >
                                    {item.name || __('Local', 'debug-suite')}
                                </span>
                                {index !== breadcrumb.length - 1 ? (
                                    <span className="ml-2 text-gray-700 dark:text-gray-300">{item.separator}</span>
                                ) : null}
                            </div>
                        ))}
                    </div>
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
                        <div className="h-[500px] overflow-y-auto">
                            <ul className="p-4">
                                {files.map((file) => (
                                    <FileTree key={file.path} file={file} action={fetchNestedFiles} />
                                ))}
                            </ul>
                        </div>
                    )}
                </div>
                <div className="col-span-5 border-l dark:border-gray-800">
                    {detailLoading ? <FileDetailSkeleton /> : null}
                    {selectedFiles.length && !detailLoading ? (
                        <div className="h-[500px] overflow-y-auto">
                            <table className="w-full overflow-hidden rounded-lg border border-gray-200 text-left dark:border-gray-700">
                                <thead>
                                    <tr className="bg-gray-50 text-xs font-semibold text-gray-500 uppercase dark:bg-gray-800 dark:text-gray-300">
                                        <td className="sticky top-0 z-50 w-10 rounded-tl-lg border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700">
                                            <input
                                                type="checkbox"
                                                className="form-input-checkbox accent-primary-500"
                                                checked={allSelected}
                                                onChange={checkedAllItems}
                                            />
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
                                                className="rounded-lg border-b border-gray-200 px-4 py-3 text-center dark:border-gray-700"
                                            >
                                                {__('You have selected', 'debug-suite')}{' '}
                                                <strong>{checkedItems.length}</strong> {__('users.', 'debug-suite')}
                                                <button
                                                    className="mx-1 text-red-500 hover:text-red-700"
                                                    onClick={() =>
                                                        confirm('Are you sure you want to delete all selected files?')
                                                    }
                                                >
                                                    {__('Delete', 'debug-suite')}
                                                </button>
                                                {__('them?', 'debug-suite')}
                                            </td>
                                        </tr>
                                    ) : null}

                                    {selectedFiles.map((file) => (
                                        <tr
                                            key={file.path}
                                            className={cn(
                                                'group transition-colors',
                                                file.checked
                                                    ? 'bg-primary-50 dark:bg-primary-900/10'
                                                    : 'hover:bg-gray-50 dark:hover:bg-gray-800/40'
                                            )}
                                        >
                                            <td className="w-10 border-b border-gray-200 px-4 py-3 align-middle dark:border-gray-700">
                                                <input
                                                    type="checkbox"
                                                    className="form-input-checkbox accent-primary-500"
                                                    onChange={checkedItem.bind(null, file)}
                                                    checked={file.checked}
                                                />
                                            </td>
                                            <td className="border-b border-gray-200 px-4 py-3 align-middle dark:border-gray-700">
                                                <div
                                                    onClick={fetchNestedFiles.bind(null, file)}
                                                    className="flex cursor-pointer items-center gap-2"
                                                >
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
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        width="16"
                                                        height="16"
                                                        fill="currentColor"
                                                        className="bi bi-three-dots-vertical"
                                                        viewBox="0 0 16 16"
                                                    >
                                                        <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : null}
                </div>
            </div>
            <FileEditor open={openEditor} toggle={toggleEditor} fileContent={fileContent} fileName={fileName} />
        </Card>
    );
};
export default FileManager;
