/**
 * Types for Debug Log components.
 *
 * @since 1.0.0
 */

export interface LogEntry {
    timestamp: string;
    level: 'critical' | 'error' | 'warning' | 'notice' | 'info' | 'debug';
    message: string;
    file_path?: string;
    line?: number;
    line_number?: number;
    stack_trace?: string;
    has_stack_trace?: boolean;
    raw_line?: string;
}

export interface LogResponse {
    entries: LogEntry[];
    total: number;
    total_pages: number;
    current_page: number;
    per_page: number;
    has_more: boolean;
}

export interface LogStats {
    total_entries: number;
    file_size: string;
    last_modified: string;
    level_counts: Record<string, number>;
}

export interface LogFilters {
    level: string;
    search: string;
    sortBy: string;
    sortOrder: string;
    perPage: number;
}

export interface InfiniteScrollState {
    page: number;
    hasMore: boolean;
    isLoadingMore: boolean;
}

export type ViewMode = 'parsed' | 'raw';

export interface RawFileContent {
    content: string;
    filename: string;
    size: string;
    last_modified: string;
}

export interface LogActions {
    onLevelChange: (level: string) => void;
    onSearchChange: (term: string) => void;
    onSortChange: (sortBy: string, sortOrder: string) => void;
    onPerPageChange: (perPage: number) => void;
    onRefresh: () => void;
    onClear: () => void;
    onExport: (format: 'json' | 'csv' | 'txt') => void;
    onLoadMore: () => void;
}
