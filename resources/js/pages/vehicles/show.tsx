import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Clock3,
    Database,
    Gauge,
    Info,
    Pencil,
    RefreshCw,
    Settings2,
    TriangleAlert,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { show as showTrip } from '@/actions/App/Http/Controllers/TripController';
import VehicleController from '@/actions/App/Http/Controllers/VehicleController';
import VehicleSyncController from '@/actions/App/Http/Controllers/VehicleSyncController';
import { CurrentAssignment } from '@/components/assignments/current-assignment';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { OperationalFields } from '@/components/vehicles/operational-fields';
import { StatusBadge } from '@/components/vehicles/status-badge';
import {
    formatCurrency,
    formatDate,
    formatMileage,
    humanizeStatus,
} from '@/lib/vehicles';
import { index as odometerIndex } from '@/routes/odometer';
import type { Vehicle } from '@/types';
import type { Assignment } from '@/types/assignment';
import type { OdometerEntry } from '@/types/odometer';

type Props = {
    vehicle: Vehicle;
    currentAssignment: Assignment | null;
    recentOdometerEntries: OdometerEntry[];
    operationalStatuses: string[];
    fuelTypes: string[];
    canUpdate: boolean;
    canSync: boolean;
};

function ReadOnlyField({ label, value }: { label: string; value: ReactNode }) {
    const hasValue = value !== null && value !== undefined && value !== '';

    return (
        <div className="grid min-w-0 gap-1 rounded-lg border bg-muted/30 p-3">
            <dt className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {label}
            </dt>
            <dd className="min-w-0 text-sm break-words">
                {hasValue ? value : 'Not recorded'}
            </dd>
        </div>
    );
}

function valueWithCode(value: string | null, code: string | null) {
    if (!value && !code) {
        return null;
    }

    return code ? `${value ?? 'Unnamed'} (${code})` : value;
}

export default function VehicleShow({
    vehicle,
    operationalStatuses,
    fuelTypes,
    canUpdate,
    canSync,
    currentAssignment,
    recentOdometerEntries,
}: Props) {
    const { flash } = usePage();
    const identityDetails = [vehicle.brand, vehicle.model, vehicle.vehicle_year]
        .filter(Boolean)
        .join(' · ');

    return (
        <>
            <Head title={vehicle.name} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <section className="rounded-xl border bg-card p-5 shadow-xs md:p-6">
                    <div className="flex flex-col justify-between gap-5 xl:flex-row xl:items-start">
                        <div className="min-w-0 space-y-4">
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Vehicle profile
                                </p>
                                <h1 className="text-2xl font-semibold tracking-tight md:text-3xl">
                                    {vehicle.name}
                                </h1>
                                <p className="text-sm text-muted-foreground md:text-base">
                                    {identityDetails ||
                                        'Vehicle details not recorded'}
                                </p>
                            </div>

                            <div className="flex flex-wrap gap-x-6 gap-y-3 text-sm">
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Plate number
                                    </p>
                                    <p className="font-medium">
                                        {vehicle.plate_number ?? 'Not recorded'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Property number
                                    </p>
                                    <p className="font-medium">
                                        {vehicle.property_number ??
                                            'Not recorded'}
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-3">
                                <div className="flex items-center gap-2">
                                    <span className="text-xs text-muted-foreground">
                                        Operational
                                    </span>
                                    <StatusBadge
                                        value={vehicle.operational_status}
                                    />
                                </div>
                                <div className="flex items-center gap-2">
                                    <span className="text-xs text-muted-foreground">
                                        AMS asset
                                    </span>
                                    <StatusBadge
                                        value={vehicle.ams_asset_status}
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="grid shrink-0 gap-4 sm:grid-cols-2 xl:min-w-xl">
                            <div className="rounded-lg border bg-muted/30 p-4">
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Gauge className="size-4" />
                                    Current mileage
                                </div>
                                <p className="mt-2 text-2xl font-semibold tabular-nums">
                                    {formatMileage(vehicle.current_mileage)}
                                </p>
                            </div>
                            <div className="rounded-lg border bg-muted/30 p-4">
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Clock3 className="size-4" />
                                    Last synced
                                </div>
                                <p className="mt-2 font-medium">
                                    {formatDate(vehicle.last_synced_at, true)}
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2 sm:col-span-2 sm:justify-end">
                                <Button asChild variant="outline">
                                    <Link href={VehicleController.index()}>
                                        <ArrowLeft />
                                        Back
                                    </Link>
                                </Button>
                                {canUpdate && (
                                    <Button asChild variant="outline">
                                        <a href="#operational-information">
                                            <Pencil />
                                            Edit operational information
                                        </a>
                                    </Button>
                                )}
                                {canSync && (
                                    <Form
                                        {...VehicleSyncController.store.form(
                                            vehicle.id,
                                        )}
                                        options={{ preserveScroll: true }}
                                    >
                                        {({ processing }) => (
                                            <Button disabled={processing}>
                                                <RefreshCw
                                                    className={
                                                        processing
                                                            ? 'animate-spin'
                                                            : ''
                                                    }
                                                />
                                                {processing
                                                    ? 'Syncing…'
                                                    : 'Sync with AMS'}
                                            </Button>
                                        )}
                                    </Form>
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                <CurrentAssignment
                    assignment={currentAssignment}
                    vehicleId={vehicle.id}
                    canManage={canUpdate}
                />

                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <Gauge className="size-5" />
                                    Recent odometer history
                                </CardTitle>
                                <CardDescription>
                                    Trip readings and authorized manual
                                    corrections are retained.
                                </CardDescription>
                            </div>
                            <Button asChild variant="outline" size="sm">
                                <Link
                                    href={odometerIndex({
                                        query: { vehicle_id: vehicle.id },
                                    })}
                                >
                                    View full history
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {recentOdometerEntries.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No odometer history has been recorded yet.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="py-2 font-medium">
                                                Recorded
                                            </th>
                                            <th className="py-2 font-medium">
                                                Reading
                                            </th>
                                            <th className="py-2 font-medium">
                                                Source
                                            </th>
                                            <th className="py-2 font-medium">
                                                Recorded by
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recentOdometerEntries.map((entry) => (
                                            <tr
                                                key={entry.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="py-3 pr-4 whitespace-nowrap">
                                                    {formatDate(
                                                        entry.recorded_at,
                                                        true,
                                                    )}
                                                </td>
                                                <td className="py-3 pr-4 font-medium whitespace-nowrap">
                                                    {formatMileage(
                                                        entry.reading,
                                                    )}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    {entry.trip ? (
                                                        <Link
                                                            className="underline underline-offset-4"
                                                            href={showTrip(
                                                                entry.trip.id,
                                                            )}
                                                        >
                                                            {
                                                                entry.trip
                                                                    .reference_number
                                                            }
                                                        </Link>
                                                    ) : (
                                                        humanizeStatus(
                                                            entry.entry_type,
                                                        )
                                                    )}
                                                </td>
                                                <td className="py-3">
                                                    {entry.recorder.name}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {flash.vehicleSyncWarning && (
                    <Alert variant="destructive">
                        <TriangleAlert />
                        <AlertTitle>AMS sync failed</AlertTitle>
                        <AlertDescription>
                            {flash.vehicleSyncWarning.message} Last successful
                            sync:{' '}
                            {formatDate(
                                flash.vehicleSyncWarning.lastSuccessfulSync,
                                true,
                            )}
                            . The vehicle profile and all VEMS operational data
                            were preserved.
                        </AlertDescription>
                    </Alert>
                )}

                {(!vehicle.is_present_in_ams || vehicle.last_sync_error) && (
                    <Alert variant="destructive">
                        <TriangleAlert />
                        <AlertTitle>
                            AMS synchronization needs review
                        </AlertTitle>
                        <AlertDescription>
                            {vehicle.last_sync_error ??
                                'This vehicle was not returned by the latest AMS feed.'}{' '}
                            Last successful sync:{' '}
                            {formatDate(vehicle.last_synced_at, true)}. The
                            cached profile remains available for review.
                        </AlertDescription>
                    </Alert>
                )}

                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,3fr)_minmax(22rem,2fr)]">
                    <Card>
                        <CardHeader>
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <CardTitle className="flex items-center gap-2">
                                    <Database className="size-5" />
                                    AMS asset information
                                </CardTitle>
                                <Badge variant="secondary">Read-only</Badge>
                            </div>
                            <CardDescription>
                                Master data is synchronized from AMS and cannot
                                be edited in VEMS.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <section className="space-y-3">
                                <h2 className="text-sm font-semibold">
                                    Identity and classification
                                </h2>
                                <dl className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    <ReadOnlyField
                                        label="AMS asset ID"
                                        value={vehicle.ams_asset_id}
                                    />
                                    <ReadOnlyField
                                        label="Property tag UUID"
                                        value={vehicle.property_tag_uuid}
                                    />
                                    <ReadOnlyField
                                        label="Property number"
                                        value={vehicle.property_number}
                                    />
                                    <ReadOnlyField
                                        label="Plate number"
                                        value={vehicle.plate_number}
                                    />
                                    <ReadOnlyField
                                        label="Serial number"
                                        value={vehicle.serial_number}
                                    />
                                    <ReadOnlyField
                                        label="Brand"
                                        value={vehicle.brand}
                                    />
                                    <ReadOnlyField
                                        label="Model"
                                        value={vehicle.model}
                                    />
                                    <ReadOnlyField
                                        label="Year"
                                        value={vehicle.vehicle_year}
                                    />
                                    <ReadOnlyField
                                        label="Unit of measure"
                                        value={vehicle.unit_of_measure}
                                    />
                                    <ReadOnlyField
                                        label="Category"
                                        value={valueWithCode(
                                            vehicle.category_name,
                                            vehicle.category_code,
                                        )}
                                    />
                                    <ReadOnlyField
                                        label="Subcategory"
                                        value={valueWithCode(
                                            vehicle.subcategory_name,
                                            vehicle.subcategory_code,
                                        )}
                                    />
                                    <ReadOnlyField
                                        label="AMS created"
                                        value={
                                            vehicle.ams_created_at
                                                ? formatDate(
                                                      vehicle.ams_created_at,
                                                      true,
                                                  )
                                                : null
                                        }
                                    />
                                </dl>
                                <dl>
                                    <ReadOnlyField
                                        label="Description"
                                        value={vehicle.description}
                                    />
                                </dl>
                            </section>

                            <section className="space-y-3">
                                <h2 className="text-sm font-semibold">
                                    Assignment and location
                                </h2>
                                <dl className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    <ReadOnlyField
                                        label="Office / unit"
                                        value={vehicle.office_name}
                                    />
                                    <ReadOnlyField
                                        label="Location"
                                        value={vehicle.location}
                                    />
                                    <ReadOnlyField
                                        label="Nature of occupancy"
                                        value={vehicle.nature_of_occupancy}
                                    />
                                    <ReadOnlyField
                                        label="Custodian"
                                        value={valueWithCode(
                                            vehicle.current_custodian,
                                            vehicle.current_custodian_code,
                                        )}
                                    />
                                    <ReadOnlyField
                                        label="Borrower"
                                        value={valueWithCode(
                                            vehicle.current_borrower,
                                            vehicle.current_borrower_code,
                                        )}
                                    />
                                    <ReadOnlyField
                                        label="Custody status"
                                        value={humanizeStatus(
                                            vehicle.ams_custody_status,
                                        )}
                                    />
                                </dl>
                            </section>

                            <section className="space-y-3">
                                <h2 className="text-sm font-semibold">
                                    Acquisition and AMS status
                                </h2>
                                <dl className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    <ReadOnlyField
                                        label="Fund cluster"
                                        value={vehicle.fund_cluster}
                                    />
                                    <ReadOnlyField
                                        label="Acquisition date"
                                        value={
                                            vehicle.acquisition_date
                                                ? formatDate(
                                                      vehicle.acquisition_date,
                                                  )
                                                : null
                                        }
                                    />
                                    <ReadOnlyField
                                        label="Acquisition cost"
                                        value={formatCurrency(
                                            vehicle.acquisition_cost,
                                        )}
                                    />
                                    <ReadOnlyField
                                        label="AMS asset status"
                                        value={
                                            <StatusBadge
                                                value={vehicle.ams_asset_status}
                                            />
                                        }
                                    />
                                    <ReadOnlyField
                                        label="AMS condition"
                                        value={humanizeStatus(
                                            vehicle.ams_condition_status,
                                        )}
                                    />
                                    <ReadOnlyField
                                        label="AMS last updated"
                                        value={
                                            vehicle.ams_updated_at
                                                ? formatDate(
                                                      vehicle.ams_updated_at,
                                                      true,
                                                  )
                                                : null
                                        }
                                    />
                                </dl>
                            </section>
                        </CardContent>
                    </Card>

                    <div className="grid gap-6">
                        <Card
                            id="operational-information"
                            className="scroll-mt-6"
                        >
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Settings2 className="size-5" />
                                    VEMS operational information
                                </CardTitle>
                                <CardDescription>
                                    These fields are maintained locally. AMS
                                    sync preserves them, except that disposed or
                                    retired assets are automatically marked
                                    retired in VEMS.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {canUpdate ? (
                                    <Form
                                        {...VehicleController.update.form(
                                            vehicle.id,
                                        )}
                                        options={{ preserveScroll: true }}
                                    >
                                        {({ processing, errors }) => (
                                            <div className="grid gap-5">
                                                <OperationalFields
                                                    vehicle={vehicle}
                                                    operationalStatuses={
                                                        operationalStatuses
                                                    }
                                                    fuelTypes={fuelTypes}
                                                    errors={errors}
                                                />
                                                <Button
                                                    className="justify-self-end"
                                                    disabled={processing}
                                                >
                                                    {processing
                                                        ? 'Saving…'
                                                        : 'Save operational information'}
                                                </Button>
                                            </div>
                                        )}
                                    </Form>
                                ) : (
                                    <dl className="grid gap-3 sm:grid-cols-2">
                                        <ReadOnlyField
                                            label="Current mileage"
                                            value={formatMileage(
                                                vehicle.current_mileage,
                                            )}
                                        />
                                        <ReadOnlyField
                                            label="Operational status"
                                            value={humanizeStatus(
                                                vehicle.operational_status,
                                            )}
                                        />
                                        <ReadOnlyField
                                            label="Fuel type"
                                            value={vehicle.fuel_type}
                                        />
                                        <ReadOnlyField
                                            label="Maintenance interval"
                                            value={
                                                vehicle.maintenance_interval_km
                                                    ? formatMileage(
                                                          vehicle.maintenance_interval_km,
                                                      )
                                                    : null
                                            }
                                        />
                                        <div className="sm:col-span-2">
                                            <ReadOnlyField
                                                label="Operational notes"
                                                value={
                                                    vehicle.operational_notes
                                                }
                                            />
                                        </div>
                                    </dl>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Info className="size-5" />
                                    Integration information
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="grid gap-3 sm:grid-cols-2">
                                    <ReadOnlyField
                                        label="Source system"
                                        value="MKWD IMS / AMS"
                                    />
                                    <ReadOnlyField
                                        label="AMS asset ID"
                                        value={vehicle.ams_asset_id}
                                    />
                                    <ReadOnlyField
                                        label="Imported into VEMS"
                                        value={formatDate(
                                            vehicle.imported_at,
                                            true,
                                        )}
                                    />
                                    <ReadOnlyField
                                        label="Last successful sync"
                                        value={formatDate(
                                            vehicle.last_synced_at,
                                            true,
                                        )}
                                    />
                                    <div className="sm:col-span-2">
                                        <ReadOnlyField
                                            label="Synchronization state"
                                            value={
                                                flash.vehicleSyncWarning
                                                    ? 'Failed — cached data retained'
                                                    : vehicle.last_sync_error
                                                      ? 'Needs review'
                                                      : vehicle.is_present_in_ams
                                                        ? vehicle.last_synced_at
                                                            ? 'Synchronized'
                                                            : 'Never synced'
                                                        : 'Missing from AMS'
                                            }
                                        />
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

VehicleShow.layout = {
    breadcrumbs: [
        { title: 'Vehicles', href: VehicleController.index() },
        { title: 'Vehicle profile', href: '#' },
    ],
};
