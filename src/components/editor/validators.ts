import type { Monaco } from '@monaco-editor/react';
import { type editor } from 'monaco-editor';
import parser from 'php-parser';

// Simple validation error type
export interface ValidationError {
    lineNumber: number;
    column: number;
    message: string;
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
            message: error.message || 'Syntax error'
        };
    }
};

// Simple object lookup - no switch needed!
const validators = {
    php: phpValidator
    // Add new validators here: python: validatePython,
};

// Main validation function that handles Monaco Editor markers
export const validateCode = (code: string, language: string, editor: editor.IStandaloneCodeEditor, monaco: Monaco) => {
    const model = editor.getModel();
    if (!model) return;

    // Get validator function from object
    const validator = validators[language as keyof typeof validators];
    if (typeof validator !== 'function') return;

    const error = validator(code);

    // Update Monaco markers
    if (error) {
        monaco.editor.setModelMarkers(model, language, [
            {
                startLineNumber: error.lineNumber,
                startColumn: error.column,
                endLineNumber: error.lineNumber,
                endColumn: error.column + 1, // +1 for proper highlighting
                message: error.message,
                severity: monaco.MarkerSeverity.Error
            }
        ]);
    } else {
        // Clear markers if no errors
        monaco.editor.setModelMarkers(model, language, []);
    }
};
