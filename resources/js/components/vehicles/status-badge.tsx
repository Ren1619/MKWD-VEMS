import { Badge } from '@/components/ui/badge';
import { humanizeStatus } from '@/lib/vehicles';

function statusClass(value: string | null): string {
    if (!value) {
        return 'border-border bg-muted text-muted-foreground';
    }

    const normalizedValue = value.toLocaleLowerCase();

    if (
        ['active', 'available', 'good', 'operational', 'serviceable'].includes(
            normalizedValue,
        )
    ) {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300';
    }

    if (
        [
            'disposed',
            'lost',
            'non_usable',
            'retired',
            'out_of_service',
        ].includes(normalizedValue)
    ) {
        return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300';
    }

    if (
        ['defective', 'for_repair', 'under_maintenance'].includes(
            normalizedValue,
        )
    ) {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300';
    }

    return 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-300';
}

export function StatusBadge({ value }: { value: string | null }) {
    return (
        <Badge variant="outline" className={statusClass(value)}>
            {humanizeStatus(value)}
        </Badge>
    );
}
