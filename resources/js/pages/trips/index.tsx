import { Form, Head, Link } from '@inertiajs/react';
import { index, show } from '@/actions/App/Http/Controllers/TripController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, humanizeStatus } from '@/lib/vehicles';
import type { Paginated } from '@/types';
import type { Trip, TripStatus } from '@/types/trip';
import { TripRequestModal } from './trip-request-modal';

type Props = {
    trips: Paginated<Trip>;
    filters: { search?: string; status?: string };
    statuses: TripStatus[];
    canManage: boolean;
    defaultDeparture: string;
    defaultReturn: string;
};

export default function TripIndex({
    trips,
    filters,
    statuses,
    canManage,
    defaultDeparture,
    defaultReturn,
}: Props) {
    return (
        <>
            <Head title="Trips and dispatch" />
            <div className="grid gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Trips and dispatch
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Track requests, approvals, departures, returns, and
                            odometer readings.
                        </p>
                    </div>
                    <TripRequestModal
                        defaultDeparture={defaultDeparture}
                        defaultReturn={defaultReturn}
                    />
                </div>
                <Form
                    {...index.form()}
                    className="flex flex-wrap items-end gap-3 rounded-xl border bg-card p-4"
                >
                    <div className="grid min-w-56 flex-1 gap-2">
                        <Label htmlFor="trip-search">Search</Label>
                        <Input
                            id="trip-search"
                            name="search"
                            defaultValue={filters.search ?? ''}
                            placeholder="Reference or destination"
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="trip-status">Status</Label>
                        <select
                            id="trip-status"
                            name="status"
                            defaultValue={filters.status ?? ''}
                            className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">All statuses</option>
                            {statuses.map((status) => (
                                <option key={status} value={status}>
                                    {humanizeStatus(status)}
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
                                        'Reference',
                                        'Schedule',
                                        'Destination',
                                        'Resources',
                                        'Status',
                                        '',
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
                                {!trips.data.length && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="p-8 text-center text-muted-foreground"
                                        >
                                            No trip requests found.
                                        </td>
                                    </tr>
                                )}
                                {trips.data.map((trip) => (
                                    <tr
                                        key={trip.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3">
                                            <p className="font-medium">
                                                {trip.reference_number}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Requested by{' '}
                                                {trip.requester.name}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {formatDate(
                                                trip.planned_departure,
                                                true,
                                            )}
                                            <br />
                                            <span className="text-muted-foreground">
                                                to{' '}
                                                {formatDate(
                                                    trip.planned_return,
                                                    true,
                                                )}
                                            </span>
                                        </td>
                                        <td className="max-w-64 px-4 py-3 break-words">
                                            {trip.destination}
                                        </td>
                                        <td className="px-4 py-3">
                                            {trip.vehicle?.name ??
                                                'Not assigned'}
                                            <br />
                                            <span className="text-xs text-muted-foreground">
                                                {trip.driver?.employee_name ??
                                                    'No driver'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant={
                                                    trip.status === 'rejected'
                                                        ? 'destructive'
                                                        : trip.status ===
                                                            'pending'
                                                          ? 'outline'
                                                          : 'default'
                                                }
                                            >
                                                {humanizeStatus(trip.status)}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                            >
                                                <Link href={show(trip.id)}>
                                                    {canManage &&
                                                    trip.status === 'pending'
                                                        ? 'Review'
                                                        : 'View'}
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <div className="flex flex-wrap items-center justify-between gap-3 border-t p-4 text-sm">
                        <p>
                            {trips.total} trip{trips.total === 1 ? '' : 's'} ·
                            Page {trips.current_page} of {trips.last_page}
                        </p>
                        <div className="flex gap-2">
                            {trips.current_page > 1 && (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={index({
                                            query: {
                                                ...filters,
                                                page: trips.current_page - 1,
                                            },
                                        })}
                                    >
                                        Previous
                                    </Link>
                                </Button>
                            )}
                            {trips.current_page < trips.last_page && (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={index({
                                            query: {
                                                ...filters,
                                                page: trips.current_page + 1,
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
            </div>
        </>
    );
}
TripIndex.layout = { breadcrumbs: [{ title: 'Trips', href: index() }] };
