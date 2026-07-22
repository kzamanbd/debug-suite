import { useCallback, useState } from '@wordpress/element';
import { HISTORY_KEY, MAX_HISTORY } from '../constants';
import type { HistoryEntry } from '../types';

const read = (): HistoryEntry[] => {
    try {
        const raw = localStorage.getItem(HISTORY_KEY);
        return raw ? (JSON.parse(raw) as HistoryEntry[]) : [];
    } catch {
        return [];
    }
};

const useConsoleHistory = () => {
    const [history, setHistory] = useState<HistoryEntry[]>(read);

    const persist = useCallback((entries: HistoryEntry[]) => {
        setHistory(entries);
        localStorage.setItem(HISTORY_KEY, JSON.stringify(entries));
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
            localStorage.setItem(HISTORY_KEY, JSON.stringify(next));
            return next;
        });
    }, []);

    const clear = useCallback(() => persist([]), [persist]);

    return { history, push, clear };
};

export default useConsoleHistory;
