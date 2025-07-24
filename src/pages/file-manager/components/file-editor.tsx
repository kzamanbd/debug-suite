/**
 * FileEditor component.
 *
 * A modal file editor using the generic Editor component.
 *
 * @since 1.0.0
 */
import Modal from '@/components/base/modal';
import Editor from '@/components/editor';
import type { ValidationError } from '@/components/editor/validators';
import { classNames } from '@/utils';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

/**
 * Props for the FileEditor component.
 *
 * @since 1.0.0
 */
interface FileEditorProps {
    open: boolean;
    filePath: string;
    fileName: string;
    toggle: (action: boolean) => void;
    onSaveSuccess?: () => void;
}

/**
 * FileEditor modal component.
 *
 * @since 1.0.0
 */
const FileEditor = ({ open, toggle, fileName, filePath, onSaveSuccess }: FileEditorProps): JSX.Element => {
    const [editorContent, setEditorContent] = useState('');
    const [hasChanges, setHasChanges] = useState(false);
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const fetchFileContents = async (path: string) => {
        const apiPath = addQueryArgs('/debug-suite/v1/files/content', {
            path
        });

        return apiFetch<{
            contents: string;
            extension: string;
        }>({
            path: apiPath
        });
    };

    useEffect(() => {
        const loadFileContents = async () => {
            if (!filePath || !open) return;

            try {
                setLoading(true);
                setError(null);
                const response = await fetchFileContents(filePath);
                setEditorContent(response.contents);
            } catch (error) {
                console.error('Error loading file:', error);
                const errorMessage =
                    error instanceof Error
                        ? error.message
                        : __('An error occurred while loading the file.', 'debug-suite');
                setError(errorMessage);
            } finally {
                setLoading(false);
            }
        };

        void loadFileContents();
    }, [filePath, open]);

    const handleContentChange = (value: string | undefined, error: ValidationError | null) => {
        const newContent = value || '';
        setEditorContent(newContent);
        setHasChanges(true);
        if (error) {
            setHasChanges(false);
            setError(error.message);
            return;
        }
        setError(null);
    };

    const handleSave = async () => {
        if (!filePath || !hasChanges) return;

        try {
            setSaving(true);
            setError(null);
            await apiFetch({
                path: '/debug-suite/v1/files/content',
                method: 'POST',
                data: {
                    path: filePath,
                    contents: editorContent
                }
            });

            // Refresh the file contents to ensure we have the latest version
            const response = await fetchFileContents(filePath);
            setEditorContent(response.contents);
            setHasChanges(false);
            onSaveSuccess?.();
            toggle(false);
        } catch (error: unknown) {
            console.error('Error saving file:', error);
            const errorMessage =
                error instanceof Error ? error.message : __('An error occurred while saving the file.', 'debug-suite');
            setError(errorMessage);
        } finally {
            setSaving(false);
        }
    };

    const handleClose = () => {
        setEditorContent('');
        setHasChanges(false);
        setError(null);
        toggle(false);
    };

    return (
        <Modal
            open={open}
            title={fileName}
            onClose={handleClose}
            className="mx-auto max-h-[calc(100svh_-_20px)] max-w-full">
            <Modal.Title>{fileName}</Modal.Title>
            <div className="mt-2">
                <Editor
                    value={editorContent}
                    filename={fileName}
                    height="calc(100svh - 160px)"
                    loading={loading}
                    onChange={handleContentChange}
                    loadingText={__('Loading file editor…', 'debug-suite')}
                />
            </div>
            <Modal.Footer className="flex flex-row-reverse items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                    <button
                        className={classNames(
                            'bg-primary-500 rounded-lg px-5 py-2 text-sm font-semibold text-white shadow-sm transition-colors',
                            'hover:bg-primary-600 focus:ring-primary-400 focus:ring-2 focus:outline-none',
                            'disabled:opacity-60',
                            (saving || !hasChanges) && 'cursor-not-allowed opacity-60'
                        )}
                        disabled={saving || !hasChanges}
                        onClick={handleSave}>
                        {saving ? __('Saving...', 'debug-suite') : __('Save', 'debug-suite')}
                    </button>
                    <button
                        className={classNames(
                            'rounded-lg border border-gray-200 bg-white px-5 py-2 text-sm font-semibold text-gray-700 shadow-sm transition-colors',
                            'focus:ring-primary-400 hover:bg-gray-100 focus:ring-2 focus:outline-none',
                            'dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'
                        )}
                        onClick={handleClose}>
                        {__('Cancel', 'debug-suite')}
                    </button>
                </div>
                {error ? (
                    <div className="flex items-center gap-2">
                        <div className="flex-shrink-0">
                            <svg
                                className="h-5 w-5 text-red-400"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true">
                                <path
                                    fillRule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                    clipRule="evenodd"
                                />
                            </svg>
                        </div>
                        <div className="text-sm text-red-700 dark:text-red-300">{error}</div>
                    </div>
                ) : null}
            </Modal.Footer>
        </Modal>
    );
};

export default FileEditor;
