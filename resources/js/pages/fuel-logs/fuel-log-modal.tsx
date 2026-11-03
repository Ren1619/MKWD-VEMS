import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { store } from '@/actions/App/Http/Controllers/FuelLogController';
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

export type FuelVehicle = {
    id: number;
    name: string;
    plate_number: string | null;
    fuel_type: string | null;
    current_mileage: number;
};
export function FuelLogModal({
    vehicles,
    now,
}: {
    vehicles: FuelVehicle[];
    now: string;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>Record refueling</Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Record refueling</DialogTitle>
                    <DialogDescription>
                        Enter actual pump values and dashboard odometer. The
                        reading is preserved in Odometer History.
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
                                <Label htmlFor="fuel_vehicle_id">Vehicle</Label>
                                <select
                                    id="fuel_vehicle_id"
                                    name="vehicle_id"
                                    defaultValue=""
                                    className="h-9 rounded-md border bg-background px-3 text-sm"
                                >
                                    <option value="" disabled>
                                        Select vehicle
                                    </option>
                                    {vehicles.map((v) => (
                                        <option key={v.id} value={v.id}>
                                            {v.name} ·{' '}
                                            {v.plate_number ?? `#${v.id}`} ·{' '}
                                            {v.current_mileage.toLocaleString()}{' '}
                                            km
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.vehicle_id} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field
                                    label="Fuel quantity (liters)"
                                    name="quantity_liters"
                                    type="number"
                                    error={errors.quantity_liters}
                                />
                                <Field
                                    label="Total cost (PHP)"
                                    name="total_cost"
                                    type="number"
                                    error={errors.total_cost}
                                />
                                <Field
                                    label="Odometer (km)"
                                    name="odometer"
                                    type="number"
                                    error={errors.odometer}
                                />
                                <Field
                                    label="Refueling date and time"
                                    name="refueled_at"
                                    type="datetime-local"
                                    defaultValue={now}
                                    error={errors.refueled_at}
                                />
                                <Field
                                    label="Fuel station (optional)"
                                    name="station"
                                    error={errors.station}
                                />
                                <Field
                                    label="Receipt/reference (optional)"
                                    name="receipt_reference"
                                    error={errors.receipt_reference}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="fuel_notes">
                                    Notes (optional)
                                </Label>
                                <textarea
                                    id="fuel_notes"
                                    name="notes"
                                    rows={3}
                                    className="rounded-md border bg-background p-3 text-sm"
                                />
                                <InputError message={errors.notes} />
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
                                    disabled={processing || !vehicles.length}
                                >
                                    {processing
                                        ? 'Recording…'
                                        : 'Record fuel log'}
                                </Button>
                            </div>
                        </fieldset>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
function Field({
    label,
    name,
    error,
    ...props
}: {
    label: string;
    name: string;
    error?: string;
    type?: string;
    defaultValue?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={`fuel_${name}`}>{label}</Label>
            <Input
                id={`fuel_${name}`}
                name={name}
                {...props}
            />
            <InputError message={error} />
        </div>
    );
}
