/**
 * FileIcon component.
 *
 * Renders a file or directory icon with brand color.
 *
 * @since 1.0.0
 */

import { classNames } from '@/utils';
import { FileText, Folder } from 'lucide-react';

interface FileIconProps {
    type?: string;
    className?: string;
}

const FileIcon = ({ type = 'file', className = '' }: FileIconProps) => {
    if (type === 'directory') {
        return <Folder aria-label="Directory" className={classNames('text-primary', className)} size={24} />;
    }
    return <FileText aria-label="File" className={classNames('text-primary', className)} size={24} />;
};

export default FileIcon;
