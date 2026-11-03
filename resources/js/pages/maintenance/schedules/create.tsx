import { Form, Head, Link } from '@inertiajs/react';
import MaintenanceController from '@/actions/App/Http/Controllers/MaintenanceController';
import {
    create,
    store,
} from '@/actions/App/Http/Controllers/MaintenanceScheduleController';
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

type Props = {
    vehicles: {
        id: number;
        name: string;
        plate_number: string | null;
        current_mileage: number;
    }[];
};
const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm';

export default function CreateMaintenanceSchedule({ vehicles }: Props) {
    return (
        <>
            <Head title="New maintenance schedule" />
            <div className="mx-auto grid w-full max-w-3xl gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Preventive maintenance schedule
                    </h1>
                    <Button asChild variant="outline">
                        <Link href={MaintenanceController()}>Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Schedule details</CardTitle>
                        <CardDescription>
                            Set a mileage interval, a date interval, or both.
                            When both are set, either threshold can make service
                            due.
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
                                                        `Vehicle #${v.id}`}{' '}
                                                    ·{' '}
                                                    {v.current_mileage.toLocaleString()}{' '}
                                                    km
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={errors.vehicle_id}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="service_type">
                                            Service type
                                        </Label>
                                        <Input
                                            id="service_type"
                                            name="service_type"
                                            required
                                            placeholder="e.g. Periodic service / oil change"
                                        />
                                        <InputError
                                            message={errors.service_type}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="description">
                                            Scope (optional)
                                        </Label>
                                        <textarea
                                            id="description"
                                            name="description"
                                            rows={3}
                                            className="rounded-md border bg-background p-3 text-sm"
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="interval_days">
                                                Interval in days
                                            </Label>
                                            <Input
                                                id="interval_days"
                                                name="interval_days"
                                                type="number"
                                                min="1"
                                                placeholder="180"
                                            />
                                            <InputError
                                                message={errors.interval_days}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="interval_km">
                                                Interval in kilometers
                                            </Label>
                                            <Input
                                                id="interval_km"
                                                name="interval_km"
                                                type="number"
                                                min="1"
                                                placeholder="5000"
                                            />
                                            <InputError
                                                message={errors.interval_km}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="last_completed_date">
                                                Last completion date
                                            </Label>
                                            <Input
                                                id="last_completed_date"
                                                name="last_completed_date"
                                                type="date"
                                            />
                                            <InputError
                                                message={
                                                    errors.last_completed_date
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="last_completed_mileage">
                                                Mileage at last completion
                                            </Label>
                                            <Input
                                                id="last_completed_mileage"
                                                name="last_completed_mileage"
                                                type="number"
                                                min="0"
                                            />
                                            <InputError
                                                message={
                                                    errors.last_completed_mileage
                                                }
                                            />
                                        </div>
                                    </div>
                                    <Button
                                        disabled={
                                            processing || !vehicles.length
                                        }
                                    >
                                        {processing
                                            ? 'Saving…'
                                            : 'Create schedule'}
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
CreateMaintenanceSchedule.layout = {
    breadcrumbs: [
        { title: 'Maintenance', href: MaintenanceController() },
        { title: 'New schedule', href: create() },
    ],
};
