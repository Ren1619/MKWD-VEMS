import { Form, Head, Link } from '@inertiajs/react';
import MaintenanceController from '@/actions/App/Http/Controllers/MaintenanceController';
import {
    cancel,
    complete,
    show,
    start,
} from '@/actions/App/Http/Controllers/MaintenanceWorkOrderController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    formatCurrency,
    formatDate,
    formatMileage,
    humanizeStatus,
} from '@/lib/vehicles';

type Props = {
    workOrder: {
        id: number;
        reference_number: string;
        status: string;
        title: string;
        instructions: string;
        service_provider: string | null;
        scheduled_start: string | null;
        scheduled_end: string | null;
        started_at: string | null;
        completed_at: string | null;
        completion_mileage: number | null;
        work_performed: string | null;
        cost: string | null;
        cancellation_reason: string | null;
        vehicle: {
            name: string;
            plate_number: string | null;
            current_mileage: number;
            operational_status: string;
        };
        maintenance_request: {
            reference_number: string;
            subject: string;
        } | null;
        schedule: {
            service_type: string;
            next_due_date: string | null;
            next_due_mileage: number | null;
        } | null;
        opener: { name: string };
        completer: { name: string } | null;
        canceller: { name: string } | null;
    };
    canManage: boolean;
    now: string;
};
export default function WorkOrderShow({ workOrder, canManage, now }: Props) {
    const active = ['open', 'in_progress'].includes(workOrder.status);

    return (
        <>
            <Head title={workOrder.reference_number} />
            <div className="grid gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-sm text-muted-foreground">
                            Maintenance work order
                        </p>
                        <h1 className="text-2xl font-semibold">
                            {workOrder.reference_number}
                        </h1>
                        <Badge variant="outline">
                            {humanizeStatus(workOrder.status)}
                        </Badge>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={MaintenanceController()}>
                            Maintenance dashboard
                        </Link>
                    </Button>
                </div>
                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>{workOrder.title}</CardTitle>
                            <CardDescription>
                                {workOrder.vehicle.name} ·{' '}
                                {workOrder.vehicle.plate_number ?? 'No plate'} ·{' '}
                                {humanizeStatus(
                                    workOrder.vehicle.operational_status,
                                )}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-5">
                            <div>
                                <p className="text-xs text-muted-foreground uppercase">
                                    Instructions
                                </p>
                                <p className="whitespace-pre-wrap">
                                    {workOrder.instructions}
                                </p>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p className="text-xs text-muted-foreground uppercase">
                                        Provider
                                    </p>
                                    <p>
                                        {workOrder.service_provider ??
                                            'Internal / not specified'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground uppercase">
                                        Schedule
                                    </p>
                                    <p>
                                        {formatDate(workOrder.scheduled_start)}{' '}
                                        – {formatDate(workOrder.scheduled_end)}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground uppercase">
                                        Source
                                    </p>
                                    <p>
                                        {workOrder.maintenance_request
                                            ?.reference_number ??
                                            workOrder.schedule?.service_type ??
                                            'Direct work order'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground uppercase">
                                        Opened by
                                    </p>
                                    <p>{workOrder.opener.name}</p>
                                </div>
                            </div>
                            {workOrder.status === 'completed' && (
                                <div className="rounded-lg border p-4">
                                    <h3 className="font-semibold">
                                        Completion record
                                    </h3>
                                    <p className="mt-2 whitespace-pre-wrap">
                                        {workOrder.work_performed}
                                    </p>
                                    <div className="mt-3 grid gap-2 text-sm sm:grid-cols-3">
                                        <p>
                                            {formatDate(
                                                workOrder.completed_at,
                                                true,
                                            )}
                                        </p>
                                        <p>
                                            {formatMileage(
                                                workOrder.completion_mileage,
                                            )}
                                        </p>
                                        <p>{formatCurrency(workOrder.cost)}</p>
                                    </div>
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        Recorded by {workOrder.completer?.name}
                                    </p>
                                </div>
                            )}
                            {workOrder.status === 'cancelled' && (
                                <div className="rounded-lg border border-destructive/40 p-4">
                                    <h3 className="font-semibold">
                                        Cancellation record
                                    </h3>
                                    <p>{workOrder.cancellation_reason}</p>
                                    <p className="text-xs text-muted-foreground">
                                        Recorded by {workOrder.canceller?.name}
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                    {canManage && active && (
                        <div className="grid content-start gap-4">
                            {workOrder.status === 'open' && (
                                <Form {...start.form(workOrder)}>
                                    <Button className="w-full">
                                        Start work
                                    </Button>
                                </Form>
                            )}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Complete work</CardTitle>
                                    <CardDescription>
                                        Completion mileage is entered manually
                                        and preserved.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Form {...complete.form(workOrder)}>
                                        {({ errors, processing }) => (
                                            <fieldset
                                                disabled={processing}
                                                className="grid gap-3"
                                            >
                                                <div className="grid gap-1">
                                                    <Label htmlFor="completed_at">
                                                        Completed at
                                                    </Label>
                                                    <Input
                                                        id="completed_at"
                                                        name="completed_at"
                                                        type="datetime-local"
                                                        required
                                                        defaultValue={now}
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.completed_at
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-1">
                                                    <Label htmlFor="completion_mileage">
                                                        Odometer
                                                    </Label>
                                                    <Input
                                                        id="completion_mileage"
                                                        name="completion_mileage"
                                                        type="number"
                                                        min={
                                                            workOrder.vehicle
                                                                .current_mileage
                                                        }
                                                        required
                                                        defaultValue={
                                                            workOrder.vehicle
                                                                .current_mileage
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.completion_mileage
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-1">
                                                    <Label htmlFor="work_performed">
                                                        Work performed
                                                    </Label>
                                                    <textarea
                                                        id="work_performed"
                                                        name="work_performed"
                                                        rows={4}
                                                        required
                                                        className="rounded-md border bg-background p-3 text-sm"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.work_performed
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-1">
                                                    <Label htmlFor="cost">
                                                        Cost (PHP)
                                                    </Label>
                                                    <Input
                                                        id="cost"
                                                        name="cost"
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                    />
                                                    <InputError
                                                        message={errors.cost}
                                                    />
                                                </div>
                                                <Button disabled={processing}>
                                                    Complete work order
                                                </Button>
                                            </fieldset>
                                        )}
                                    </Form>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle>Cancel work order</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Form {...cancel.form(workOrder)}>
                                        {({ errors, processing }) => (
                                            <fieldset
                                                disabled={processing}
                                                className="grid gap-3"
                                            >
                                                <textarea
                                                    name="cancellation_reason"
                                                    required
                                                    rows={2}
                                                    placeholder="Reason for cancellation"
                                                    className="rounded-md border bg-background p-3 text-sm"
                                                />
                                                <InputError
                                                    message={
                                                        errors.cancellation_reason
                                                    }
                                                />
                                                <Button variant="destructive">
                                                    Cancel work order
                                                </Button>
                                            </fieldset>
                                        )}
                                    </Form>
                                </CardContent>
                            </Card>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

WorkOrderShow.layout = (page: { props: Props }) => ({
    breadcrumbs: [
        { title: 'Maintenance', href: MaintenanceController() },
        {
            title: page.props.workOrder.reference_number,
            href: show(page.props.workOrder.id),
        },
    ],
});
