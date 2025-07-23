/**
 * FileIcon component.
 *
 * Renders a file or directory icon with brand color.
 *
 * @since 1.0.0
 */

import { classNames } from '@/utils';
import { FileText, Folder, FolderOpen } from 'lucide-react';

interface FileIconProps {
    type?: string;
    className?: string;
    isOpen?: boolean;
}

const FileIcon = ({ type = 'file', className = '', isOpen = false }: FileIconProps) => {
    if (type === 'directory') {
        const IconComponent = isOpen ? FolderOpen : Folder;
        return <IconComponent aria-label="Directory" className={classNames('text-primary', className)} size={24} />;
    }
    return <FileText aria-label="File" className={classNames('text-primary', className)} size={24} />;
};

export default FileIcon;
