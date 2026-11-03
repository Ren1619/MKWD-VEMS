import { Form, Head, Link } from '@inertiajs/react';
import MaintenanceController from '@/actions/App/Http/Controllers/MaintenanceController';
import { review } from '@/actions/App/Http/Controllers/MaintenanceRequestController';
import { show } from '@/actions/App/Http/Controllers/MaintenanceWorkOrderController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    formatCurrency,
    formatDate,
    formatMileage,
    humanizeStatus,
} from '@/lib/vehicles';
import {
    MaintenanceRequestModal,
    MaintenanceScheduleModal,
    WorkOrderModal,
} from './maintenance-modals';
import type {
    MaintenanceVehicle,
    WorkOrderOption,
} from './maintenance-modals';

type Vehicle = {
    id: number;
    name: string;
    plate_number: string | null;
    current_mileage?: number;
};
type Schedule = {
    id: number;
    vehicle_id: number;
    vehicle: Vehicle;
    service_type: string;
    description: string | null;
    interval_days: number | null;
    interval_km: number | null;
    next_due_date: string | null;
    next_due_mileage: number | null;
    due_status: string;
};
type Request = {
    id: number;
    reference_number: string;
    vehicle: Vehicle;
    requester: { name: string };
    priority: string;
    status: string;
    subject: string;
    description: string;
    work_order: { id: number; reference_number: string } | null;
};
type WorkOrder = {
    id: number;
    reference_number: string;
    vehicle: Vehicle;
    schedule: { service_type: string } | null;
    title: string;
    status: string;
    scheduled_start: string | null;
    completed_at: string | null;
    completion_mileage: number | null;
    cost: string | null;
};
type Props = {
    schedules: Schedule[];
    requests: Request[];
    workOrders: WorkOrder[];
    summary: {
        due: number;
        overdue: number;
        pendingRequests: number;
        activeWorkOrders: number;
    };
    canManage: boolean;
    vehicles: MaintenanceVehicle[];
    approvedRequests: WorkOrderOption[];
    now: string;
};

const tone = (status: string) =>
    status === 'overdue' || status === 'critical'
        ? 'destructive'
        : status === 'due' || status === 'pending' || status === 'in_progress'
          ? 'secondary'
          : 'outline';

export default function MaintenanceIndex({
    schedules,
    requests,
    workOrders,
    summary,
    canManage,
    vehicles,
    approvedRequests,
}: Props) {
    return (
        <>
            <Head title="Maintenance" />
            <div className="grid gap-6 p-4 md:p-6">
                <div>
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Maintenance management
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Preventive schedules, requests, work orders,
                            completion records, and permanent service history.
                        </p>
                    </div>
                </div>
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    {[
                        ['Overdue services', summary.overdue],
                        ['Due now', summary.due],
                        ['Pending requests', summary.pendingRequests],
                        ['Active work orders', summary.activeWorkOrders],
                    ].map(([label, value]) => (
                        <Card key={label}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm text-muted-foreground">
                                    {label}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="text-3xl font-semibold">
                                {value}
                            </CardContent>
                        </Card>
                    ))}
                </div>
                <section className="overflow-hidden rounded-xl border">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b p-4">
                        <div>
                            <h2 className="font-semibold">
                                Preventive maintenance schedules
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Due status is evaluated against the vehicle’s
                                current odometer and today’s date.
                            </p>
                        </div>
                        {canManage && (
                            <MaintenanceScheduleModal vehicles={vehicles} />
                        )}
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/40 text-left">
                                <tr>
                                    <th className="p-3">Vehicle / service</th>
                                    <th className="p-3">Interval</th>
                                    <th className="p-3">Next due</th>
                                    <th className="p-3">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {schedules.map((item) => (
                                    <tr key={item.id}>
                                        <td className="p-3">
                                            <p className="font-medium">
                                                {item.vehicle.name}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {item.service_type}
                                            </p>
                                        </td>
                                        <td className="p-3">
                                            {[
                                                item.interval_days &&
                                                    `${item.interval_days} days`,
                                                item.interval_km &&
                                                    `${item.interval_km.toLocaleString()} km`,
                                            ]
                                                .filter(Boolean)
                                                .join(' / ')}
                                        </td>
                                        <td className="p-3">
                                            <p>
                                                {formatDate(item.next_due_date)}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {formatMileage(
                                                    item.next_due_mileage,
                                                )}
                                            </p>
                                        </td>
                                        <td className="p-3">
                                            <Badge
                                                variant={tone(item.due_status)}
                                            >
                                                {humanizeStatus(
                                                    item.due_status,
                                                )}
                                            </Badge>
                                        </td>
                                    </tr>
                                ))}
                                {!schedules.length && (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="p-6 text-center text-muted-foreground"
                                        >
                                            No preventive schedules yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>
                <section className="overflow-hidden rounded-xl border">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b p-4">
                        <h2 className="font-semibold">Maintenance requests</h2>
                        <MaintenanceRequestModal vehicles={vehicles} />
                    </div>
                    <div className="divide-y">
                        {requests.map((item) => (
                            <article
                                key={item.id}
                                className="grid gap-3 p-4 lg:grid-cols-[1fr_auto]"
                            >
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-medium">
                                            {item.reference_number}
                                        </span>
                                        <Badge variant={tone(item.priority)}>
                                            {humanizeStatus(item.priority)}
                                        </Badge>
                                        <Badge variant={tone(item.status)}>
                                            {humanizeStatus(item.status)}
                                        </Badge>
                                    </div>
                                    <p className="mt-1 font-medium">
                                        {item.subject} · {item.vehicle.name}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Requested by {item.requester.name} —{' '}
                                        {item.description}
                                    </p>
                                </div>
                                {canManage && item.status === 'pending' && (
                                    <Form
                                        {...review.form(item)}
                                        className="flex min-w-64 items-end gap-2"
                                    >
                                        {({ errors, processing }) => (
                                            <>
                                                <div className="grid grow gap-1">
                                                    <input
                                                        name="review_notes"
                                                        placeholder="Review notes"
                                                        className="h-9 rounded-md border bg-background px-3 text-sm"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.review_notes
                                                        }
                                                    />
                                                </div>
                                                <Button
                                                    name="decision"
                                                    value="approved"
                                                    size="sm"
                                                    disabled={processing}
                                                >
                                                    Approve
                                                </Button>
                                                <Button
                                                    name="decision"
                                                    value="rejected"
                                                    variant="destructive"
                                                    size="sm"
                                                    disabled={processing}
                                                >
                                                    Reject
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                )}
                                {item.work_order && (
                                    <Button asChild size="sm" variant="outline">
                                        <Link href={show(item.work_order.id)}>
                                            {item.work_order.reference_number}
                                        </Link>
                                    </Button>
                                )}
                            </article>
                        ))}
                        {!requests.length && (
                            <p className="p-6 text-center text-sm text-muted-foreground">
                                No maintenance requests yet.
                            </p>
                        )}
                    </div>
                </section>
                <section className="overflow-hidden rounded-xl border">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b p-4">
                        <div>
                            <h2 className="font-semibold">
                                Work orders and maintenance history
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Completed and cancelled records remain available
                                here.
                            </p>
                        </div>
                        {canManage && (
                            <WorkOrderModal
                                vehicles={vehicles}
                                requests={approvedRequests}
                                schedules={schedules as WorkOrderOption[]}
                            />
                        )}
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/40 text-left">
                                <tr>
                                    <th className="p-3">Work order</th>
                                    <th className="p-3">Vehicle</th>
                                    <th className="p-3">Schedule</th>
                                    <th className="p-3">Completion</th>
                                    <th className="p-3">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {workOrders.map((item) => (
                                    <tr key={item.id}>
                                        <td className="p-3">
                                            <Link
                                                className="font-medium underline-offset-4 hover:underline"
                                                href={show(item.id)}
                                            >
                                                {item.reference_number}
                                            </Link>
                                            <p className="text-muted-foreground">
                                                {item.title}
                                            </p>
                                        </td>
                                        <td className="p-3">
                                            {item.vehicle.name}
                                        </td>
                                        <td className="p-3">
                                            {item.schedule?.service_type ??
                                                'Corrective / unscheduled'}
                                        </td>
                                        <td className="p-3">
                                            {item.completed_at ? (
                                                <>
                                                    <p>
                                                        {formatDate(
                                                            item.completed_at,
                                                            true,
                                                        )}
                                                    </p>
                                                    <p className="text-muted-foreground">
                                                        {formatMileage(
                                                            item.completion_mileage,
                                                        )}{' '}
                                                        ·{' '}
                                                        {formatCurrency(
                                                            item.cost,
                                                        )}
                                                    </p>
                                                </>
                                            ) : (
                                                formatDate(item.scheduled_start)
                                            )}
                                        </td>
                                        <td className="p-3">
                                            <Badge variant={tone(item.status)}>
                                                {humanizeStatus(item.status)}
                                            </Badge>
                                        </td>
                                    </tr>
                                ))}
                                {!workOrders.length && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="p-6 text-center text-muted-foreground"
                                        >
                                            No work orders yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </>
    );
}
MaintenanceIndex.layout = {
    breadcrumbs: [{ title: 'Maintenance', href: MaintenanceController() }],
};
