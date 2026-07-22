import { useCallback, useState } from '@wordpress/element';
import { HISTORY_KEY, MAX_HISTORY } from '../constants';
import type { HistoryEntry } from '../types';

const isHistoryEntry = (value: unknown): value is HistoryEntry => {
    if (typeof value !== 'object' || value === null) return false;
    const entry = value as Record<string, unknown>;
    return (
        typeof entry.id === 'string' &&
        typeof entry.code === 'string' &&
        typeof entry.ranAt === 'number'
    );
};

const read = (): HistoryEntry[] => {
    try {
        const raw = localStorage.getItem(HISTORY_KEY);
        if (!raw) return [];
        const parsed: unknown = JSON.parse(raw);
        if (!Array.isArray(parsed)) return [];
        return parsed.filter(isHistoryEntry);
    } catch {
        return [];
    }
};

const useConsoleHistory = () => {
    const [history, setHistory] = useState<HistoryEntry[]>(read);

    const persist = useCallback((entries: HistoryEntry[]) => {
        setHistory(entries);
        try {
            localStorage.setItem(HISTORY_KEY, JSON.stringify(entries));
        } catch {
            /* storage unavailable / quota exceeded — history is best-effort */
        }
    }, []);

    const push = useCallback((code: string) => {
        const trimmed = code.trim();
        if (!trimmed) return;
        setHistory((prev) => {
            if (prev[0]?.code === trimmed) return prev;
            const entry: HistoryEntry = {
                id: `${prev.length}-${trimmed.length}-${trimmed.slice(0, 8)}`,
                code: trimmed,
                ranAt: Date.now()
            };
            const next = [entry, ...prev].slice(0, MAX_HISTORY);
            try {
                localStorage.setItem(HISTORY_KEY, JSON.stringify(next));
            } catch {
                /* storage unavailable / quota exceeded — history is best-effort */
            }
            return next;
        });
    }, []);

    const clear = useCallback(() => persist([]), [persist]);

    return { history, push, clear };
};

export default useConsoleHistory;
