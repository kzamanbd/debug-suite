/**
 * Types for Debug Log components.
 *
 * @since 1.0.0
 */

export interface LogEntry {
    id: number;
    timestamp: string;
    level: 'critical' | 'error' | 'warning' | 'notice' | 'info' | 'debug';
    message: string;
    file?: string;
    line?: number;
    stack_trace?: string;
    has_stack_trace?: boolean;
}

export interface LogFile {
    name: string;
    path: string;
    size: string;
    size_bytes: number;
    modified: string;
    type: string;
    is_current: boolean;
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
    page: number;
    perPage: number;
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
    onPageChange: (page: number) => void;
    onPerPageChange: (perPage: number) => void;
    onRefresh: () => void;
    onClear: () => void;
    onExport: (format: 'json' | 'csv' | 'txt') => void;
}
