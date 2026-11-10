import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
}: {
    icon: LucideIcon;
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <div className="rounded-xl border border-dashed border-border p-14 text-center">
            <Icon className="mx-auto size-8 text-muted-foreground" />
            <p className="mt-4 font-medium">{title}</p>
            {description && (
                <p className="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                    {description}
                </p>
            )}
            {action && <div className="mt-6">{action}</div>}
        </div>
    );
}
