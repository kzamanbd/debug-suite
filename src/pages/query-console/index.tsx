import Editor from '@/components/editor';
import { Button } from '@/components/ui';
import { classNames } from '@/utils';
import { Fill } from '@wordpress/components';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Columns2, Play, Rows2 } from 'lucide-react';
import OutputPane from './components/output-pane';
import SnippetsMenu from './components/snippets-menu';
import SplitLayout from './components/split-layout';
import { DEFAULT_CODE } from './constants';
import useConsoleApi from './hooks/use-console-api';
import useConsoleHistory from './hooks/use-console-history';
import type { ConsoleError, ConsoleSettings, ExecuteResult, SplitOrientation } from './types';

const QueryConsole = ({ className }: { className?: string }) => {
    const { execute, getSettings, saveSettings } = useConsoleApi();
    const { push } = useConsoleHistory();

    const [code, setCode] = useState(DEFAULT_CODE);
    const [result, setResult] = useState<ExecuteResult | null>(null);
    const [error, setError] = useState<ConsoleError | null>(null);
    const [loading, setLoading] = useState(false);
    const [settings, setSettings] = useState<ConsoleSettings>({ window_split: 'vertical', snippets: [] });

    const codeRef = useRef(code);
    codeRef.current = code;

    useEffect(() => {
        getSettings()
            .then(setSettings)
            .catch(() => undefined);
    }, [getSettings]);

    const run = useCallback(async () => {
        const input = codeRef.current;
        if (!input.trim()) return;
        setLoading(true);
        setError(null);
        try {
            const res = await execute(input);
            setResult(res);
            push(input);
        } catch (e) {
            const err = e as { message?: string; data?: { trace?: string; input?: string } };
            setResult(null);
            setError({ message: err.message ?? 'Error', trace: err.data?.trace, input: err.data?.input });
        } finally {
            setLoading(false);
        }
    }, [execute, push]);

    const persistSettings = useCallback(
        (patch: Partial<ConsoleSettings>) => {
            setSettings((prev) => {
                const next = { ...prev, ...patch };
                saveSettings(patch).catch(() => undefined);
                return next;
            });
        },
        [saveSettings]
    );

    const toggleSplit = useCallback(() => {
        const next: SplitOrientation = settings.window_split === 'vertical' ? 'horizontal' : 'vertical';
        persistSettings({ window_split: next });
    }, [settings.window_split, persistSettings]);

    const insertSnippet = useCallback((snippetCode: string) => setCode(snippetCode), []);

    const saveSnippet = useCallback(
        (title: string) => {
            const snippet = { id: `${Date.now()}`, title, code: codeRef.current };
            persistSettings({ snippets: [...settings.snippets, snippet] });
        },
        [settings.snippets, persistSettings]
    );

    const deleteSnippet = useCallback(
        (id: string) => persistSettings({ snippets: settings.snippets.filter((s) => s.id !== id) }),
        [settings.snippets, persistSettings]
    );

    // Ctrl/Cmd+Enter to run.
    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                e.preventDefault();
                run();
            }
        };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [run]);

    return (
        <div className={classNames('flex h-full min-h-0 flex-col', className)}>
            <Fill name="console-logs-actions">
                <Button size="sm" variant="ghost" onClick={toggleSplit} title={__('Toggle split', 'debug-suite')}>
                    {settings.window_split === 'vertical' ? <Rows2 size={18} /> : <Columns2 size={18} />}
                </Button>
                <Button size="sm" variant="secondary" onClick={run} disabled={loading}>
                    <Play size={16} />
                    <span>{__('Run', 'debug-suite')}</span>
                </Button>
            </Fill>

            <SplitLayout
                orientation={settings.window_split}
                first={
                    <div className="flex h-full min-h-0">
                        <SnippetsMenu
                            snippets={settings.snippets}
                            onInsert={insertSnippet}
                            onSave={saveSnippet}
                            onDelete={deleteSnippet}
                        />
                        <div className="min-w-0 flex-1">
                            <Editor
                                value={code}
                                filename="console.php"
                                height="100%"
                                onChange={(value) => setCode(value ?? '')}
                            />
                        </div>
                    </div>
                }
                second={<OutputPane result={result} error={error} loading={loading} />}
            />
        </div>
    );
};

export default QueryConsole;
