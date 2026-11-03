import { Form, Head, Link } from '@inertiajs/react';
import MaintenanceController from '@/actions/App/Http/Controllers/MaintenanceController';
import {
    create,
    store,
} from '@/actions/App/Http/Controllers/MaintenanceRequestController';
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
import { humanizeStatus } from '@/lib/vehicles';

type Props = {
    vehicles: {
        id: number;
        name: string;
        plate_number: string | null;
        operational_status: string;
    }[];
};
const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm';
export default function CreateMaintenanceRequest({ vehicles }: Props) {
    return (
        <>
            <Head title="Maintenance request" />
            <div className="mx-auto grid w-full max-w-3xl gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Request maintenance
                    </h1>
                    <Button asChild variant="outline">
                        <Link href={MaintenanceController()}>Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Vehicle concern</CardTitle>
                        <CardDescription>
                            Report a fault, inspection finding, or maintenance
                            need for review.
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
                                                    {humanizeStatus(
                                                        v.operational_status,
                                                    )}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={errors.vehicle_id}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="priority">
                                            Priority
                                        </Label>
                                        <select
                                            id="priority"
                                            name="priority"
                                            defaultValue="normal"
                                            className={selectClass}
                                        >
                                            {[
                                                'low',
                                                'normal',
                                                'high',
                                                'critical',
                                            ].map((value) => (
                                                <option
                                                    key={value}
                                                    value={value}
                                                >
                                                    {humanizeStatus(value)}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.priority} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="subject">Subject</Label>
                                        <Input
                                            id="subject"
                                            name="subject"
                                            required
                                            placeholder="Briefly describe the concern"
                                        />
                                        <InputError message={errors.subject} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="description">
                                            Details
                                        </Label>
                                        <textarea
                                            id="description"
                                            name="description"
                                            required
                                            rows={5}
                                            className="rounded-md border bg-background p-3 text-sm"
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>
                                    <Button
                                        disabled={
                                            processing || !vehicles.length
                                        }
                                    >
                                        {processing
                                            ? 'Submitting…'
                                            : 'Submit request'}
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
CreateMaintenanceRequest.layout = {
    breadcrumbs: [
        { title: 'Maintenance', href: MaintenanceController() },
        { title: 'Request maintenance', href: create() },
    ],
};
