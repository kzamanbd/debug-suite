import type { Monaco } from '@monaco-editor/react';
import { type editor } from 'monaco-editor';
import parser from 'php-parser';

// Simple validation error type
export interface ValidationError {
    lineNumber: number;
    column: number;
    message: string;
    severity?: 'error' | 'warning';
}

// PHP validator function
export const phpValidator = (code: string): ValidationError | null => {
    const phpEngine = new parser.Engine({
        parser: { extractDoc: true, php7: true },
        ast: { withPositions: true }
    });

    try {
        phpEngine.parseCode(code, 'server.php');
        return null; // No errors
    } catch (e: unknown) {
        const error = e as ValidationError;
        return {
            lineNumber: error.lineNumber || 1,
            column: error.column || 1,
            message: error.message || 'Syntax error',
            severity: 'error'
        };
    }
};

// Simple object lookup - no switch needed!
const validators = {
    php: phpValidator,
    // Query Console buffers are bare PHP under a dedicated language id.
    'php-console': phpValidator
    // Add new validators here: python: validatePython,
};

// Store decorations collection as a WeakMap to avoid memory leaks
const decorationsCollections = new WeakMap<editor.IStandaloneCodeEditor, editor.IEditorDecorationsCollection>();

// Main validation function that handles Monaco Editor markers
export const validateCode = (
    code: string,
    language: string,
    editor: editor.IStandaloneCodeEditor,
    monaco: Monaco
): ValidationError | null => {
    const model = editor.getModel();
    if (!model) return null;

    // Get validator function from object
    const validator = validators[language as keyof typeof validators];
    if (typeof validator !== 'function') return null;

    const error = validator(code);

    // Get or create decorations collection
    let decorations = decorationsCollections.get(editor);
    if (!decorations) {
        decorations = editor.createDecorationsCollection();
        decorationsCollections.set(editor, decorations);
    }

    // Update Monaco markers and decorations
    if (error) {
        // Set basic marker for error indication
        monaco.editor.setModelMarkers(model, language, [
            {
                startLineNumber: error.lineNumber,
                startColumn: error.column,
                endLineNumber: error.lineNumber,
                endColumn: error.column + 1,
                message: error.message,
                severity: monaco.MarkerSeverity.Error
            }
        ]);

        // Add enhanced error decoration for more visibility
        const errorRange = new monaco.Range(
            error.lineNumber,
            1,
            error.lineNumber,
            model.getLineMaxColumn(error.lineNumber)
        );
        decorations.set([
            {
                range: errorRange,
                options: {
                    isWholeLine: true,
                    inlineClassName: 'error-line-highlight',
                    linesDecorationsClassName: 'error-line-decoration',
                    overviewRuler: {
                        color: { id: 'errorForeground' },
                        position: monaco.editor.OverviewRulerLane.Right
                    }
                }
            }
        ]);
    } else {
        // Clear markers if no errors
        monaco.editor.setModelMarkers(model, language, []);
        // Clear decorations
        decorations.clear();
    }

    return error;
};
