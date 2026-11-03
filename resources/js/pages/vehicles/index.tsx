import { Form, Head, Link, router } from '@inertiajs/react';
import {
    CarFront,
    CircleCheckBig,
    Eye,
    Import,
    RefreshCw,
    Search,
    TriangleAlert,
    Wrench,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import VehicleController from '@/actions/App/Http/Controllers/VehicleController';
import VehicleSyncController from '@/actions/App/Http/Controllers/VehicleSyncController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { StatusBadge } from '@/components/vehicles/status-badge';
import { formatDate, formatMileage, humanizeStatus } from '@/lib/vehicles';
import type { Paginated, Vehicle } from '@/types';

type Props = {
    vehicles: Paginated<Vehicle>;
    filters: {
        search?: string;
        operational_status?: string;
        ams_asset_status?: string;
        office_name?: string;
    };
    filterOptions: {
        operationalStatuses: string[];
        amsAssetStatuses: string[];
        offices: string[];
    };
    summary: {
        total: number;
        available: number;
        maintenance: number;
        needsReview: number;
    };
    canManage: boolean;
};

function SummaryCard({
    label,
    value,
    detail,
    icon: Icon,
}: {
    label: string;
    value: number;
    detail: string;
    icon: typeof CarFront;
}) {
    return (
        <div className="rounded-xl border bg-card p-4 shadow-xs">
            <div className="flex items-start justify-between gap-4">
                <div className="grid gap-1">
                    <p className="text-sm text-muted-foreground">{label}</p>
                    <p className="text-2xl font-semibold tracking-tight">
                        {value.toLocaleString()}
                    </p>
                </div>
                <div className="rounded-lg bg-primary/10 p-2 text-primary">
                    <Icon className="size-5" />
                </div>
            </div>
            <p className="mt-3 text-xs text-muted-foreground">{detail}</p>
        </div>
    );
}

function paginationLabel(label: string): string {
    return label.replace('&laquo;', '‹').replace('&raquo;', '›');
}

export default function VehicleIndex({
    vehicles,
    filters,
    filterOptions,
    summary,
    canManage,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [operationalStatus, setOperationalStatus] = useState(
        filters.operational_status ?? '',
    );
    const [amsAssetStatus, setAmsAssetStatus] = useState(
        filters.ams_asset_status ?? '',
    );
    const [officeName, setOfficeName] = useState(filters.office_name ?? '');

    function applyFilters(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        router.get(
            VehicleController.index.url(),
            {
                search: search || undefined,
                operational_status: operationalStatus || undefined,
                ams_asset_status: amsAssetStatus || undefined,
                office_name: officeName || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Vehicle Management" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-hidden p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                    <div className="grid gap-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Vehicle management
                        </h1>
                        <p className="max-w-2xl text-sm text-muted-foreground">
                            Operational profiles registered in VEMS. Asset
                            master data remains controlled by AMS.
                        </p>
                    </div>

                    {canManage && (
                        <div className="flex flex-wrap gap-2">
                            <Form
                                {...VehicleSyncController.storeAll.form()}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <Button
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        <RefreshCw
                                            className={
                                                processing ? 'animate-spin' : ''
                                            }
                                        />
                                        Sync all
                                    </Button>
                                )}
                            </Form>
                            <Button asChild>
                                <Link href={VehicleController.create()}>
                                    <Import />
                                    Import from AMS
                                </Link>
                            </Button>
                        </div>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard
                        label="Imported vehicles"
                        value={summary.total}
                        detail="Operational profiles in VEMS"
                        icon={CarFront}
                    />
                    <SummaryCard
                        label="Available"
                        value={summary.available}
                        detail="Ready for operational use"
                        icon={CircleCheckBig}
                    />
                    <SummaryCard
                        label="Under maintenance"
                        value={summary.maintenance}
                        detail="Temporarily unavailable"
                        icon={Wrench}
                    />
                    <SummaryCard
                        label="Needs review"
                        value={summary.needsReview}
                        detail="Missing or failed AMS synchronization"
                        icon={TriangleAlert}
                    />
                </div>

                <section className="overflow-hidden rounded-xl border bg-card shadow-xs">
                    <form
                        onSubmit={applyFilters}
                        className="grid gap-3 border-b p-4 lg:grid-cols-[minmax(15rem,1fr)_13rem_13rem_13rem_auto]"
                    >
                        <div className="relative">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Search vehicle, plate, property, or serial no."
                                aria-label="Search imported vehicles"
                                className="pl-9"
                            />
                        </div>
                        <select
                            value={operationalStatus}
                            onChange={(event) =>
                                setOperationalStatus(event.target.value)
                            }
                            aria-label="Filter operational status"
                            className="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                        >
                            <option value="">All operational statuses</option>
                            {filterOptions.operationalStatuses.map((status) => (
                                <option key={status} value={status}>
                                    {humanizeStatus(status)}
                                </option>
                            ))}
                        </select>
                        <select
                            value={amsAssetStatus}
                            onChange={(event) =>
                                setAmsAssetStatus(event.target.value)
                            }
                            aria-label="Filter AMS status"
                            className="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                        >
                            <option value="">All AMS statuses</option>
                            {filterOptions.amsAssetStatuses.map((status) => (
                                <option key={status} value={status}>
                                    {humanizeStatus(status)}
                                </option>
                            ))}
                        </select>
                        <select
                            value={officeName}
                            onChange={(event) =>
                                setOfficeName(event.target.value)
                            }
                            aria-label="Filter office"
                            className="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                        >
                            <option value="">All offices</option>
                            {filterOptions.offices.map((office) => (
                                <option key={office} value={office}>
                                    {office}
                                </option>
                            ))}
                        </select>
                        <Button variant="secondary" type="submit">
                            Apply filters
                        </Button>
                    </form>

                    {vehicles.data.length === 0 ? (
                        <div className="grid min-h-72 place-items-center p-6 text-center">
                            <div className="grid max-w-sm justify-items-center gap-3">
                                <div className="rounded-full bg-muted p-3 text-muted-foreground">
                                    <CarFront className="size-6" />
                                </div>
                                <h2 className="font-medium">
                                    No imported vehicles found
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    {summary.total === 0
                                        ? 'Import an eligible vehicle from AMS to create its VEMS operational profile.'
                                        : 'Try changing the current filters.'}
                                </p>
                                {canManage && summary.total === 0 && (
                                    <Button asChild>
                                        <Link href={VehicleController.create()}>
                                            Import from AMS
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-6xl text-left text-sm">
                                <thead className="bg-muted/50 text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Vehicle
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Plate number
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Property number
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Mileage
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Operational status
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            AMS status
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Last sync
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {vehicles.data.map((vehicle) => (
                                        <tr
                                            key={vehicle.id}
                                            className="transition-colors hover:bg-muted/30"
                                        >
                                            <td className="max-w-xs px-4 py-4">
                                                <p className="font-medium">
                                                    {vehicle.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {[
                                                        vehicle.brand,
                                                        vehicle.model,
                                                        vehicle.vehicle_year,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(' · ') ||
                                                        'Details not recorded'}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4">
                                                {vehicle.plate_number ??
                                                    'Not recorded'}
                                            </td>
                                            <td className="px-4 py-4">
                                                {vehicle.property_number ??
                                                    'Not recorded'}
                                            </td>
                                            <td className="px-4 py-4 font-medium">
                                                {formatMileage(
                                                    vehicle.current_mileage,
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <StatusBadge
                                                    value={
                                                        vehicle.operational_status
                                                    }
                                                />
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="grid justify-items-start gap-1">
                                                    <StatusBadge
                                                        value={
                                                            vehicle.ams_asset_status
                                                        }
                                                    />
                                                    {!vehicle.is_present_in_ams && (
                                                        <span className="text-xs text-destructive">
                                                            Missing from feed
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-4">
                                                <p>
                                                    {formatDate(
                                                        vehicle.last_synced_at,
                                                        true,
                                                    )}
                                                </p>
                                                {vehicle.last_sync_error && (
                                                    <p className="text-xs text-destructive">
                                                        Sync needs review
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="ghost"
                                                    >
                                                        <Link
                                                            href={VehicleController.show(
                                                                vehicle.id,
                                                            )}
                                                        >
                                                            <Eye />
                                                            View
                                                        </Link>
                                                    </Button>
                                                    {canManage && (
                                                        <Form
                                                            {...VehicleSyncController.store.form(
                                                                vehicle.id,
                                                            )}
                                                            options={{
                                                                preserveScroll: true,
                                                            }}
                                                        >
                                                            {({
                                                                processing,
                                                            }) => (
                                                                <Button
                                                                    size="sm"
                                                                    variant="ghost"
                                                                    disabled={
                                                                        processing
                                                                    }
                                                                    aria-label={`Sync ${vehicle.name}`}
                                                                >
                                                                    <RefreshCw
                                                                        className={
                                                                            processing
                                                                                ? 'animate-spin'
                                                                                : ''
                                                                        }
                                                                    />
                                                                </Button>
                                                            )}
                                                        </Form>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {vehicles.last_page > 1 && (
                        <nav
                            className="flex flex-wrap items-center justify-between gap-3 border-t p-4"
                            aria-label="Vehicle pagination"
                        >
                            <p className="text-sm text-muted-foreground">
                                Showing {vehicles.from ?? 0}–{vehicles.to ?? 0}{' '}
                                of {vehicles.total}
                            </p>
                            <div className="flex flex-wrap gap-1">
                                {vehicles.links.map((link) => (
                                    <Button
                                        key={link.label}
                                        asChild={link.url !== null}
                                        size="sm"
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
                                        disabled={link.url === null}
                                    >
                                        {link.url ? (
                                            <Link
                                                href={link.url}
                                                preserveScroll
                                            >
                                                {paginationLabel(link.label)}
                                            </Link>
                                        ) : (
                                            <span>
                                                {paginationLabel(link.label)}
                                            </span>
                                        )}
                                    </Button>
                                ))}
                            </div>
                        </nav>
                    )}
                </section>
            </div>
        </>
    );
}

VehicleIndex.layout = {
    breadcrumbs: [{ title: 'Vehicles', href: VehicleController.index() }],
};
