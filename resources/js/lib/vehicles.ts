export function humanizeStatus(value: string | null): string {
    if (!value) {
        return 'Not recorded';
    }

    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

export function formatMileage(value: number | null): string {
    return value === null || !Number.isFinite(value)
        ? 'Not recorded'
        : `${value.toLocaleString()} km`;
}

export function formatCurrency(value: string | number | null): string {
    if (value === null || !Number.isFinite(Number(value))) {
        return 'Not recorded';
    }

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(Number(value));
}

export function formatDate(value: string | null, includeTime = false): string {
    if (!value) {
        return 'Never';
    }

    const date = new Date(value.includes('T') ? value : `${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return 'Not recorded';
    }

    return new Intl.DateTimeFormat('en-PH', {
        dateStyle: 'medium',
        ...(includeTime ? { timeStyle: 'short' } : {}),
    }).format(date);
}
