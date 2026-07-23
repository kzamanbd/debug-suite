import { classNames } from '@/utils';
import type { ReactNode } from 'react';
import type { SplitOrientation } from '../types';

interface SplitLayoutProps {
    orientation: SplitOrientation;
    first: ReactNode;
    second: ReactNode;
    className?: string;
}

const SplitLayout = ({ orientation, first, second, className }: SplitLayoutProps) => {
    const isVertical = orientation === 'vertical';
    return (
        <div className={classNames('flex min-h-0 flex-1', isVertical ? 'flex-row' : 'flex-col', className)}>
            <div
                className={classNames('min-h-0 min-w-0 flex-1', isVertical ? 'border-r' : 'border-b', 'border-border')}>
                {first}
            </div>
            <div className="min-h-0 min-w-0 flex-1">{second}</div>
        </div>
    );
};

export default SplitLayout;
