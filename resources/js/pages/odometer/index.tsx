import { Form, Head, Link } from '@inertiajs/react';
import { show as showTrip } from '@/actions/App/Http/Controllers/TripController';
import { show as showVehicle } from '@/actions/App/Http/Controllers/VehicleController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { formatDate, formatMileage, humanizeStatus } from '@/lib/vehicles';
import { index } from '@/routes/odometer';
import type { Paginated } from '@/types';
import type { OdometerEntry, OdometerEntryType } from '@/types/odometer';

const selectClass =
    'h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs focus-visible:outline-2 focus-visible:outline-ring';

type Props = {
    entries: Paginated<OdometerEntry>;
    filters: { vehicle_id?: number; entry_type?: OdometerEntryType };
    types: OdometerEntryType[];
    vehicles: { id: number; name: string; plate_number: string | null }[];
    canCorrect: boolean;
};

export default function OdometerIndex({
    entries,
    filters,
    types,
    vehicles,
    canCorrect,
}: Props) {
    return (
        <>
            <Head title="Mileage / Odometer history" />
            <div className="grid gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Mileage / Odometer history
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        An append-only record of trip readings and authorized
                        manual corrections.
                    </p>
                </div>
                <Form
                    {...index.form()}
                    className="flex flex-wrap items-end gap-3 rounded-xl border bg-card p-4"
                >
                    <div className="grid min-w-56 flex-1 gap-2">
                        <Label htmlFor="vehicle_id">Vehicle</Label>
                        <select
                            id="vehicle_id"
                            name="vehicle_id"
                            defaultValue={filters.vehicle_id ?? ''}
                            className={selectClass}
                        >
                            <option value="">All vehicles</option>
                            {vehicles.map((vehicle) => (
                                <option key={vehicle.id} value={vehicle.id}>
                                    {vehicle.name} ·{' '}
                                    {vehicle.plate_number ||
                                        `Vehicle #${vehicle.id}`}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="entry_type">Entry type</Label>
                        <select
                            id="entry_type"
                            name="entry_type"
                            defaultValue={filters.entry_type ?? ''}
                            className={selectClass}
                        >
                            <option value="">All types</option>
                            {types.map((type) => (
                                <option key={type} value={type}>
                                    {humanizeStatus(type)}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button variant="outline">Filter</Button>
                    <Button asChild variant="ghost">
                        <Link href={index()}>Clear</Link>
                    </Button>
                </Form>
                <section className="overflow-hidden rounded-xl border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    {[
                                        'Recorded',
                                        'Vehicle',
                                        'Reading',
                                        'Change',
                                        'Source',
                                        'Recorded by',
                                        'Notes',
                                    ].map((label) => (
                                        <th
                                            key={label}
                                            className="px-4 py-3 font-medium"
                                        >
                                            {label}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {!entries.data.length && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="p-8 text-center text-muted-foreground"
                                        >
                                            No odometer history found.
                                        </td>
                                    </tr>
                                )}
                                {entries.data.map((entry) => (
                                    <tr
                                        key={entry.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {formatDate(
                                                entry.recorded_at,
                                                true,
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Link
                                                className="font-medium underline underline-offset-4"
                                                href={showVehicle(
                                                    entry.vehicle_id,
                                                )}
                                            >
                                                {entry.vehicle?.name}
                                            </Link>
                                            <p className="text-xs text-muted-foreground">
                                                {entry.vehicle?.plate_number ||
                                                    'No plate'}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3 font-medium whitespace-nowrap">
                                            {formatMileage(entry.reading)}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {entry.reading -
                                                entry.previous_reading >=
                                            0
                                                ? '+'
                                                : ''}
                                            {(
                                                entry.reading -
                                                entry.previous_reading
                                            ).toLocaleString()}{' '}
                                            km
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant={
                                                    entry.entry_type ===
                                                    'manual_correction'
                                                        ? 'outline'
                                                        : 'secondary'
                                                }
                                            >
                                                {humanizeStatus(
                                                    entry.entry_type,
                                                )}
                                            </Badge>
                                            {entry.trip && (
                                                <p className="pt-1">
                                                    <Link
                                                        className="text-xs underline underline-offset-4"
                                                        href={showTrip(
                                                            entry.trip.id,
                                                        )}
                                                    >
                                                        {
                                                            entry.trip
                                                                .reference_number
                                                        }
                                                    </Link>
                                                </p>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            {entry.recorder.name}
                                        </td>
                                        <td className="max-w-72 px-4 py-3 break-words">
                                            {entry.notes || '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <div className="flex flex-wrap items-center justify-between gap-3 border-t p-4 text-sm">
                        <p>
                            {entries.total} entries · Page{' '}
                            {entries.current_page} of {entries.last_page}
                        </p>
                        <div className="flex gap-2">
                            {entries.current_page > 1 && (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={index({
                                            query: {
                                                ...filters,
                                                page: entries.current_page - 1,
                                            },
                                        })}
                                    >
                                        Previous
                                    </Link>
                                </Button>
                            )}
                            {entries.current_page < entries.last_page && (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={index({
                                            query: {
                                                ...filters,
                                                page: entries.current_page + 1,
                                            },
                                        })}
                                    >
                                        Next
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </div>
                </section>
                {canCorrect && (
                    <p className="text-sm text-muted-foreground">
                        Manual corrections are entered from a vehicle profile
                        and require a reason.
                    </p>
                )}
            </div>
        </>
    );
}
OdometerIndex.layout = {
    breadcrumbs: [{ title: 'Odometer history', href: index() }],
};
