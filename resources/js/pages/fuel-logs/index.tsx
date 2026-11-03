import { Form, Head, Link } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/FuelLogController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { formatCurrency, formatDate, formatMileage } from '@/lib/vehicles';
import type { Paginated } from '@/types';
import { FuelLogModal  } from './fuel-log-modal';
import type {FuelVehicle} from './fuel-log-modal';

type Vehicle = FuelVehicle;
type FuelLog = {
    id: number;
    vehicle: Vehicle;
    recorder: { name: string };
    quantity_liters: string;
    total_cost: string;
    odometer: number;
    refueled_at: string;
    station: string | null;
    receipt_reference: string | null;
    notes: string | null;
    distance_since_previous: number | null;
    consumption_km_per_liter: string | null;
    cost_per_km: string | null;
    price_per_liter: string;
};
type Props = {
    logs: Paginated<FuelLog>;
    filters: { vehicle_id?: number; date_from?: string; date_to?: string };
    vehicles: Vehicle[];
    summary: {
        quantity: number;
        cost: number;
        distance: number;
        consumption: number | null;
    };
    canManage: boolean;
    now: string;
};
const selectClass =
    'h-9 rounded-md border border-input bg-background px-3 text-sm';

export default function FuelLogIndex({
    logs,
    filters,
    vehicles,
    summary,
    canManage,
    now,
}: Props) {
    return (
        <>
            <Head title="Fuel logs" />
            <div className="grid gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">Fuel logs</h1>
                        <p className="text-sm text-muted-foreground">
                            Recorded fuel, cost, odometer readings, and
                            consumption based on consecutive refueling entries.
                        </p>
                    </div>
                    {canManage && (
                        <FuelLogModal vehicles={vehicles} now={now} />
                    )}
                </div>
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    {[
                        [
                            'Fuel quantity',
                            `${summary.quantity.toLocaleString()} L`,
                        ],
                        ['Fuel cost', formatCurrency(summary.cost)],
                        ['Recorded distance', formatMileage(summary.distance)],
                        [
                            'Average consumption',
                            summary.consumption === null
                                ? 'Needs two entries'
                                : `${summary.consumption.toFixed(2)} km/L`,
                        ],
                    ].map(([label, value]) => (
                        <Card key={label}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm text-muted-foreground">
                                    {label}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="text-2xl font-semibold">
                                {value}
                            </CardContent>
                        </Card>
                    ))}
                </div>
                <Form
                    {...index.form()}
                    className="flex flex-wrap items-end gap-3 rounded-xl border p-4"
                >
                    <div className="grid gap-2">
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
                                    {vehicle.plate_number ?? `#${vehicle.id}`}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="date_from">From</Label>
                        <input
                            id="date_from"
                            name="date_from"
                            type="date"
                            defaultValue={filters.date_from}
                            className={selectClass}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="date_to">To</Label>
                        <input
                            id="date_to"
                            name="date_to"
                            type="date"
                            defaultValue={filters.date_to}
                            className={selectClass}
                        />
                    </div>
                    <Button variant="outline">Filter</Button>
                    <Button asChild variant="ghost">
                        <Link href={index()}>Clear</Link>
                    </Button>
                </Form>
                <section className="overflow-hidden rounded-xl border">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/40 text-left">
                                <tr>
                                    <th className="p-3">Date / vehicle</th>
                                    <th className="p-3">Fuel</th>
                                    <th className="p-3">Cost</th>
                                    <th className="p-3">Odometer / distance</th>
                                    <th className="p-3">Consumption</th>
                                    <th className="p-3">Reference</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {logs.data.map((log) => (
                                    <tr key={log.id}>
                                        <td className="p-3">
                                            <p className="font-medium">
                                                {formatDate(
                                                    log.refueled_at,
                                                    true,
                                                )}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {log.vehicle.name} ·{' '}
                                                {log.vehicle.plate_number ??
                                                    'No plate'}
                                            </p>
                                        </td>
                                        <td className="p-3">
                                            <p>
                                                {Number(
                                                    log.quantity_liters,
                                                ).toLocaleString()}{' '}
                                                L
                                            </p>
                                            <p className="text-muted-foreground">
                                                {formatCurrency(
                                                    log.price_per_liter,
                                                )}
                                                /L
                                            </p>
                                        </td>
                                        <td className="p-3">
                                            <p>
                                                {formatCurrency(log.total_cost)}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {log.cost_per_km
                                                    ? `${formatCurrency(log.cost_per_km)}/km`
                                                    : 'First reading'}
                                            </p>
                                        </td>
                                        <td className="p-3">
                                            <p>{formatMileage(log.odometer)}</p>
                                            <p className="text-muted-foreground">
                                                {log.distance_since_previous ===
                                                null
                                                    ? 'Baseline entry'
                                                    : `${formatMileage(log.distance_since_previous)} since prior fill`}
                                            </p>
                                        </td>
                                        <td className="p-3">
                                            {log.consumption_km_per_liter
                                                ? `${Number(log.consumption_km_per_liter).toFixed(2)} km/L`
                                                : 'Available after next entry'}
                                        </td>
                                        <td className="p-3">
                                            <p>
                                                {log.receipt_reference ??
                                                    'No receipt reference'}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {log.station ??
                                                    'Station not specified'}{' '}
                                                · {log.recorder.name}
                                            </p>
                                        </td>
                                    </tr>
                                ))}
                                {!logs.data.length && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="p-8 text-center text-muted-foreground"
                                        >
                                            No fuel logs match these filters.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="flex flex-wrap items-center justify-between gap-3 border-t p-4 text-sm">
                        <p>
                            {logs.total} records · Page {logs.current_page} of{' '}
                            {logs.last_page}
                        </p>
                        <div className="flex gap-2">
                            {logs.current_page > 1 && (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={index({
                                            query: {
                                                ...filters,
                                                page: logs.current_page - 1,
                                            },
                                        })}
                                    >
                                        Previous
                                    </Link>
                                </Button>
                            )}
                            {logs.current_page < logs.last_page && (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={index({
                                            query: {
                                                ...filters,
                                                page: logs.current_page + 1,
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
                <p className="text-xs text-muted-foreground">
                    Consumption uses distance since the immediately previous
                    refueling divided by the current fuel quantity. For the most
                    meaningful figures, log consistently at full-tank refueling.
                </p>
            </div>
        </>
    );
}
FuelLogIndex.layout = { breadcrumbs: [{ title: 'Fuel Logs', href: index() }] };
