import { Form } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { store as storeRequest } from '@/actions/App/Http/Controllers/MaintenanceRequestController';
import { store as storeSchedule } from '@/actions/App/Http/Controllers/MaintenanceScheduleController';
import { store as storeWorkOrder } from '@/actions/App/Http/Controllers/MaintenanceWorkOrderController';
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
import { humanizeStatus } from '@/lib/vehicles';

export type MaintenanceVehicle = {
    id: number;
    name: string;
    plate_number: string | null;
    current_mileage: number;
    operational_status: string;
};
export type WorkOrderOption = {
    id: number;
    vehicle_id: number;
    reference_number?: string;
    subject?: string;
    service_type?: string;
    vehicle: { name: string };
};
const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm';

export function MaintenanceRequestModal({
    vehicles,
}: {
    vehicles: MaintenanceVehicle[];
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline">
                    <Plus aria-hidden="true" />
                    Request maintenance
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Request maintenance</DialogTitle>
                    <DialogDescription>
                        Report a fault, inspection finding, or maintenance need
                        for review.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...storeRequest.form()}
                    noValidate
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                >
                    {({ errors, processing }) => (
                        <fieldset disabled={processing} className="grid gap-4">
                            <VehicleSelect
                                vehicles={vehicles}
                                id="request_vehicle_id"
                                error={errors.vehicle_id}
                            />
                            <div className="grid gap-2">
                                <Label htmlFor="request_priority">
                                    Priority
                                </Label>
                                <select
                                    id="request_priority"
                                    name="priority"
                                    defaultValue="normal"
                                    className={selectClass}
                                >
                                    {['low', 'normal', 'high', 'critical'].map(
                                        (v) => (
                                            <option key={v} value={v}>
                                                {humanizeStatus(v)}
                                            </option>
                                        ),
                                    )}
                                </select>
                                <InputError message={errors.priority} />
                            </div>
                            <TextField
                                id="request_subject"
                                name="subject"
                                label="Subject"
                                error={errors.subject}
                            />
                            <div className="grid gap-2">
                                <Label htmlFor="request_description">
                                    Details
                                </Label>
                                <textarea
                                    id="request_description"
                                    name="description"
                                    rows={5}
                                    className="rounded-md border bg-background p-3 text-sm"
                                />
                                <InputError message={errors.description} />
                            </div>
                            <ModalActions
                                processing={processing}
                                close={() => setOpen(false)}
                                label="Submit request"
                            />
                        </fieldset>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export function MaintenanceScheduleModal({
    vehicles,
}: {
    vehicles: MaintenanceVehicle[];
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline">
                    <Plus aria-hidden="true" />
                    Preventive maintenance schedule
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Preventive maintenance schedule</DialogTitle>
                    <DialogDescription>
                        Set a date interval, mileage interval, or both. Either
                        threshold can make service due.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...storeSchedule.form()}
                    noValidate
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                >
                    {({ errors, processing }) => (
                        <fieldset disabled={processing} className="grid gap-4">
                            <VehicleSelect
                                vehicles={vehicles}
                                id="schedule_vehicle_id"
                                error={errors.vehicle_id}
                                showMileage
                            />
                            <TextField
                                id="service_type"
                                name="service_type"
                                label="Service type"
                                error={errors.service_type}
                            />
                            <div className="grid gap-2">
                                <Label htmlFor="schedule_description">
                                    Scope (optional)
                                </Label>
                                <textarea
                                    id="schedule_description"
                                    name="description"
                                    rows={3}
                                    className="rounded-md border bg-background p-3 text-sm"
                                />
                                <InputError message={errors.description} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <TextField
                                    id="interval_days"
                                    name="interval_days"
                                    label="Interval in days"
                                    type="number"
                                    error={errors.interval_days}
                                />
                                <TextField
                                    id="interval_km"
                                    name="interval_km"
                                    label="Interval in kilometers"
                                    type="number"
                                    error={errors.interval_km}
                                />
                                <TextField
                                    id="last_completed_date"
                                    name="last_completed_date"
                                    label="Last completion date"
                                    type="date"
                                    error={errors.last_completed_date}
                                />
                                <TextField
                                    id="last_completed_mileage"
                                    name="last_completed_mileage"
                                    label="Mileage at last completion"
                                    type="number"
                                    error={errors.last_completed_mileage}
                                />
                            </div>
                            <ModalActions
                                processing={processing}
                                close={() => setOpen(false)}
                                label="Create schedule"
                            />
                        </fieldset>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export function WorkOrderModal({
    vehicles,
    requests,
    schedules,
}: {
    vehicles: MaintenanceVehicle[];
    requests: WorkOrderOption[];
    schedules: WorkOrderOption[];
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>
                    <Plus aria-hidden="true" />
                    Create work order
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Create work order</DialogTitle>
                    <DialogDescription>
                        Link an approved request or preventive schedule when
                        applicable.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...storeWorkOrder.form()}
                    noValidate
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                >
                    {({ errors, processing }) => (
                        <fieldset disabled={processing} className="grid gap-4">
                            <VehicleSelect
                                vehicles={vehicles}
                                id="work_vehicle_id"
                                error={errors.vehicle_id}
                            />
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="work_request_id">
                                        Approved request (optional)
                                    </Label>
                                    <select
                                        id="work_request_id"
                                        name="maintenance_request_id"
                                        defaultValue=""
                                        className={selectClass}
                                    >
                                        <option value="">None</option>
                                        {requests.map((r) => (
                                            <option key={r.id} value={r.id}>
                                                {r.reference_number} ·{' '}
                                                {r.vehicle.name} · {r.subject}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError
                                        message={errors.maintenance_request_id}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="work_schedule_id">
                                        Preventive schedule (optional)
                                    </Label>
                                    <select
                                        id="work_schedule_id"
                                        name="maintenance_schedule_id"
                                        defaultValue=""
                                        className={selectClass}
                                    >
                                        <option value="">None</option>
                                        {schedules.map((s) => (
                                            <option key={s.id} value={s.id}>
                                                {s.vehicle.name} ·{' '}
                                                {s.service_type}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError
                                        message={errors.maintenance_schedule_id}
                                    />
                                </div>
                            </div>
                            <TextField
                                id="work_title"
                                name="title"
                                label="Title"
                                error={errors.title}
                            />
                            <div className="grid gap-2">
                                <Label htmlFor="work_instructions">
                                    Work instructions
                                </Label>
                                <textarea
                                    id="work_instructions"
                                    name="instructions"
                                    rows={4}
                                    className="rounded-md border bg-background p-3 text-sm"
                                />
                                <InputError message={errors.instructions} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-3">
                                <TextField
                                    id="service_provider"
                                    name="service_provider"
                                    label="Service provider"
                                    error={errors.service_provider}
                                />
                                <TextField
                                    id="scheduled_start"
                                    name="scheduled_start"
                                    label="Scheduled start"
                                    type="date"
                                    error={errors.scheduled_start}
                                />
                                <TextField
                                    id="scheduled_end"
                                    name="scheduled_end"
                                    label="Scheduled end"
                                    type="date"
                                    error={errors.scheduled_end}
                                />
                            </div>
                            <ModalActions
                                processing={processing}
                                close={() => setOpen(false)}
                                label="Create work order"
                            />
                        </fieldset>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function VehicleSelect({
    vehicles,
    id,
    error,
    showMileage = false,
}: {
    vehicles: MaintenanceVehicle[];
    id: string;
    error?: string;
    showMileage?: boolean;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>Vehicle</Label>
            <select
                id={id}
                name="vehicle_id"
                defaultValue=""
                className={selectClass}
            >
                <option value="" disabled>
                    Select vehicle
                </option>
                {vehicles.map((v) => (
                    <option key={v.id} value={v.id}>
                        {v.name} · {v.plate_number ?? `#${v.id}`}
                        {showMileage
                            ? ` · ${v.current_mileage.toLocaleString()} km`
                            : ''}
                    </option>
                ))}
            </select>
            <InputError message={error} />
        </div>
    );
}
function TextField({
    id,
    name,
    label,
    error,
    type = 'text',
}: {
    id: string;
    name: string;
    label: string;
    error?: string;
    type?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <Input
                id={id}
                name={name}
                type={type}
            />
            <InputError message={error} />
        </div>
    );
}
function ModalActions({
    processing,
    close,
    label,
}: {
    processing: boolean;
    close: () => void;
    label: string;
}) {
    return (
        <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={close}>
                Cancel
            </Button>
            <Button disabled={processing}>
                {processing ? 'Saving…' : label}
            </Button>
        </div>
    );
}
