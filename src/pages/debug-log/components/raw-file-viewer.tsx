/**
 * RawFileViewer component - Display raw log file content using Monaco Editor.
 *
 * @since 1.0.0
 */
import Editor from '@/components/editor';
import Button from '@/components/ui/button';
import { classNames } from '@/utils';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CopyIcon, DownloadIcon, Maximize2Icon, Minimize2Icon, RefreshCwIcon } from 'lucide-react';
import type { RawFileContent } from '../types';

interface RawFileViewerProps {
    content: RawFileContent | null;
    loading: boolean;
    onRefresh: () => void;
}

const RawFileViewer = ({ content, loading, onRefresh }: RawFileViewerProps) => {
    const [copying, setCopying] = useState(false);
    const [isFullscreen, setIsFullscreen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

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

    const toggleFullscreen = async () => {
        if (!containerRef.current) return;

        try {
            if (!isFullscreen) {
                // Enter fullscreen
                if (containerRef.current.requestFullscreen) {
                    await containerRef.current.requestFullscreen();
                } else if ((containerRef.current as any).webkitRequestFullscreen) {
                    await (containerRef.current as any).webkitRequestFullscreen();
                } else if ((containerRef.current as any).msRequestFullscreen) {
                    await (containerRef.current as any).msRequestFullscreen();
                }
            } else {
                // Exit fullscreen
                if (document.exitFullscreen) {
                    await document.exitFullscreen();
                } else if ((document as any).webkitExitFullscreen) {
                    await (document as any).webkitExitFullscreen();
                } else if ((document as any).msExitFullscreen) {
                    await (document as any).msExitFullscreen();
                }
            }
        } catch (error) {
            console.error('Fullscreen toggle failed:', error);
        }
    };

    const handleFullscreenChange = () => {
        const isCurrentlyFullscreen = !!(
            document.fullscreenElement ||
            (document as any).webkitFullscreenElement ||
            (document as any).msFullscreenElement
        );
        setIsFullscreen(isCurrentlyFullscreen);
    };

    // Add fullscreen event listeners and keyboard support
    useEffect(() => {
        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape' && isFullscreen) {
                toggleFullscreen();
            }
        };

        // Listen for fullscreen change events
        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
        document.addEventListener('msfullscreenchange', handleFullscreenChange);

        // Listen for escape key
        if (isFullscreen) {
            document.addEventListener('keydown', handleKeyDown);
        }

        return () => {
            document.removeEventListener('fullscreenchange', handleFullscreenChange);
            document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
            document.removeEventListener('msfullscreenchange', handleFullscreenChange);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [isFullscreen]);

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
                    <Button variant="light" onClick={onRefresh} className="mt-4">
                        <RefreshCwIcon className="mr-2 h-4 w-4" />
                        {__('Retry', 'debug-suite')}
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <div
            ref={containerRef}
            className={classNames(
                'flex flex-1 flex-col overflow-hidden rounded-lg border bg-white',
                isFullscreen && 'h-screen w-screen rounded-none'
            )}
        >
            {/* Toolbar */}
            <div className="rounded-t-lg border-b border-gray-200 bg-gray-50 px-4 py-3">
                <div className="flex items-center justify-between">
                    <p className="text-xs text-gray-500">
                        {content.size} • {__('Modified:', 'debug-suite')} {content.last_modified}
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
                        <Button variant="light" onClick={toggleFullscreen}>
                            {isFullscreen ? (
                                <>
                                    <Minimize2Icon className="mr-2 h-4 w-4" />
                                    {__('Exit Fullscreen', 'debug-suite')}
                                </>
                            ) : (
                                <>
                                    <Maximize2Icon className="mr-2 h-4 w-4" />
                                    {__('Fullscreen', 'debug-suite')}
                                </>
                            )}
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
                    height={isFullscreen ? '100vh' : 'calc(100vh - 120px)'}
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
