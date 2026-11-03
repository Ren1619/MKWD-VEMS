import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { store } from '@/actions/App/Http/Controllers/VehicleDriverAssignmentController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Vehicle = { id: number; name: string; plate_number: string | null };
type Driver = {
    id: number;
    employee_name: string;
    employee_number: string | null;
    license_number: string;
};
export function AssignmentModal({
    vehicles,
    drivers,
    defaults,
    today,
    openByDefault = false,
}: {
    vehicles: Vehicle[];
    drivers: Driver[];
    defaults: { vehicle_id?: number; driver_id?: number };
    today: string;
    openByDefault?: boolean;
}) {
    const [open, setOpen] = useState(openByDefault);
    const selectClass =
        'h-9 w-full rounded-md border border-input bg-background px-3 text-sm';

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>Assign driver</Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Assign driver to vehicle</DialogTitle>
                    <DialogDescription>
                        Both assignment types reserve the vehicle and driver for
                        inclusive selected dates.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...store.form()}
                    noValidate
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                >
                    {({ errors, processing }) => (
                        <fieldset disabled={processing} className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="assign_vehicle_id">
                                    Vehicle
                                </Label>
                                <select
                                    id="assign_vehicle_id"
                                    name="vehicle_id"
                                    defaultValue={defaults.vehicle_id ?? ''}
                                    className={selectClass}
                                >
                                    <option value="" disabled>
                                        Select vehicle
                                    </option>
                                    {vehicles.map((v) => (
                                        <option key={v.id} value={v.id}>
                                            {v.name} ·{' '}
                                            {v.plate_number ?? `#${v.id}`}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.vehicle_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="assign_driver_id">Driver</Label>
                                <select
                                    id="assign_driver_id"
                                    name="driver_id"
                                    defaultValue={defaults.driver_id ?? ''}
                                    className={selectClass}
                                >
                                    <option value="" disabled>
                                        Select registered driver
                                    </option>
                                    {drivers.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.employee_name} ·{' '}
                                            {d.employee_number ??
                                                d.license_number}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.driver_id} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="assign_start_date">
                                        Start date
                                    </Label>
                                    <Input
                                        id="assign_start_date"
                                        name="start_date"
                                        type="date"
                                        defaultValue={today}
                                    />
                                    <InputError message={errors.start_date} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="assign_end_date">
                                        End date (optional)
                                    </Label>
                                    <Input
                                        id="assign_end_date"
                                        name="end_date"
                                        type="date"
                                    />
                                    <InputError message={errors.end_date} />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="assign_type">
                                    Assignment type
                                </Label>
                                <select
                                    id="assign_type"
                                    name="assignment_type"
                                    defaultValue="primary"
                                    className={selectClass}
                                >
                                    <option value="primary">Primary</option>
                                    <option value="temporary">Temporary</option>
                                </select>
                                <InputError message={errors.assignment_type} />
                            </div>
                            <div className="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    disabled={
                                        processing ||
                                        !vehicles.length ||
                                        !drivers.length
                                    }
                                >
                                    {processing ? 'Saving…' : 'Save assignment'}
                                </Button>
                            </div>
                        </fieldset>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
