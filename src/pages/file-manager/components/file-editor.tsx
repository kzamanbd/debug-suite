/**
 * FileEditor component.
 *
 * A modal file editor with Monaco, modern design, and full i18n support.
 *
 * @since 1.0.0
 */
import Modal from '@/components/ui/modal';
import { cn } from '@/utils/cn';
import MonacoEditor from '@monaco-editor/react';
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from 'react';

// Mapping of file extensions to Monaco languages
const extensionToLanguageMap: Record<string, string> = {
    '.js': 'javascript',
    '.ts': 'typescript',
    '.jsx': 'javascript',
    '.tsx': 'typescript',
    '.py': 'python',
    '.cpp': 'cpp',
    '.html': 'html',
    '.css': 'css',
    '.json': 'json',
    '.xml': 'xml',
    '.yml': 'yaml',
    '.yaml': 'yaml',
    '.md': 'markdown',
    '.sh': 'shell',
    '.sql': 'sql',
    '.java': 'java',
    '.php': 'php',
    '.rb': 'ruby',
    '.go': 'go',
    '.cs': 'csharp',
    '.swift': 'swift',
    '.lock': 'json'
};

/**
 * Get Monaco language from file extension.
 *
 * @since 1.0.0
 * @param filename File name
 * @return Monaco language string
 */
function getLanguageFromExtension(filename: string): string {
    const lastDot = filename.lastIndexOf('.');
    const ext = lastDot !== -1 ? filename.slice(lastDot) : '';
    return extensionToLanguageMap[ext] || 'plaintext';
}

/**
 * Props for the FileEditor component.
 *
 * @since 1.0.0
 */
interface FileEditorProps {
    open: boolean;
    fileContent: string;
    fileName: string;
    readOnly?: boolean;
    toggle: (action: boolean) => void;
}

/**
 * FileEditor modal component.
 *
 * @since 1.0.0
 */
const FileEditor = ({ open, toggle, fileName, fileContent, readOnly = false }: FileEditorProps): JSX.Element => {
    const editorRef = useRef<any>(undefined);

    const handleEditorDidMount = (editor: any) => {
        editorRef.current = editor;
    };

    useEffect(() => {
        if (editorRef.current) {
            editorRef.current.setValue(fileContent);
            editorRef.current.getModel()?.updateOptions({ tabSize: 4, insertSpaces: true });
        }
    }, [fileContent]);

    return (
        <Modal
            open={open}
            title={fileName}
            onClose={() => toggle(false)}
            className="mx-auto max-h-[calc(100svh_-_20px)] max-w-full"
        >
            <div className="mt-2">
                <MonacoEditor
                    height="80vh"
                    defaultValue={fileContent}
                    onMount={handleEditorDidMount}
                    options={{ readOnly }}
                    defaultLanguage={getLanguageFromExtension(fileName)}
                />
            </div>
            <div className="flex items-center justify-end gap-2 border-t border-gray-100 bg-gray-50 px-6 py-3 dark:border-gray-800 dark:bg-gray-900">
                <button
                    className={cn(
                        'bg-primary-500 rounded-lg px-5 py-2 text-sm font-semibold text-white shadow-sm transition-colors',
                        'hover:bg-primary-600 focus:ring-primary-400 focus:ring-2 focus:outline-none',
                        'disabled:opacity-60',
                        readOnly && 'cursor-not-allowed opacity-60'
                    )}
                    disabled={readOnly}
                >
                    {__('Save', 'debug-suite')}
                </button>
                <button
                    className={cn(
                        'rounded-lg border border-gray-200 bg-white px-5 py-2 text-sm font-semibold text-gray-700 shadow-sm transition-colors',
                        'focus:ring-primary-400 hover:bg-gray-100 focus:ring-2 focus:outline-none',
                        'dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'
                    )}
                    onClick={() => toggle(false)}
                >
                    {__('Cancel', 'debug-suite')}
                </button>
            </div>
        </Modal>
    );
};

export default FileEditor;
