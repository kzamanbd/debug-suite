/**
 * Types for API Log components.
 *
 * @since 1.2.0
 */

export interface ApiLogEntry {
    id: number;
    method: string;
    route: string;
    url?: string;
    request_headers?: Record<string, string | string[]>;
    request_body?: Record<string, unknown>;
    request_params?: Record<string, unknown>;
    response_status: number;
    response_headers?: Record<string, string>;
    response_body?: string;
    duration: number;
    user_id: number;
    user_ip?: string;
    source: string;
    created_at: string;
}

export interface ApiLogResponse {
    entries: ApiLogEntry[];
    total: number;
    total_pages: number;
    current_page: number;
    per_page: number;
    has_more: boolean;
}

export interface ApiLogFilters {
    method: string;
    status: string;
    route: string;
    search: string;
    sortBy: string;
    sortOrder: 'asc' | 'desc';
    perPage: number;
}

export interface ApiLogStats {
    total_requests: number;
    successful: number;
    failed: number;
    avg_duration: number;
}

export interface BulkAction {
    action: 'delete';
    selected_ids: number[];
}
