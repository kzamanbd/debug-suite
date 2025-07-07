import type { ReactNode } from 'react';

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
    reject?: () => void;
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

export interface SettingsState {
    fileManagerAccess: string;
    publicRootPath: string;
    filesUrl: string;
    defaultViewType: string;
    enableTrash: boolean;
    hideHtaccess: boolean;
    logQueries: boolean;
    logErrors: boolean;
}

// global window type
declare global {
    interface Window {
        debugSuite: {
            wpDebug: boolean;
            wpDebugLog: boolean;
            wpDebugDisplay: boolean;
            wpVersion: string;
            phpVersion: string;
            favicon: string;
            roles: Record<string, { name: string }>;
        } & SettingsState;
    }
}
