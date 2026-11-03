import { Form, Head, Link } from '@inertiajs/react';
import {
    create,
    index,
    store,
} from '@/actions/App/Http/Controllers/VehicleDriverAssignmentController';
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

const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs focus-visible:outline-2 focus-visible:outline-ring';

type Props = {
    vehicles: { id: number; name: string; plate_number: string | null }[];
    drivers: {
        id: number;
        employee_name: string;
        employee_number: string | null;
        license_number: string;
    }[];
    defaults: { vehicle_id?: number; driver_id?: number };
    today: string;
};

export default function AssignmentCreate({
    vehicles,
    drivers,
    defaults,
    today,
}: Props) {
    return (
        <>
            <Head title="Assign driver" />
            <div className="mx-auto grid w-full max-w-3xl gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        Assign driver to vehicle
                    </h1>
                    <Button asChild variant="outline">
                        <Link href={index()}>Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Assignment details</CardTitle>
                        <CardDescription>
                            Both assignment types reserve the vehicle and driver
                            for the selected dates. End dates are inclusive.
                            Leave the end date blank for an ongoing assignment.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form {...store.form()}>
                            {({ processing, errors }) => (
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
                                            defaultValue={
                                                defaults.vehicle_id ?? ''
                                            }
                                            className={selectClass}
                                        >
                                            <option value="" disabled>
                                                Select a vehicle
                                            </option>
                                            {vehicles.map((vehicle) => (
                                                <option
                                                    key={vehicle.id}
                                                    value={vehicle.id}
                                                >
                                                    {vehicle.name} ·{' '}
                                                    {vehicle.plate_number ||
                                                        `Vehicle #${vehicle.id}`}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={errors.vehicle_id}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="driver_id">
                                            Driver
                                        </Label>
                                        <select
                                            id="driver_id"
                                            name="driver_id"
                                            required
                                            defaultValue={
                                                defaults.driver_id ?? ''
                                            }
                                            className={selectClass}
                                        >
                                            <option value="" disabled>
                                                Select a registered driver
                                            </option>
                                            {drivers.map((driver) => (
                                                <option
                                                    key={driver.id}
                                                    value={driver.id}
                                                >
                                                    {driver.employee_name} ·{' '}
                                                    {driver.employee_number ||
                                                        driver.license_number}
                                                </option>
                                            ))}
                                        </select>
                                        <p className="text-xs text-muted-foreground">
                                            The driver must be active, approved,
                                            and have a valid license.
                                            Eligibility and overlapping dates
                                            are checked when saving.
                                        </p>
                                        <InputError
                                            message={errors.driver_id}
                                        />
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="start_date">
                                                Start date
                                            </Label>
                                            <Input
                                                id="start_date"
                                                name="start_date"
                                                type="date"
                                                required
                                                defaultValue={today}
                                            />
                                            <InputError
                                                message={errors.start_date}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="end_date">
                                                End date (optional)
                                            </Label>
                                            <Input
                                                id="end_date"
                                                name="end_date"
                                                type="date"
                                            />
                                            <InputError
                                                message={errors.end_date}
                                            />
                                        </div>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="assignment_type">
                                            Assignment type
                                        </Label>
                                        <select
                                            id="assignment_type"
                                            name="assignment_type"
                                            className={selectClass}
                                            defaultValue="primary"
                                        >
                                            <option value="primary">
                                                Primary
                                            </option>
                                            <option value="temporary">
                                                Temporary
                                            </option>
                                        </select>
                                        <InputError
                                            message={errors.assignment_type}
                                        />
                                    </div>
                                    {(!vehicles.length || !drivers.length) && (
                                        <p className="text-sm text-muted-foreground">
                                            Import a vehicle and register a
                                            driver before creating an
                                            assignment.
                                        </p>
                                    )}
                                    <Button
                                        disabled={
                                            processing ||
                                            !vehicles.length ||
                                            !drivers.length
                                        }
                                    >
                                        {processing
                                            ? 'Saving…'
                                            : 'Save assignment'}
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
AssignmentCreate.layout = {
    breadcrumbs: [
        { title: 'Assignments', href: index() },
        { title: 'Assign driver', href: create() },
    ],
};
