import { classNames } from '@/utils';

const QueryConsole = ({ className }: { className?: string }) => {
    return (
        <div className={classNames('query-console', className)}>
            <h1>Query Console</h1>
        </div>
    );
};

export default QueryConsole;
