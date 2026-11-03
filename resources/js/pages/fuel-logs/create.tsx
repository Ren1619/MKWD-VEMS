import { Form, Head, Link } from '@inertiajs/react';
import {
    create,
    index,
    store,
} from '@/actions/App/Http/Controllers/FuelLogController';
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
        fuel_type: string | null;
        current_mileage: number;
    }[];
    now: string;
};
const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm';
export default function CreateFuelLog({ vehicles, now }: Props) {
    return (
        <>
            <Head title="Record refueling" />
            <div className="mx-auto grid w-full max-w-3xl gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Record refueling
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Enter the actual pump quantity, cost, and dashboard
                            odometer.
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={index()}>Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Fuel transaction</CardTitle>
                        <CardDescription>
                            The odometer reading updates vehicle mileage and is
                            also preserved in Odometer History.
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
                                                        `#${v.id}`}{' '}
                                                    ·{' '}
                                                    {v.current_mileage.toLocaleString()}{' '}
                                                    km ·{' '}
                                                    {v.fuel_type ??
                                                        'Fuel not set'}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={errors.vehicle_id}
                                        />
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="quantity_liters">
                                                Fuel quantity (liters)
                                            </Label>
                                            <Input
                                                id="quantity_liters"
                                                name="quantity_liters"
                                                type="number"
                                                min="0.001"
                                                step="0.001"
                                                required
                                            />
                                            <InputError
                                                message={errors.quantity_liters}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="total_cost">
                                                Total cost (PHP)
                                            </Label>
                                            <Input
                                                id="total_cost"
                                                name="total_cost"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                required
                                            />
                                            <InputError
                                                message={errors.total_cost}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="odometer">
                                                Odometer (km)
                                            </Label>
                                            <Input
                                                id="odometer"
                                                name="odometer"
                                                type="number"
                                                min="0"
                                                required
                                            />
                                            <InputError
                                                message={errors.odometer}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="refueled_at">
                                                Refueling date and time
                                            </Label>
                                            <Input
                                                id="refueled_at"
                                                name="refueled_at"
                                                type="datetime-local"
                                                max={now}
                                                defaultValue={now}
                                                required
                                            />
                                            <InputError
                                                message={errors.refueled_at}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="station">
                                                Fuel station (optional)
                                            </Label>
                                            <Input
                                                id="station"
                                                name="station"
                                            />
                                            <InputError
                                                message={errors.station}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="receipt_reference">
                                                Receipt/reference (optional)
                                            </Label>
                                            <Input
                                                id="receipt_reference"
                                                name="receipt_reference"
                                            />
                                            <InputError
                                                message={
                                                    errors.receipt_reference
                                                }
                                            />
                                        </div>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="notes">
                                            Notes (optional)
                                        </Label>
                                        <textarea
                                            id="notes"
                                            name="notes"
                                            rows={3}
                                            className="rounded-md border bg-background p-3 text-sm"
                                        />
                                        <InputError message={errors.notes} />
                                    </div>
                                    <Button
                                        disabled={
                                            processing || !vehicles.length
                                        }
                                    >
                                        {processing
                                            ? 'Recording…'
                                            : 'Record fuel log'}
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
CreateFuelLog.layout = {
    breadcrumbs: [
        { title: 'Fuel Logs', href: index() },
        { title: 'Record refueling', href: create() },
    ],
};
