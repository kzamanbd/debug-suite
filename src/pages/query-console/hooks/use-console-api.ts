import apiFetch from '@wordpress/api-fetch';
import { useCallback } from '@wordpress/element';
import type { ConsoleSettings, ExecuteResult } from '../types';

interface UseConsoleApi {
    execute: (input: string) => Promise<ExecuteResult>;
    getSettings: () => Promise<ConsoleSettings>;
    saveSettings: (patch: Partial<ConsoleSettings>) => Promise<ConsoleSettings>;
}

const useConsoleApi = (): UseConsoleApi => {
    const execute = useCallback(async (input: string) => {
        return await apiFetch<ExecuteResult>({
            path: '/debug-suite/v1/console/execute',
            method: 'POST',
            data: { input }
        });
    }, []);

    const getSettings = useCallback(async () => {
        return await apiFetch<ConsoleSettings>({
            path: '/debug-suite/v1/console/settings'
        });
    }, []);

    const saveSettings = useCallback(async (patch: Partial<ConsoleSettings>) => {
        return await apiFetch<ConsoleSettings>({
            path: '/debug-suite/v1/console/settings',
            method: 'POST',
            data: patch
        });
    }, []);

    return { execute, getSettings, saveSettings };
};

export default useConsoleApi;
