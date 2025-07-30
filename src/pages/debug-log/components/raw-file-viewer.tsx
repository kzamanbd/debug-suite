/**
 * RawFileViewer component - Display raw log file content using Monaco Editor.
 *
 * @since 1.0.0
 */
import Button from '@/components/base/button';
import Editor from '@/components/editor';
import { classNames } from '@/utils';
import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { RefreshCwIcon } from 'lucide-react';
import type { RawFileContent } from '../types';

interface RawFileViewerProps {
    content: RawFileContent | null;
    loading: boolean;
    onRefresh: () => void;
}

const RawFileViewer = ({ content, loading, onRefresh }: RawFileViewerProps) => {
    const containerRef = useRef<HTMLDivElement>(null);

    if (loading && !content) {
        return (
            <div className="flex flex-1 items-center justify-center bg-white">
                <p className="text-sm text-gray-500">{__('Loading...', 'debug-suite')}</p>
            </div>
        );
    }

    if (!content) {
        return (
            <div className="flex flex-1 items-center justify-center bg-white">
                <div className="text-center">
                    <p className="text-sm text-gray-500">{__('No file content available.', 'debug-suite')}</p>
                    <Button onClick={onRefresh} className="mt-4">
                        <RefreshCwIcon className="mr-2 size-4" />
                        {__('Retry', 'debug-suite')}
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <div
            ref={containerRef}
            className={classNames('flex flex-1 flex-col overflow-hidden rounded-lg border bg-white')}>
            {/* Toolbar */}
            <div className="rounded-t-lg border-b border-gray-200 bg-gray-50 px-4 py-3">
                <div className="flex items-center justify-between">
                    <p className="text-xs font-bold text-gray-500">
                        {content.size} • {__('Modified:', 'debug-suite')} {content.last_modified}
                    </p>
                </div>
            </div>

            {/* Editor */}
            <div className="flex-1">
                <Editor
                    readOnly
                    value={content.content}
                    filename={content.filename}
                    loading={loading}
                    height={'calc(100vh - 120px)'}
                />
            </div>
        </div>
    );
};

export default RawFileViewer;
