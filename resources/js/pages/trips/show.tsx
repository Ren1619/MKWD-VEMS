import { Form, Head, Link } from '@inertiajs/react';
import { show as showDriver } from '@/actions/App/Http/Controllers/DriverController';
import {
    complete,
    decide,
    dispatch,
    index,
} from '@/actions/App/Http/Controllers/TripController';
import { show as showVehicle } from '@/actions/App/Http/Controllers/VehicleController';
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
import { formatDate, formatMileage, humanizeStatus } from '@/lib/vehicles';
import type { Trip, TripDriverOption, TripVehicleOption } from '@/types/trip';

const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs focus-visible:outline-2 focus-visible:outline-ring';
const textareaClass =
    'min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs focus-visible:outline-2 focus-visible:outline-ring';

type Props = {
    trip: Trip;
    vehicles: TripVehicleOption[];
    drivers: TripDriverOption[];
    canManage: boolean;
    now: string;
};

function Detail({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid gap-1">
            <dt className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {label}
            </dt>
            <dd className="text-sm break-words">{children}</dd>
        </div>
    );
}

export default function TripShow({
    trip,
    vehicles,
    drivers,
    canManage,
    now,
}: Props) {
    return (
        <>
            <Head title={trip.reference_number} />
            <div className="grid gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="grid gap-2">
                        <p className="text-sm text-muted-foreground">
                            Trip / Dispatch
                        </p>
                        <h1 className="text-2xl font-semibold">
                            {trip.reference_number}
                        </h1>
                        <div>
                            <Badge
                                variant={
                                    trip.status === 'rejected'
                                        ? 'destructive'
                                        : trip.status === 'pending'
                                          ? 'outline'
                                          : 'default'
                                }
                            >
                                {humanizeStatus(trip.status)}
                            </Badge>
                        </div>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={index()}>Back to trips</Link>
                    </Button>
                </div>

                <div className="grid items-start gap-6 xl:grid-cols-3">
                    <Card className="xl:col-span-2">
                        <CardHeader>
                            <CardTitle>Trip details</CardTitle>
                            <CardDescription>
                                Requested by {trip.requester.name} (
                                {trip.requester.email})
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-2">
                            <Detail label="Destination">
                                {trip.destination}
                            </Detail>
                            <Detail label="Schedule">
                                {formatDate(trip.planned_departure, true)} –{' '}
                                {formatDate(trip.planned_return, true)}
                            </Detail>
                            <div className="sm:col-span-2">
                                <Detail label="Purpose">{trip.purpose}</Detail>
                            </div>
                            <div className="sm:col-span-2">
                                <Detail label="Passengers">
                                    {trip.passengers ? (
                                        <span className="whitespace-pre-line">
                                            {trip.passengers}
                                        </span>
                                    ) : (
                                        'No passengers listed'
                                    )}
                                </Detail>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Assigned resources</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <Detail label="Vehicle">
                                {trip.vehicle ? (
                                    <Link
                                        className="underline underline-offset-4"
                                        href={showVehicle(trip.vehicle.id)}
                                    >
                                        {trip.vehicle.name} ·{' '}
                                        {trip.vehicle.plate_number ||
                                            'No plate'}
                                    </Link>
                                ) : (
                                    'Not assigned'
                                )}
                            </Detail>
                            <Detail label="Driver">
                                {trip.driver ? (
                                    <Link
                                        className="underline underline-offset-4"
                                        href={showDriver(trip.driver.id)}
                                    >
                                        {trip.driver.employee_name}
                                    </Link>
                                ) : (
                                    'Not assigned'
                                )}
                            </Detail>
                        </CardContent>
                    </Card>
                </div>

                {trip.status === 'pending' && canManage && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Approve or reject request</CardTitle>
                            <CardDescription>
                                Approval reserves both resources for the planned
                                time. Overlapping trips and conflicting
                                vehicle–driver assignments are blocked.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form {...decide.form(trip.id)}>
                                {({ processing, errors }) => (
                                    <fieldset
                                        disabled={processing}
                                        className="grid gap-5"
                                    >
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="vehicle_id">
                                                    Vehicle for approval
                                                </Label>
                                                <select
                                                    id="vehicle_id"
                                                    name="vehicle_id"
                                                    defaultValue=""
                                                    className={selectClass}
                                                >
                                                    <option value="">
                                                        Select vehicle
                                                    </option>
                                                    {vehicles.map((vehicle) => (
                                                        <option
                                                            key={vehicle.id}
                                                            value={vehicle.id}
                                                        >
                                                            {vehicle.name} ·{' '}
                                                            {vehicle.plate_number ||
                                                                `Vehicle #${vehicle.id}`}{' '}
                                                            ·{' '}
                                                            {formatMileage(
                                                                vehicle.current_mileage,
                                                            )}
                                                        </option>
                                                    ))}
                                                </select>
                                                <InputError
                                                    message={errors.vehicle_id}
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="driver_id">
                                                    Driver for approval
                                                </Label>
                                                <select
                                                    id="driver_id"
                                                    name="driver_id"
                                                    defaultValue=""
                                                    className={selectClass}
                                                >
                                                    <option value="">
                                                        Select driver
                                                    </option>
                                                    {drivers.map((driver) => (
                                                        <option
                                                            key={driver.id}
                                                            value={driver.id}
                                                            disabled={
                                                                !driver.eligible_to_drive
                                                            }
                                                        >
                                                            {
                                                                driver.employee_name
                                                            }{' '}
                                                            ·{' '}
                                                            {
                                                                driver.license_number
                                                            }
                                                            {!driver.eligible_to_drive
                                                                ? ' · Not eligible'
                                                                : ''}
                                                        </option>
                                                    ))}
                                                </select>
                                                <InputError
                                                    message={errors.driver_id}
                                                />
                                            </div>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="decision_notes">
                                                Decision notes
                                            </Label>
                                            <textarea
                                                id="decision_notes"
                                                name="decision_notes"
                                                maxLength={2000}
                                                className={textareaClass}
                                                placeholder="Required when rejecting; optional when approving"
                                            />
                                            <InputError
                                                message={errors.decision_notes}
                                            />
                                        </div>
                                        <InputError message={errors.decision} />
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                name="decision"
                                                value="approved"
                                            >
                                                Approve and assign
                                            </Button>
                                            <Button
                                                name="decision"
                                                value="rejected"
                                                variant="destructive"
                                            >
                                                Reject request
                                            </Button>
                                        </div>
                                    </fieldset>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                {trip.status === 'approved' && canManage && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Record departure</CardTitle>
                            <CardDescription>
                                Confirm the actual departure and vehicle
                                odometer. Eligibility and vehicle status are
                                checked again.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form {...dispatch.form(trip.id)}>
                                {({ processing, errors }) => (
                                    <fieldset
                                        disabled={processing}
                                        className="grid gap-4 md:grid-cols-2"
                                    >
                                        <div className="grid gap-2">
                                            <Label htmlFor="actual_departure">
                                                Actual departure
                                            </Label>
                                            <Input
                                                id="actual_departure"
                                                name="actual_departure"
                                                type="datetime-local"
                                                required
                                                max={now}
                                                defaultValue={now}
                                            />
                                            <InputError
                                                message={
                                                    errors.actual_departure
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="start_odometer">
                                                Start odometer (km)
                                            </Label>
                                            <Input
                                                id="start_odometer"
                                                name="start_odometer"
                                                type="number"
                                                required
                                                min={
                                                    trip.vehicle
                                                        ?.current_mileage ?? 0
                                                }
                                                defaultValue={
                                                    trip.vehicle
                                                        ?.current_mileage ?? 0
                                                }
                                            />
                                            <InputError
                                                message={errors.start_odometer}
                                            />
                                        </div>
                                        <Button
                                            className="md:col-span-2"
                                            disabled={processing}
                                        >
                                            Dispatch vehicle
                                        </Button>
                                    </fieldset>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                {trip.status === 'dispatched' && canManage && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Record return</CardTitle>
                            <CardDescription>
                                Complete the trip and update the vehicle’s
                                current mileage.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form {...complete.form(trip.id)}>
                                {({ processing, errors }) => (
                                    <fieldset
                                        disabled={processing}
                                        className="grid gap-4 md:grid-cols-2"
                                    >
                                        <div className="grid gap-2">
                                            <Label htmlFor="actual_return">
                                                Actual return
                                            </Label>
                                            <Input
                                                id="actual_return"
                                                name="actual_return"
                                                type="datetime-local"
                                                required
                                                min={trip.actual_departure?.slice(
                                                    0,
                                                    16,
                                                )}
                                                max={now}
                                                defaultValue={now}
                                            />
                                            <InputError
                                                message={errors.actual_return}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="end_odometer">
                                                End odometer (km)
                                            </Label>
                                            <Input
                                                id="end_odometer"
                                                name="end_odometer"
                                                type="number"
                                                required
                                                min={trip.start_odometer ?? 0}
                                                defaultValue={
                                                    trip.start_odometer ?? 0
                                                }
                                            />
                                            <InputError
                                                message={errors.end_odometer}
                                            />
                                        </div>
                                        <Button
                                            className="md:col-span-2"
                                            disabled={processing}
                                        >
                                            Complete trip
                                        </Button>
                                    </fieldset>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Dispatch record</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <Detail label="Decision">
                            {trip.decided_at
                                ? `${formatDate(trip.decided_at, true)} by ${trip.decision_maker?.name ?? 'Unknown'}`
                                : 'Awaiting decision'}
                        </Detail>
                        <Detail label="Departure">
                            {trip.actual_departure
                                ? `${formatDate(trip.actual_departure, true)} · ${formatMileage(trip.start_odometer)}`
                                : 'Not departed'}
                        </Detail>
                        <Detail label="Return">
                            {trip.actual_return
                                ? `${formatDate(trip.actual_return, true)} · ${formatMileage(trip.end_odometer)}`
                                : 'Not returned'}
                        </Detail>
                        <Detail label="Distance">
                            {trip.start_odometer !== null &&
                            trip.end_odometer !== null
                                ? formatMileage(
                                      trip.end_odometer - trip.start_odometer,
                                  )
                                : 'Not available'}
                        </Detail>
                        {trip.decision_notes && (
                            <div className="sm:col-span-2 lg:col-span-4">
                                <Detail label="Decision notes">
                                    {trip.decision_notes}
                                </Detail>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
TripShow.layout = { breadcrumbs: [{ title: 'Trips', href: index() }] };
