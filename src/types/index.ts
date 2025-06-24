export interface ItemTree {
    name: string;
    type: string;
    path: string;
    size: number;
    checked: boolean;
    modified_at: string;
    expanded: boolean;
    children: ItemTree[];
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
        debugSuite: SettingsState & {
            wpDebug: boolean;
            wpDebugLog: boolean;
            wpDebugDisplay: boolean;
            wpVersion: string;
            phpVersion: string;
            favicon: string;
            roles: Record<string, { name: string }>;
            [key: string]: string | boolean | Record<string, { name: string }> | undefined;
        };
    }
}
