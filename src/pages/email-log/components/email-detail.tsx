/**
 * EmailDetail - Modal component for displaying detailed email information.
 *
 * @since 1.0.0
 */
import {
    DateTimeHtml,
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui';
import { __ } from '@wordpress/i18n';
import { AlertCircle, Calendar, CheckCircle, FileText, Mail, MessageSquare, Paperclip } from 'lucide-react';
import type { EmailLogEntry } from '../types';

interface EmailDetailProps {
    isOpen: boolean;
    onClose: () => void;
    entry: EmailLogEntry | null;
    loading?: boolean;
}

interface Attachment {
    filename?: string;
    content_type?: string;
}

type Headers = Record<string, string | number | boolean | null | undefined>;

const EmailDetail = ({ isOpen, onClose, entry, loading = false }: EmailDetailProps) => {
    if (!entry) {
        return null;
    }

    // Parse attachments if they exist
    let attachments: Attachment[] = [];
    try {
        attachments = entry.attachments ? (JSON.parse(entry.attachments) as Attachment[]) : [];
    } catch {
        attachments = [];
    }

    // Parse headers if they exist
    let headers: Headers = {};
    try {
        headers = entry.headers ? (JSON.parse(entry.headers) as Headers) : {};
    } catch {
        headers = {};
    }

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="flex max-h-[90vh] max-w-3xl flex-col gap-0 p-0 sm:max-w-3xl">
                <DialogHeader className="flex-none border-b p-6 pb-4">
                    <DialogTitle className="flex items-center gap-2 text-xl">
                        <Mail className="text-primary h-5 w-5" />
                        {__('Email Details', 'debug-suite')}
                    </DialogTitle>
                </DialogHeader>

                <div className="flex-1 overflow-y-auto p-6 pt-4">
                    <DialogDescription className="sr-only">Detailed view of the sent email.</DialogDescription>
                    {loading ? (
                        <div className="flex items-center justify-center py-8">
                            <div className="border-primary h-8 w-8 animate-spin rounded-full border-2 border-t-transparent"></div>
                            <span className="ml-3 text-gray-600">{__('Loading email details...', 'debug-suite')}</span>
                        </div>
                    ) : (
                        <div className="space-y-6">
                            {/* Status and Basic Info */}
                            <div className="rounded-lg border bg-gray-50 p-4">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div className="flex items-center gap-2">
                                        <Calendar className="h-4 w-4 text-gray-500" />
                                        <span className="text-sm font-medium text-gray-700">
                                            {__('Sent:', 'debug-suite')}
                                        </span>
                                        <span className="text-sm text-gray-900">
                                            <DateTimeHtml date={entry.sent_date} />
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {entry.error ? (
                                            <>
                                                <AlertCircle className="h-4 w-4 text-red-500" />
                                                <span className="inline-flex items-center rounded-full border border-red-200 bg-red-100 px-2 py-1 text-xs font-medium text-red-800">
                                                    {__('Failed', 'debug-suite')}
                                                </span>
                                            </>
                                        ) : (
                                            <>
                                                <CheckCircle className="h-4 w-4 text-green-500" />
                                                <span className="inline-flex items-center rounded-full border border-green-200 bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                                                    {__('Success', 'debug-suite')}
                                                </span>
                                            </>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Email Information */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold text-gray-900">
                                    {__('Email Information', 'debug-suite')}
                                </h3>

                                <div className="space-y-3">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            {__('To:', 'debug-suite')}
                                        </label>
                                        <div className="mt-1 rounded-md border bg-gray-50 p-3 text-sm text-gray-900">
                                            {entry.receiver}
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            {__('Subject:', 'debug-suite')}
                                        </label>
                                        <div className="mt-1 rounded-md border bg-gray-50 p-3 text-sm text-gray-900">
                                            {entry.subject}
                                        </div>
                                    </div>

                                    {entry.message && (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                {__('Message:', 'debug-suite')}
                                            </label>
                                            <div className="mt-1 rounded-md border bg-gray-50 p-3">
                                                {/* Check if content is HTML */}
                                                {entry.message.includes('<') ? (
                                                    <div
                                                        className="prose prose-sm max-w-none text-gray-900"
                                                        dangerouslySetInnerHTML={{ __html: entry.message }}
                                                    />
                                                ) : (
                                                    <pre className="font-sans text-sm whitespace-pre-wrap text-gray-900">
                                                        {entry.message}
                                                    </pre>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Attachments */}
                            {attachments.length > 0 && (
                                <div className="space-y-4">
                                    <h3 className="flex items-center gap-2 text-lg font-semibold text-gray-900">
                                        <Paperclip className="h-5 w-5" />
                                        {__('Attachments', 'debug-suite')}
                                        <span className="text-sm font-normal text-gray-500">
                                            ({attachments.length})
                                        </span>
                                    </h3>
                                    <div className="space-y-2">
                                        {attachments.map((attachment, index: number) => (
                                            <div
                                                key={index}
                                                className="flex items-center gap-3 rounded-md border bg-gray-50 p-3">
                                                <FileText className="h-4 w-4 text-gray-500" />
                                                <div className="min-w-0 flex-1">
                                                    <div className="truncate text-sm font-medium text-gray-900">
                                                        {attachment.filename || `Attachment ${index + 1}`}
                                                    </div>
                                                    {attachment.content_type && (
                                                        <div className="text-xs text-gray-500">
                                                            {attachment.content_type}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Headers */}
                            {Object.keys(headers).length > 0 && (
                                <div className="space-y-4">
                                    <h3 className="flex items-center gap-2 text-lg font-semibold text-gray-900">
                                        <MessageSquare className="h-5 w-5" />
                                        {__('Email Headers', 'debug-suite')}
                                    </h3>
                                    <div className="rounded-md border bg-gray-50 p-3">
                                        <div className="space-y-2">
                                            {Object.keys(headers).map((key) => {
                                                const value = headers[key];
                                                return (
                                                    <div key={key} className="flex">
                                                        <div className="w-32 flex-shrink-0 text-sm font-medium text-gray-700">
                                                            {key}:
                                                        </div>
                                                        <div className="flex-1 text-sm break-all text-gray-900">
                                                            {String(value)}
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Error Information */}
                            {entry.error && (
                                <div className="space-y-4">
                                    <h3 className="flex items-center gap-2 text-lg font-semibold text-red-700">
                                        <AlertCircle className="h-5 w-5" />
                                        {__('Error Information', 'debug-suite')}
                                    </h3>
                                    <div className="rounded-md border border-red-200 bg-red-50 p-3">
                                        <div className="text-sm whitespace-pre-wrap text-red-900">{entry.error}</div>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>

                <DialogFooter className="flex flex-none justify-end border-t p-6">
                    <button
                        type="button"
                        onClick={onClose}
                        className="focus:ring-primary rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-offset-2 focus:outline-none">
                        {__('Close', 'debug-suite')}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
};

export default EmailDetail;
