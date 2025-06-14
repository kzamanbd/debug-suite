/**
 * Card component for Debug Suite UI.
 *
 * Simple card container for content.
 *
 * @since 1.0.0
 */
import { cn } from '@/utils/cn';

interface CardProps {
    children: JSX.Element | JSX.Element[];
    className?: string;
}

const Card = ({ children, className = '' }: CardProps) => (
    <div className={cn('rounded-xl border border-gray-200 bg-white p-6 shadow-sm', className)}>{children}</div>
);

Card.Body = ({ children }: { children: JSX.Element }) => <div className="flex flex-col gap-4">{children}</div>;
Card.Header = ({ children }: { children: JSX.Element }) => (
    <div className="mb-4">
        <h2 className="border-b bg-gray-100 text-lg font-semibold text-gray-900">{children}</h2>
    </div>
);
Card.Footer = ({ children }: { children: JSX.Element }) => <div className="mt-4 border-t pt-4">{children}</div>;

export default Card;
