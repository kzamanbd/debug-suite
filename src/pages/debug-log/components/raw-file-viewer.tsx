/**
 * RawFileViewer component - Display raw log file content using Monaco Editor.
 *
 * @since 1.0.0
 */
import Editor from '@/components/editor';
import Button from '@/components/ui/button';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CopyIcon, DownloadIcon, RefreshCwIcon } from 'lucide-react';
import type { RawFileContent } from '../types';

interface RawFileViewerProps {
    content: RawFileContent | null;
    loading: boolean;
    onRefresh: () => void;
}

const RawFileViewer = ({ content, loading, onRefresh }: RawFileViewerProps) => {
    const [copying, setCopying] = useState(false);

    const handleCopy = async () => {
        if (!content?.content) return;

        try {
            setCopying(true);
            await navigator.clipboard.writeText(content.content);

            // Show temporary success feedback
            setTimeout(() => {
                setCopying(false);
            }, 2000);
        } catch (error) {
            console.error('Failed to copy content:', error);
            setCopying(false);
        }
    };

    const handleDownload = () => {
        if (!content) return;

        const blob = new Blob([content.content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = content.filename || 'debug.log';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    };

    if (!content) {
        return (
            <div className="flex flex-1 items-center justify-center bg-white">
                <div className="text-center">
                    <p className="text-sm text-gray-500">{__('No file content available.', 'debug-suite')}</p>
                    <Button variant="light" onClick={onRefresh} className="mt-4">
                        <RefreshCwIcon className="mr-2 h-4 w-4" />
                        {__('Retry', 'debug-suite')}
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <div className="flex flex-1 flex-col bg-white">
            {/* Toolbar */}
            <div className="border-b border-gray-200 bg-gray-50 px-4 py-3">
                <div className="flex items-center justify-between">
                    <p className="text-xs text-gray-500">
                        {content.size} • {__('Modified:', 'debug-suite')}{' '}
                        {new Date(content.last_modified).toLocaleString()}
                    </p>
                    <div className="flex items-center space-x-2">
                        <Button variant="light" onClick={handleCopy} disabled={copying}>
                            <CopyIcon className="mr-2 h-4 w-4" />
                            {copying ? __('Copied!', 'debug-suite') : __('Copy', 'debug-suite')}
                        </Button>
                        <Button variant="light" onClick={handleDownload}>
                            <DownloadIcon className="mr-2 h-4 w-4" />
                            {__('Download', 'debug-suite')}
                        </Button>
                    </div>
                </div>
            </div>

            {/* Editor */}
            <div className="flex-1">
                <Editor
                    content={content.content}
                    filename={content.filename}
                    loading={loading}
                    readOnly={true}
                    height="calc(100vh - 120px)" // Adjust height based on toolbar
                    showLoadingSpinner={false}
                    options={{
                        wordWrap: 'on',
                        scrollBeyondLastLine: true,
                        minimap: { enabled: true },
                        lineNumbers: 'on',
                        folding: false,
                        fontSize: 12,
                        renderWhitespace: 'selection'
                    }}
                />
            </div>
        </div>
    );
};

export default RawFileViewer;
