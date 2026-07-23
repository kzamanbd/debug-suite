import { Button } from '@/components/ui';
import { Popover } from '@base-ui/react/popover';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ChevronDown, Code2, Plus, Trash2 } from 'lucide-react';
import type { Snippet } from '../types';

interface SnippetsMenuProps {
    snippets: Snippet[];
    onInsert: (code: string) => void;
    onSave: (title: string) => void;
    onDelete: (id: string) => void;
}

/**
 * Creatable snippets dropdown for the console header.
 *
 * Selecting a snippet loads its code into the editor. Typing a title that
 * doesn't already exist reveals a "Create" row, which saves the current
 * editor buffer under that title.
 */
const SnippetsMenu = ({ snippets, onInsert, onSave, onDelete }: SnippetsMenuProps) => {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');

    const trimmed = query.trim();

    const filtered = useMemo(() => {
        if (!trimmed) return snippets;
        const needle = trimmed.toLowerCase();
        return snippets.filter((snippet) => snippet.title.toLowerCase().includes(needle));
    }, [snippets, trimmed]);

    // An exact title match means "create" would duplicate — select the existing one instead.
    const canCreate = useMemo(
        () => trimmed.length > 0 && !snippets.some((s) => s.title.toLowerCase() === trimmed.toLowerCase()),
        [snippets, trimmed]
    );

    const close = () => {
        setOpen(false);
        setQuery('');
    };

    const handleInsert = (snippet: Snippet) => {
        onInsert(snippet.code);
        close();
    };

    const handleCreate = () => {
        if (!canCreate) return;
        onSave(trimmed);
        close();
    };

    const handleKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        if (canCreate) {
            handleCreate();
        } else if (filtered.length === 1) {
            handleInsert(filtered[0]);
        }
    };

    return (
        <Popover.Root open={open} onOpenChange={setOpen}>
            <Popover.Trigger render={<Button size="sm" variant="ghost" />}>
                <Code2 size={16} />
                <span>{__('Snippets', 'debug-suite')}</span>
                <ChevronDown size={14} />
            </Popover.Trigger>

            <Popover.Portal>
                <div className="debug-suite-root-app">
                    <Popover.Positioner className="z-[99999] outline-none" sideOffset={6} align="end">
                        <Popover.Popup className="w-72 rounded-lg border border-gray-200 bg-white p-1 text-sm text-gray-900 shadow-lg outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
                            <input
                                autoFocus
                                value={query}
                                onChange={(event) => setQuery(event.target.value)}
                                onKeyDown={handleKeyDown}
                                placeholder={__('Search or name a new snippet…', 'debug-suite')}
                                className="mb-1 w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm outline-none focus-visible:border-primary dark:border-neutral-700 dark:bg-neutral-900"
                            />

                            <ul className="max-h-64 overflow-y-auto">
                                {filtered.length === 0 && !canCreate && (
                                    <li className="text-muted-foreground px-2 py-1.5 text-xs">
                                        {snippets.length === 0
                                            ? __('No snippets saved.', 'debug-suite')
                                            : __('No matches.', 'debug-suite')}
                                    </li>
                                )}

                                {filtered.map((snippet) => (
                                    <li key={snippet.id} className="flex items-center gap-1">
                                        <button
                                            type="button"
                                            className="hover:bg-secondary flex-1 truncate rounded-md px-2 py-1.5 text-left text-sm"
                                            onClick={() => handleInsert(snippet)}>
                                            {snippet.title}
                                        </button>
                                        <Button
                                            size="icon-sm"
                                            variant="ghost"
                                            onClick={() => onDelete(snippet.id)}
                                            title={__('Delete snippet', 'debug-suite')}>
                                            <Trash2 size={14} />
                                        </Button>
                                    </li>
                                ))}
                            </ul>

                            {canCreate && (
                                <button
                                    type="button"
                                    onClick={handleCreate}
                                    className="hover:bg-secondary mt-1 flex w-full items-center gap-2 rounded-md border-t border-gray-100 px-2 py-1.5 text-left text-sm dark:border-neutral-800">
                                    <Plus size={14} />
                                    <span className="truncate">
                                        {__('Create', 'debug-suite')} “{trimmed}”
                                    </span>
                                </button>
                            )}
                        </Popover.Popup>
                    </Popover.Positioner>
                </div>
            </Popover.Portal>
        </Popover.Root>
    );
};

export default SnippetsMenu;
