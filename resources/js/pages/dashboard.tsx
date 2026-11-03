import { Form, Head, Link } from '@inertiajs/react';
import {
    Activity,
    CalendarCheck,
    CarFront,
    Fuel,
    Route,
    TriangleAlert,
    Wrench,
} from 'lucide-react';
import { show as showTrip } from '@/actions/App/Http/Controllers/TripController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    formatCurrency,
    formatDate,
    formatMileage,
    humanizeStatus,
} from '@/lib/vehicles';
import { dashboard } from '@/routes';
import { index as assignmentsIndex } from '@/routes/assignments';
import { index as fuelLogsIndex } from '@/routes/fuel-logs';
import { index as maintenanceIndex } from '@/routes/maintenance';
import { index as tripsIndex } from '@/routes/trips';
import { index as vehiclesIndex } from '@/routes/vehicles';

type TripSummary = {
    id: number;
    reference_number: string;
    status: string;
    destination: string;
    planned_departure: string;
    vehicle: { name: string; plate_number: string | null } | null;
    driver: { employee_name: string } | null;
};
type Props = {
    filters: { date_from: string; date_to: string };
    availability: { available: number; operable: number; rate: number };
    activeAssignments: number;
    trips: {
        total: number;
        byStatus: Record<string, number>;
        recent: TripSummary[];
    };
    maintenance: { due: number; overdue: number };
    fuel: {
        quantity: number;
        cost: number;
        distance: number;
        consumption: number | null;
    };
    operationalStatuses: { status: string; count: number }[];
};
const statusTone = (status: string) =>
    status === 'rejected' || status === 'cancelled'
        ? 'destructive'
        : status === 'completed'
          ? 'secondary'
          : 'outline';

export default function Dashboard({
    filters,
    availability,
    activeAssignments,
    trips,
    maintenance,
    fuel,
    operationalStatuses,
}: Props) {
    const fleetTotal = operationalStatuses.reduce(
        (total, item) => total + item.count,
        0,
    );

    return (
        <>
            <Head title="Dashboard" />
            <div className="grid gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Fleet operations dashboard
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Current fleet readiness with trip and fuel reporting
                            for {formatDate(filters.date_from)} –{' '}
                            {formatDate(filters.date_to)}.
                        </p>
                    </div>
                    <Form
                        {...dashboard.form()}
                        className="flex flex-wrap items-end gap-2 rounded-xl border p-3"
                    >
                        <div className="grid gap-1">
                            <Label htmlFor="date_from" className="text-xs">
                                Report from
                            </Label>
                            <input
                                id="date_from"
                                name="date_from"
                                type="date"
                                defaultValue={filters.date_from}
                                className="h-9 rounded-md border bg-background px-3 text-sm"
                            />
                        </div>
                        <div className="grid gap-1">
                            <Label htmlFor="date_to" className="text-xs">
                                Report to
                            </Label>
                            <input
                                id="date_to"
                                name="date_to"
                                type="date"
                                defaultValue={filters.date_to}
                                className="h-9 rounded-md border bg-background px-3 text-sm"
                            />
                        </div>
                        <Button size="sm">Apply</Button>
                    </Form>
                </div>
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <MetricCard
                        href={vehiclesIndex()}
                        icon={CarFront}
                        label="Vehicle availability"
                        value={`${availability.available} / ${availability.operable}`}
                        detail={`${availability.rate}% available`}
                    />
                    <MetricCard
                        href={assignmentsIndex({
                            query: { status: 'current' },
                        })}
                        icon={CalendarCheck}
                        label="Active assignments"
                        value={activeAssignments.toLocaleString()}
                        detail="Current inclusive dates"
                    />
                    <MetricCard
                        href={tripsIndex()}
                        icon={Route}
                        label="Trips in period"
                        value={trips.total.toLocaleString()}
                        detail={`${trips.byStatus.completed ?? 0} completed`}
                    />
                    <MetricCard
                        href={maintenanceIndex()}
                        icon={Wrench}
                        label="Maintenance due"
                        value={maintenance.due.toLocaleString()}
                        detail="Date or mileage threshold"
                    />
                    <MetricCard
                        href={maintenanceIndex()}
                        icon={TriangleAlert}
                        label="Maintenance overdue"
                        value={maintenance.overdue.toLocaleString()}
                        detail="Requires attention"
                        alert={maintenance.overdue > 0}
                    />
                </div>
                <div className="grid gap-6 xl:grid-cols-5">
                    <Card className="xl:col-span-3">
                        <CardHeader>
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <CardTitle>Operational status</CardTitle>
                                    <CardDescription>
                                        Current AMS-present fleet distribution.
                                    </CardDescription>
                                </div>
                                <Activity className="size-5 text-muted-foreground" />
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            {operationalStatuses.map((item) => {
                                const percentage = fleetTotal
                                    ? (item.count / fleetTotal) * 100
                                    : 0;

                                return (
                                    <div
                                        key={item.status}
                                        className="grid gap-1"
                                    >
                                        <div className="flex justify-between text-sm">
                                            <span>
                                                {humanizeStatus(item.status)}
                                            </span>
                                            <span className="font-medium">
                                                {item.count} ·{' '}
                                                {percentage.toFixed(0)}%
                                            </span>
                                        </div>
                                        <div className="h-2 overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-full rounded-full bg-primary"
                                                style={{
                                                    width: `${percentage}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                );
                            })}
                            {!fleetTotal && (
                                <p className="text-sm text-muted-foreground">
                                    No synchronized vehicles are available.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                    <Card className="xl:col-span-2">
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Fuel usage</CardTitle>
                                    <CardDescription>
                                        Selected report period.
                                    </CardDescription>
                                </div>
                                <Fuel className="size-5 text-muted-foreground" />
                            </div>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-4">
                            <ReportValue
                                label="Quantity"
                                value={`${fuel.quantity.toLocaleString()} L`}
                            />
                            <ReportValue
                                label="Total cost"
                                value={formatCurrency(fuel.cost)}
                            />
                            <ReportValue
                                label="Recorded distance"
                                value={formatMileage(fuel.distance)}
                            />
                            <ReportValue
                                label="Consumption"
                                value={
                                    fuel.consumption === null
                                        ? 'Needs baseline'
                                        : `${fuel.consumption.toFixed(2)} km/L`
                                }
                            />
                            <Button
                                asChild
                                variant="outline"
                                className="col-span-2"
                            >
                                <Link href={fuelLogsIndex({ query: filters })}>
                                    View fuel report
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
                <div className="grid gap-6 xl:grid-cols-5">
                    <Card className="xl:col-span-2">
                        <CardHeader>
                            <CardTitle>Trip status</CardTitle>
                            <CardDescription>
                                Requests scheduled in the report period.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-3">
                            {Object.entries(trips.byStatus).map(
                                ([status, count]) => (
                                    <div
                                        key={status}
                                        className="rounded-lg border p-3"
                                    >
                                        <p className="text-xs text-muted-foreground">
                                            {humanizeStatus(status)}
                                        </p>
                                        <p className="text-2xl font-semibold">
                                            {count}
                                        </p>
                                    </div>
                                ),
                            )}
                        </CardContent>
                    </Card>
                    <Card className="xl:col-span-3">
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Recent trips</CardTitle>
                                    <CardDescription>
                                        Latest scheduled trips in the selected
                                        period.
                                    </CardDescription>
                                </div>
                                <Button asChild size="sm" variant="outline">
                                    <Link href={tripsIndex()}>All trips</Link>
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="divide-y p-0">
                            {trips.recent.map((trip) => (
                                <Link
                                    key={trip.id}
                                    href={showTrip(trip.id)}
                                    className="flex flex-wrap items-center justify-between gap-3 px-6 py-3 hover:bg-muted/40"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {trip.reference_number} ·{' '}
                                            {trip.destination}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {formatDate(
                                                trip.planned_departure,
                                                true,
                                            )}{' '}
                                            ·{' '}
                                            {trip.vehicle?.name ??
                                                'Vehicle unassigned'}{' '}
                                            ·{' '}
                                            {trip.driver?.employee_name ??
                                                'Driver unassigned'}
                                        </p>
                                    </div>
                                    <Badge variant={statusTone(trip.status)}>
                                        {humanizeStatus(trip.status)}
                                    </Badge>
                                </Link>
                            ))}
                            {!trips.recent.length && (
                                <p className="p-6 text-sm text-muted-foreground">
                                    No trips are scheduled in this report
                                    period.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

function MetricCard({
    href,
    icon: Icon,
    label,
    value,
    detail,
    alert = false,
}: {
    href: ReturnType<typeof vehiclesIndex>;
    icon: typeof CarFront;
    label: string;
    value: string;
    detail: string;
    alert?: boolean;
}) {
    return (
        <Link href={href}>
            <Card className={alert ? 'border-destructive/50' : ''}>
                <CardHeader className="flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm text-muted-foreground">
                        {label}
                    </CardTitle>
                    <Icon
                        className={
                            alert
                                ? 'size-5 text-destructive'
                                : 'size-5 text-muted-foreground'
                        }
                    />
                </CardHeader>
                <CardContent>
                    <p className="text-3xl font-semibold">{value}</p>
                    <p className="text-xs text-muted-foreground">{detail}</p>
                </CardContent>
            </Card>
        </Link>
    );
}

function ReportValue({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-lg border p-3">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="font-semibold">{value}</p>
        </div>
    );
}

Dashboard.layout = { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] };
