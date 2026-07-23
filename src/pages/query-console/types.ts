export type SplitOrientation = 'horizontal' | 'vertical';

export interface ExecuteResult {
    output: string;
    dump: string;
    execution_time: string;
}

export interface ConsoleError {
    message: string;
    trace?: string;
    input?: string;
}

export interface Snippet {
    id: string;
    title: string;
    code: string;
}

export interface ConsoleSettings {
    window_split: SplitOrientation;
    snippets: Snippet[];
}

export interface HistoryEntry {
    id: string;
    code: string;
    ranAt: number;
}
