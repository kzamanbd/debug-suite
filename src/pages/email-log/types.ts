/**
 * Types for Email Log components.
 *
 * @since 1.0.0
 */

export interface EmailLogEntry {
    id: number;
    sent_date: string;
    receiver: string;
    subject: string;
    message?: string;
    headers?: string;
    attachments?: string;
    error?: string | null;
    status: 'success' | 'failed';
    created_at: string;
}

export interface EmailLogResponse {
    entries: EmailLogEntry[];
    total: number;
    total_pages: number;
    current_page: number;
    per_page: number;
    has_more: boolean;
}

export interface EmailLogFilters {
    receiver: string;
    status: 'all' | 'success' | 'failed';
    search: string;
    sortBy: string;
    sortOrder: 'asc' | 'desc';
    perPage: number;
}

export interface EmailLogStats {
    total_emails: number;
    successful: number;
    failed: number;
    success_rate: string;
}

export interface BulkAction {
    action: 'delete' | 'resend' | 'mark_read';
    selected_ids: number[];
}

export interface EmailLogActions {
    onReceiverChange: (receiver: string) => void;
    onStatusChange: (status: string) => void;
    onSearchChange: (term: string) => void;
    onSortChange: (sortBy: string, sortOrder: string) => void;
    onPerPageChange: (perPage: number) => void;
    onPageChange: (page: number) => void;
    onRefresh: () => void;
    onBulkAction: (action: BulkAction) => void;
    onSelectAll: (selected: boolean) => void;
    onSelectItem: (id: number, selected: boolean) => void;
}
