import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, CarFront, Import, Search, ServerOff } from 'lucide-react';
import { useMemo, useState } from 'react';
import VehicleController from '@/actions/App/Http/Controllers/VehicleController';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { OperationalFields } from '@/components/vehicles/operational-fields';
import { StatusBadge } from '@/components/vehicles/status-badge';
import type { AmsVehicle } from '@/types';

type Props = {
    amsVehicles: AmsVehicle[];
    sourceError: string | null;
    operationalStatuses: string[];
    fuelTypes: string[];
};

export default function ImportVehicle({
    amsVehicles,
    sourceError,
    operationalStatuses,
    fuelTypes,
}: Props) {
    const [search, setSearch] = useState('');
    const [selectedVehicle, setSelectedVehicle] = useState<AmsVehicle | null>(
        null,
    );

    const filteredVehicles = useMemo(() => {
        const query = search.trim().toLocaleLowerCase();

        if (!query) {
            return amsVehicles;
        }

        return amsVehicles.filter((vehicle) =>
            [
                vehicle.name,
                vehicle.brand,
                vehicle.model,
                vehicle.property_number,
                vehicle.plate_number,
                vehicle.serial_number,
            ].some((value) => value?.toLocaleLowerCase().includes(query)),
        );
    }, [amsVehicles, search]);

    return (
        <>
            <Head title="Import Vehicle from AMS" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div className="grid gap-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Import vehicle from AMS
                        </h1>
                        <p className="max-w-2xl text-sm text-muted-foreground">
                            Select an eligible AMS master record and add only
                            its VEMS operational information.
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={VehicleController.index()}>
                            <ArrowLeft />
                            Back to vehicles
                        </Link>
                    </Button>
                </div>

                {sourceError && (
                    <Alert variant="destructive">
                        <ServerOff />
                        <AlertTitle>Could not reach AMS</AlertTitle>
                        <AlertDescription>{sourceError}</AlertDescription>
                    </Alert>
                )}

                <section className="overflow-hidden rounded-xl border bg-card shadow-xs">
                    <div className="border-b p-4">
                        <div className="relative max-w-xl">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Search AMS vehicles"
                                className="pl-9"
                            />
                        </div>
                    </div>

                    {filteredVehicles.length === 0 ? (
                        <div className="grid min-h-72 place-items-center p-6 text-center">
                            <div className="grid max-w-sm justify-items-center gap-2">
                                <div className="rounded-full bg-muted p-3">
                                    <CarFront className="size-6 text-muted-foreground" />
                                </div>
                                <h2 className="font-medium">
                                    No AMS vehicles found
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    AMS returned no matching vehicle assets.
                                </p>
                            </div>
                        </div>
                    ) : (
                        <div className="grid gap-3 p-4 lg:grid-cols-2">
                            {filteredVehicles.map((vehicle) => (
                                <article
                                    key={vehicle.id}
                                    className="grid gap-4 rounded-lg border p-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <h2 className="font-medium">
                                                {vehicle.name}
                                            </h2>
                                            <p className="text-sm text-muted-foreground">
                                                {[
                                                    vehicle.brand,
                                                    vehicle.model,
                                                    vehicle.vehicle_year,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ') ||
                                                    'Vehicle details not recorded'}
                                            </p>
                                        </div>
                                        {vehicle.is_imported ? (
                                            <Badge variant="secondary">
                                                Already imported
                                            </Badge>
                                        ) : vehicle.is_eligible ? (
                                            <Badge
                                                className="border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300"
                                                variant="outline"
                                            >
                                                Available
                                            </Badge>
                                        ) : (
                                            <Badge variant="destructive">
                                                Not eligible
                                            </Badge>
                                        )}
                                    </div>

                                    <dl className="grid grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <dt className="text-xs text-muted-foreground">
                                                Property number
                                            </dt>
                                            <dd>
                                                {vehicle.property_number ??
                                                    'Not recorded'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs text-muted-foreground">
                                                Plate number
                                            </dt>
                                            <dd>
                                                {vehicle.plate_number ??
                                                    'Not recorded'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs text-muted-foreground">
                                                AMS status
                                            </dt>
                                            <dd className="mt-1">
                                                <StatusBadge
                                                    value={
                                                        vehicle.lifecycle_status
                                                    }
                                                />
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs text-muted-foreground">
                                                Location
                                            </dt>
                                            <dd>
                                                {vehicle.office_name ??
                                                    vehicle.location ??
                                                    'Not recorded'}
                                            </dd>
                                        </div>
                                    </dl>

                                    <Button
                                        disabled={!vehicle.is_eligible}
                                        onClick={() =>
                                            setSelectedVehicle(vehicle)
                                        }
                                        className="justify-self-end"
                                    >
                                        <Import />
                                        Import vehicle
                                    </Button>
                                </article>
                            ))}
                        </div>
                    )}
                </section>
            </div>

            <Dialog
                open={selectedVehicle !== null}
                onOpenChange={(open) => !open && setSelectedVehicle(null)}
            >
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            Import {selectedVehicle?.name}
                        </DialogTitle>
                        <DialogDescription>
                            AMS fields will remain read-only. Enter the initial
                            operational information owned by VEMS.
                        </DialogDescription>
                    </DialogHeader>

                    {selectedVehicle && (
                        <Form
                            {...VehicleController.store.form()}
                            onSuccess={() => setSelectedVehicle(null)}
                            className="grid gap-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="ams_asset_id"
                                        value={selectedVehicle.id}
                                    />
                                    <div className="rounded-lg border bg-muted/40 p-3 text-sm">
                                        <p className="font-medium">
                                            {selectedVehicle.name}
                                        </p>
                                        <p className="text-muted-foreground">
                                            {selectedVehicle.property_number ??
                                                'No property number'}{' '}
                                            · AMS ID {selectedVehicle.id}
                                        </p>
                                    </div>
                                    {errors.ams_asset_id && (
                                        <p className="text-sm text-destructive">
                                            {errors.ams_asset_id}
                                        </p>
                                    )}
                                    <OperationalFields
                                        operationalStatuses={
                                            operationalStatuses
                                        }
                                        fuelTypes={fuelTypes}
                                        errors={errors}
                                    />
                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                setSelectedVehicle(null)
                                            }
                                        >
                                            Cancel
                                        </Button>
                                        <Button disabled={processing}>
                                            {processing
                                                ? 'Importing…'
                                                : 'Import vehicle'}
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

ImportVehicle.layout = {
    breadcrumbs: [
        { title: 'Vehicles', href: VehicleController.index() },
        { title: 'Import from AMS', href: VehicleController.create() },
    ],
};
