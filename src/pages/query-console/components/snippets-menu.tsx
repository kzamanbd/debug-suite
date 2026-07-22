import { Button } from '@/components/ui';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Plus, Trash2 } from 'lucide-react';
import type { Snippet } from '../types';

interface SnippetsMenuProps {
    snippets: Snippet[];
    onInsert: (code: string) => void;
    onSave: (title: string) => void;
    onDelete: (id: string) => void;
}

const SnippetsMenu = ({ snippets, onInsert, onSave, onDelete }: SnippetsMenuProps) => {
    const [title, setTitle] = useState('');

    const handleSave = () => {
        const trimmed = title.trim();
        if (!trimmed) return;
        onSave(trimmed);
        setTitle('');
    };

    return (
        <div className="flex w-64 flex-col gap-2 p-2">
            <div className="flex items-center gap-1">
                <input
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    placeholder={__('Snippet title…', 'debug-suite')}
                    className="border-border w-full rounded border px-2 py-1 text-sm"
                />
                <Button
                    size="icon-sm"
                    variant="secondary"
                    onClick={handleSave}
                    title={__('Save current code', 'debug-suite')}>
                    <Plus size={16} />
                </Button>
            </div>

            <ul className="flex flex-col gap-1">
                {snippets.length === 0 && (
                    <li className="text-muted-foreground text-xs">{__('No snippets saved.', 'debug-suite')}</li>
                )}
                {snippets.map((snippet) => (
                    <li key={snippet.id} className="flex items-center justify-between gap-1">
                        <button
                            type="button"
                            className="hover:bg-secondary flex-1 truncate rounded px-2 py-1 text-left text-sm"
                            onClick={() => onInsert(snippet.code)}>
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
        </div>
    );
};

export default SnippetsMenu;
