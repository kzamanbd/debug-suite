import type { editor } from 'monaco-editor';
import type { ReactNode } from 'react';

export type IStandaloneCodeEditor = editor.IStandaloneCodeEditor;
export type EditorOptions = editor.IStandaloneEditorConstructionOptions;

export type DialogType = 'success' | 'error' | 'warning' | 'confirm';

export interface DialogOptions {
    type?: DialogType;
    title?: string;
    okText?: string;
    autoHideDelay?: number;
    showCancel?: boolean;
    showOk?: boolean;
    cancelText?: string;
    allowOutsideClick?: boolean;
}

export interface DialogState {
    open: boolean;
    title?: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    options: DialogOptions;
    resolve?: (value: boolean) => void;
    reject?: (value: string) => void;
}

export interface DialogModalProps {
    open: boolean;
    title?: string;
    message?: string;
    options: DialogOptions;
    onClose: (confirmed: boolean) => void;
}

export interface DialogTypeProps {
    icon: ReactNode;
    defaultTitle: string;
    iconClass?: string;
    buttonClass?: string;
}

export interface ItemTree {
    name: string;
    type: string;
    path: string;
    size: number;
    checked: boolean;
    modified_at: string;
    expanded: boolean;
    children?: ItemTree[];
}

export interface LogFile {
    name: string;
    path: string;
    size: string;
    size_bytes: number;
    modified: string;
    type: string;
}

export interface DebugState {
    wp_debug: boolean | string;
    wp_debug_log: boolean | string;
    wp_debug_display: boolean | string;
}

// global window type
declare global {
    interface Window {
        debugSuite: {
            onboarding_completed: boolean;
            content_url: string;
            wp_version: string;
            php_version: string;
            favicon: string;
            logo: string;
            site_name: string;
            logs: LogFile[];
        } & DebugState;
    }
}
