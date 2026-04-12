/**
 * ApiDetail - Modal component for displaying detailed API request/response information.
 *
 * @since 1.2.0
 */
import { DateTimeHtml } from '@/components/base';
import ContentTabs from '@/components/base/content-tabs';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog';
import { __ } from '@wordpress/i18n';
import { Calendar, Clock, Globe, User } from 'lucide-react';
import type { ApiLogEntry } from '../types';

interface ApiDetailProps {
    isOpen: boolean;
    onClose: () => void;
    entry: ApiLogEntry | null;
    loading?: boolean;
}

const methodColors: Record<string, string> = {
    GET: 'bg-green-100 text-green-800 border-green-200',
    POST: 'bg-blue-100 text-blue-800 border-blue-200',
    PUT: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    PATCH: 'bg-purple-100 text-purple-800 border-purple-200',
    DELETE: 'bg-red-100 text-red-800 border-red-200'
};

const getStatusColor = (status: number): string => {
    if (status >= 200 && status < 300) return 'bg-green-100 text-green-800 border-green-200';
    if (status >= 300 && status < 400) return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    if (status >= 400 && status < 500) return 'bg-orange-100 text-orange-800 border-orange-200';
    if (status >= 500) return 'bg-red-100 text-red-800 border-red-200';
    return 'bg-gray-100 text-gray-800 border-gray-200';
};

const formatJson = (data: unknown): string => {
    if (!data) return '';
    if (typeof data === 'string') {
        try {
            return JSON.stringify(JSON.parse(data), null, 2);
        } catch {
            return data;
        }
    }
    return JSON.stringify(data, null, 2);
};

const JsonBlock = ({ data, emptyMessage }: { data: unknown; emptyMessage: string }) => {
    const formatted = formatJson(data);
    if (!formatted || formatted === '{}' || formatted === '[]') {
        return <div className="py-4 text-center text-sm text-gray-400">{emptyMessage}</div>;
    }
    return (
        <pre className="max-h-96 overflow-auto rounded-md border bg-gray-900 p-4 font-mono text-xs leading-relaxed text-green-400">
            {formatted}
        </pre>
    );
};

const ApiDetail = ({ isOpen, onClose, entry, loading = false }: ApiDetailProps) => {
    if (!isOpen) {
        return null;
    }

    const tabs = [
        {
            key: 'request-headers',
            label: __('Request Headers', 'debug-suite'),
            content: <JsonBlock data={entry?.request_headers} emptyMessage={__('No request headers', 'debug-suite')} />
        },
        {
            key: 'request-body',
            label: __('Request Body', 'debug-suite'),
            content: <JsonBlock data={entry?.request_body} emptyMessage={__('No request body', 'debug-suite')} />
        },
        {
            key: 'request-params',
            label: __('Request Params', 'debug-suite'),
            content: <JsonBlock data={entry?.request_params} emptyMessage={__('No query parameters', 'debug-suite')} />
        },
        {
            key: 'response-headers',
            label: __('Response Headers', 'debug-suite'),
            content: (
                <JsonBlock data={entry?.response_headers} emptyMessage={__('No response headers', 'debug-suite')} />
            )
        },
        {
            key: 'response-body',
            label: __('Response Body', 'debug-suite'),
            content: <JsonBlock data={entry?.response_body} emptyMessage={__('No response body', 'debug-suite')} />
        }
    ];

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="flex max-h-[90vh] max-w-5xl flex-col gap-0 p-0 sm:max-w-5xl">
                <DialogHeader className="flex-none border-b p-6 pb-4">
                    <DialogTitle className="flex items-center gap-2 text-xl">
                        <Globe className="text-primary h-5 w-5" />
                        {__('API Request Details', 'debug-suite')}
                    </DialogTitle>
                    <DialogDescription className="sr-only">Detailed view of the API logs.</DialogDescription>
                </DialogHeader>

                <div className="flex-1 overflow-y-auto p-6 pt-4">
                    {loading ? (
                        <div className="flex items-center justify-center py-8">
                            <div className="border-primary h-8 w-8 animate-spin rounded-full border-2 border-t-transparent"></div>
                            <span className="ml-3 text-gray-600">
                                {__('Loading request details...', 'debug-suite')}
                            </span>
                        </div>
                    ) : entry ? (
                        <div className="space-y-6">
                            {/* Overview */}
                            <div className="rounded-lg border bg-gray-50 p-4">
                                <div className="mb-3 flex items-center gap-3">
                                    <span
                                        className={`inline-flex items-center rounded border px-2 py-0.5 text-xs font-semibold ${methodColors[entry.method] || 'bg-gray-100 text-gray-800'}`}>
                                        {entry.method}
                                    </span>
                                    <code className="flex-1 truncate text-sm text-gray-900">{entry.route}</code>
                                    <span
                                        className={`inline-flex items-center rounded border px-2 py-0.5 text-xs font-medium ${getStatusColor(entry.response_status)}`}>
                                        {entry.response_status}
                                    </span>
                                </div>
                                <div className="grid grid-cols-2 gap-3 text-sm md:grid-cols-4">
                                    <div className="flex items-center gap-1.5 text-gray-600">
                                        <Clock className="h-3.5 w-3.5" />
                                        <span>{entry.duration.toFixed(1)}ms</span>
                                    </div>
                                    <div className="flex items-center gap-1.5 text-gray-600">
                                        <Calendar className="h-3.5 w-3.5" />
                                        <DateTimeHtml date={entry.created_at} />
                                    </div>
                                    {entry.user_id > 0 && (
                                        <div className="flex items-center gap-1.5 text-gray-600">
                                            <User className="h-3.5 w-3.5" />
                                            <span>
                                                {__('User', 'debug-suite')} #{entry.user_id}
                                            </span>
                                        </div>
                                    )}
                                    {entry.user_ip && (
                                        <div className="flex items-center gap-1.5 text-gray-600">
                                            <Globe className="h-3.5 w-3.5" />
                                            <span>{entry.user_ip}</span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Tabbed Content */}
                            <ContentTabs tabs={tabs} />
                        </div>
                    ) : null}
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

export default ApiDetail;
