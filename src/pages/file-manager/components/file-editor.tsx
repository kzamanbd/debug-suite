/**
 * FileEditor component.
 *
 * A modal file editor using the generic Editor component.
 *
 * @since 1.0.0
 */
import Editor from '@/components/editor';
import Modal from '@/components/ui/modal';
import { classNames } from '@/utils';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Props for the FileEditor component.
 *
 * @since 1.0.0
 */
interface FileEditorProps {
    open: boolean;
    loading: boolean;
    fileContent: string;
    fileName: string;
    readOnly?: boolean;
    toggle: (action: boolean) => void;
    onSave?: (content: string) => void;
}

/**
 * FileEditor modal component.
 *
 * @since 1.0.0
 */
const FileEditor = ({
    open,
    toggle,
    fileName,
    loading,
    fileContent,
    readOnly = false,
    onSave
}: FileEditorProps): JSX.Element => {
    const [editorContent, setEditorContent] = useState(fileContent);
    const [hasChanges, setHasChanges] = useState(false);

    const handleContentChange = (value: string | undefined) => {
        const newContent = value || '';
        setEditorContent(newContent);
        setHasChanges(newContent !== fileContent);
    };

    const handleSave = () => {
        if (onSave && hasChanges) {
            onSave(editorContent);
            setHasChanges(false);
        }
    };

    const handleClose = () => {
        setEditorContent(fileContent);
        setHasChanges(false);
        toggle(false);
    };

    return (
        <Modal
            open={open}
            title={fileName}
            onClose={handleClose}
            className="mx-auto max-h-[calc(100svh_-_20px)] max-w-full"
        >
            <Modal.Title>{fileName}</Modal.Title>
            <div className="mt-2">
                <Editor
                    content={fileContent}
                    filename={fileName}
                    readOnly={readOnly}
                    height="calc(100svh - 190px)"
                    loading={loading}
                    onChange={handleContentChange}
                    loadingText={__('Loading file editor…', 'debug-suite')}
                />
            </div>
            <Modal.Footer className="flex items-center justify-end gap-2">
                <button
                    className={classNames(
                        'bg-primary-500 rounded-lg px-5 py-2 text-sm font-semibold text-white shadow-sm transition-colors',
                        'hover:bg-primary-600 focus:ring-primary-400 focus:ring-2 focus:outline-none',
                        'disabled:opacity-60',
                        (readOnly || !hasChanges) && 'cursor-not-allowed opacity-60'
                    )}
                    disabled={readOnly || !hasChanges}
                    onClick={handleSave}
                >
                    {__('Save', 'debug-suite')}
                </button>
                <button
                    className={classNames(
                        'rounded-lg border border-gray-200 bg-white px-5 py-2 text-sm font-semibold text-gray-700 shadow-sm transition-colors',
                        'focus:ring-primary-400 hover:bg-gray-100 focus:ring-2 focus:outline-none',
                        'dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'
                    )}
                    onClick={handleClose}
                >
                    {__('Cancel', 'debug-suite')}
                </button>
            </Modal.Footer>
        </Modal>
    );
};

export default FileEditor;
