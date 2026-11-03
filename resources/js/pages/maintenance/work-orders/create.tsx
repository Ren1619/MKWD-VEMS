import { Form, Head, Link } from '@inertiajs/react';
import MaintenanceController from '@/actions/App/Http/Controllers/MaintenanceController';
import {
    create,
    store,
} from '@/actions/App/Http/Controllers/MaintenanceWorkOrderController';
import InputError from '@/components/input-error';
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

type Vehicle = { id: number; name: string; plate_number: string | null };
type Props = {
    vehicles: Vehicle[];
    requests: {
        id: number;
        reference_number: string;
        vehicle_id: number;
        subject: string;
        vehicle: Vehicle;
    }[];
    schedules: {
        id: number;
        vehicle_id: number;
        service_type: string;
        vehicle: Vehicle;
    }[];
};
const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm';
export default function CreateWorkOrder({
    vehicles,
    requests,
    schedules,
}: Props) {
    return (
        <>
            <Head title="New work order" />
            <div className="mx-auto grid w-full max-w-3xl gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Create work order
                    </h1>
                    <Button asChild variant="outline">
                        <Link href={MaintenanceController()}>Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Work instructions</CardTitle>
                        <CardDescription>
                            A work order may be corrective, linked to an
                            approved request, linked to a preventive schedule,
                            or both.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form {...store.form()}>
                            {({ errors, processing }) => (
                                <fieldset
                                    disabled={processing}
                                    className="grid gap-5"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="vehicle_id">
                                            Vehicle
                                        </Label>
                                        <select
                                            id="vehicle_id"
                                            name="vehicle_id"
                                            required
                                            defaultValue=""
                                            className={selectClass}
                                        >
                                            <option value="" disabled>
                                                Select vehicle
                                            </option>
                                            {vehicles.map((v) => (
                                                <option key={v.id} value={v.id}>
                                                    {v.name} ·{' '}
                                                    {v.plate_number ??
                                                        `Vehicle #${v.id}`}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={errors.vehicle_id}
                                        />
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="maintenance_request_id">
                                                Approved request (optional)
                                            </Label>
                                            <select
                                                id="maintenance_request_id"
                                                name="maintenance_request_id"
                                                defaultValue=""
                                                className={selectClass}
                                            >
                                                <option value="">None</option>
                                                {requests.map((r) => (
                                                    <option
                                                        key={r.id}
                                                        value={r.id}
                                                    >
                                                        {r.reference_number} ·{' '}
                                                        {r.vehicle.name} ·{' '}
                                                        {r.subject}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={
                                                    errors.maintenance_request_id
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="maintenance_schedule_id">
                                                Preventive schedule (optional)
                                            </Label>
                                            <select
                                                id="maintenance_schedule_id"
                                                name="maintenance_schedule_id"
                                                defaultValue=""
                                                className={selectClass}
                                            >
                                                <option value="">None</option>
                                                {schedules.map((s) => (
                                                    <option
                                                        key={s.id}
                                                        value={s.id}
                                                    >
                                                        {s.vehicle.name} ·{' '}
                                                        {s.service_type}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={
                                                    errors.maintenance_schedule_id
                                                }
                                            />
                                        </div>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="title">Title</Label>
                                        <Input
                                            id="title"
                                            name="title"
                                            required
                                        />
                                        <InputError message={errors.title} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="instructions">
                                            Work instructions
                                        </Label>
                                        <textarea
                                            id="instructions"
                                            name="instructions"
                                            required
                                            rows={5}
                                            className="rounded-md border bg-background p-3 text-sm"
                                        />
                                        <InputError
                                            message={errors.instructions}
                                        />
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-3">
                                        <div className="grid gap-2">
                                            <Label htmlFor="service_provider">
                                                Service provider
                                            </Label>
                                            <Input
                                                id="service_provider"
                                                name="service_provider"
                                            />
                                            <InputError
                                                message={
                                                    errors.service_provider
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="scheduled_start">
                                                Scheduled start
                                            </Label>
                                            <Input
                                                id="scheduled_start"
                                                name="scheduled_start"
                                                type="date"
                                            />
                                            <InputError
                                                message={errors.scheduled_start}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="scheduled_end">
                                                Scheduled end
                                            </Label>
                                            <Input
                                                id="scheduled_end"
                                                name="scheduled_end"
                                                type="date"
                                            />
                                            <InputError
                                                message={errors.scheduled_end}
                                            />
                                        </div>
                                    </div>
                                    <Button
                                        disabled={
                                            processing || !vehicles.length
                                        }
                                    >
                                        {processing
                                            ? 'Creating…'
                                            : 'Create work order'}
                                    </Button>
                                </fieldset>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
CreateWorkOrder.layout = {
    breadcrumbs: [
        { title: 'Maintenance', href: MaintenanceController() },
        { title: 'New work order', href: create() },
    ],
};
