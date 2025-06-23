export interface IFile {
    name: string;
    type: string;
    path: string;
    size: number;
    checked: boolean;
    modified_at: string;
    expanded: boolean;
    children: IFile[];
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
    wpDebug: boolean;
    wpDebugLog: boolean;
    wpDebugDisplay: boolean;
    [key: string]: string | boolean | Record<string, { name: string }>;
}

// global window type
declare global {
    interface Window {
        debugSuite: SettingsState & {
            roles: Record<string, { name: string }>;
            [key: string]: string | boolean | Record<string, { name: string }> | undefined;
        };
    }
}
