import { classNames } from '@/utils';
import { __ } from '@wordpress/i18n';
import type { ConsoleError, ExecuteResult } from '../types';

interface OutputPaneProps {
    result: ExecuteResult | null;
    error: ConsoleError | null;
    loading: boolean;
}

const OutputPane = ({ result, error, loading }: OutputPaneProps) => {
    return (
        <div className="bg-background flex h-full min-h-0 flex-col overflow-y-auto p-3 font-mono text-sm">
            {loading && <div className="text-muted-foreground">{__('Running…', 'debug-suite')}</div>}

            {!loading && error && (
                <div className="text-red-600">
                    <div className="font-semibold">{error.message}</div>
                    {error.trace && (
                        <details className="mt-2">
                            <summary className="cursor-pointer select-none">{__('Stack trace', 'debug-suite')}</summary>
                            <pre className="mt-1 whitespace-pre-wrap text-xs opacity-80">{error.trace}</pre>
                        </details>
                    )}
                </div>
            )}

            {!loading && !error && result && (
                <div className="flex flex-col gap-3">
                    {result.output && <pre className="whitespace-pre-wrap">{result.output}</pre>}
                    {result.dump && (
                        <div
                            className={classNames('debug-suite-dump')}
                            // dump HTML comes from our controlled server-side HtmlDumper (admin only).
                            dangerouslySetInnerHTML={{ __html: result.dump }}
                        />
                    )}
                    <div className="text-muted-foreground text-xs">
                        {__('Executed in', 'debug-suite')} {result.execution_time}s
                    </div>
                </div>
            )}

            {!loading && !error && !result && (
                <div className="text-muted-foreground">{__('Output will appear here.', 'debug-suite')}</div>
            )}
        </div>
    );
};

export default OutputPane;
