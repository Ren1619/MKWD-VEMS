import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { show as showDriver } from '@/actions/App/Http/Controllers/DriverController';
import { show as showVehicle } from '@/actions/App/Http/Controllers/VehicleController';
import {
    end,
    index,
} from '@/actions/App/Http/Controllers/VehicleDriverAssignmentController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, humanizeStatus } from '@/lib/vehicles';
import type { Paginated } from '@/types';
import type { Assignment } from '@/types/assignment';
import { AssignmentModal } from './assignment-modal';

type Props = {
    assignments: Paginated<Assignment>;
    filters: {
        vehicle_id?: number;
        driver_id?: number;
        status?: string;
        assign?: boolean;
    };
    canManage: boolean;
    today: string;
    vehicles: { id: number; name: string; plate_number: string | null }[];
    drivers: {
        id: number;
        employee_name: string;
        employee_number: string | null;
        license_number: string;
    }[];
};

export default function AssignmentIndex({
    assignments,
    filters,
    canManage,
    today,
    vehicles,
    drivers,
}: Props) {
    const [endingId, setEndingId] = useState<number | null>(null);

    return (
        <>
            <Head title="Vehicle–driver assignments" />
            <div className="grid gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Vehicle–driver assignments
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Current, scheduled, and past assignments. History is
                            retained when an assignment ends.
                        </p>
                    </div>
                    {canManage && (
                        <AssignmentModal
                            vehicles={vehicles}
                            drivers={drivers}
                            defaults={filters}
                            today={today}
                            openByDefault={Boolean(filters.assign)}
                        />
                    )}
                </div>
                <Form
                    {...index.form()}
                    className="flex flex-wrap items-end gap-3 rounded-xl border p-4"
                >
                    {filters.vehicle_id && (
                        <input
                            type="hidden"
                            name="vehicle_id"
                            value={filters.vehicle_id}
                        />
                    )}
                    {filters.driver_id && (
                        <input
                            type="hidden"
                            name="driver_id"
                            value={filters.driver_id}
                        />
                    )}
                    <div className="grid gap-2">
                        <Label htmlFor="status">Assignment status</Label>
                        <select
                            id="status"
                            name="status"
                            defaultValue={filters.status ?? ''}
                            className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">All dates</option>
                            <option value="current">Current</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="ended">Ended</option>
                        </select>
                    </div>
                    <Button variant="outline">Filter</Button>
                    <Button asChild variant="ghost">
                        <Link href={index()}>Clear filters</Link>
                    </Button>
                    {filters.vehicle_id && (
                        <p className="text-sm text-muted-foreground">
                            Vehicle #{filters.vehicle_id}
                        </p>
                    )}
                    {filters.driver_id && (
                        <p className="text-sm text-muted-foreground">
                            Driver #{filters.driver_id}
                        </p>
                    )}
                </Form>
                <section className="overflow-hidden rounded-xl border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    {[
                                        'Vehicle',
                                        'Driver',
                                        'Type',
                                        'Dates (inclusive)',
                                        'Status',
                                        'Actions',
                                    ].map((label) => (
                                        <th
                                            key={label}
                                            className="px-4 py-3 font-medium"
                                        >
                                            {label}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {!assignments.data.length && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="p-8 text-center text-muted-foreground"
                                        >
                                            No assignments found.
                                        </td>
                                    </tr>
                                )}
                                {assignments.data.map((assignment) => (
                                    <tr
                                        key={assignment.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3">
                                            <Link
                                                className="font-medium underline underline-offset-4"
                                                href={showVehicle(
                                                    assignment.vehicle_id,
                                                )}
                                            >
                                                {assignment.vehicle.name}
                                            </Link>
                                            <p className="text-xs text-muted-foreground">
                                                {assignment.vehicle
                                                    .plate_number ||
                                                    'No plate recorded'}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3">
                                            <Link
                                                className="underline underline-offset-4"
                                                href={showDriver(
                                                    assignment.driver_id,
                                                )}
                                            >
                                                {
                                                    assignment.driver
                                                        .employee_name
                                                }
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3">
                                            {humanizeStatus(
                                                assignment.assignment_type,
                                            )}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {formatDate(assignment.start_date)}
                                            <br />
                                            {assignment.end_date
                                                ? formatDate(
                                                      assignment.end_date,
                                                  )
                                                : 'Open-ended'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant={
                                                    assignment.status ===
                                                    'current'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {humanizeStatus(
                                                    assignment.status,
                                                )}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            {canManage &&
                                                assignment.can_end &&
                                                (endingId === assignment.id ? (
                                                    <Form
                                                        {...end.form(
                                                            assignment.id,
                                                        )}
                                                        options={{
                                                            preserveScroll: true,
                                                        }}
                                                        onSuccess={() =>
                                                            setEndingId(null)
                                                        }
                                                    >
                                                        {({
                                                            processing,
                                                            errors,
                                                        }) => (
                                                            <fieldset
                                                                disabled={
                                                                    processing
                                                                }
                                                                className="grid min-w-56 gap-2"
                                                            >
                                                                <Label
                                                                    htmlFor={`end-${assignment.id}`}
                                                                >
                                                                    Last
                                                                    assigned day
                                                                </Label>
                                                                <Input
                                                                    id={`end-${assignment.id}`}
                                                                    name="end_date"
                                                                    type="date"
                                                                    required
                                                                    min={
                                                                        assignment.start_date
                                                                    }
                                                                    max={today}
                                                                    defaultValue={
                                                                        today
                                                                    }
                                                                />
                                                                <p className="text-xs text-muted-foreground">
                                                                    Choosing
                                                                    today keeps
                                                                    this
                                                                    assignment
                                                                    current
                                                                    through
                                                                    today.
                                                                </p>
                                                                <InputError
                                                                    message={
                                                                        errors.end_date
                                                                    }
                                                                />
                                                                <div className="flex gap-2">
                                                                    <Button
                                                                        size="sm"
                                                                        disabled={
                                                                            processing
                                                                        }
                                                                    >
                                                                        Confirm
                                                                        end
                                                                    </Button>
                                                                    <Button
                                                                        type="button"
                                                                        size="sm"
                                                                        variant="ghost"
                                                                        onClick={() =>
                                                                            setEndingId(
                                                                                null,
                                                                            )
                                                                        }
                                                                    >
                                                                        Cancel
                                                                    </Button>
                                                                </div>
                                                            </fieldset>
                                                        )}
                                                    </Form>
                                                ) : (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            setEndingId(
                                                                assignment.id,
                                                            )
                                                        }
                                                    >
                                                        End assignment
                                                    </Button>
                                                ))}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <div className="flex flex-wrap items-center justify-between gap-3 border-t p-4 text-sm">
                        <p>
                            {assignments.total} assignments · Page{' '}
                            {assignments.current_page} of{' '}
                            {assignments.last_page}
                        </p>
                        <div className="flex gap-2">
                            {assignments.current_page > 1 && (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={index({
                                            query: {
                                                ...filters,
                                                page:
                                                    assignments.current_page -
                                                    1,
                                            },
                                        })}
                                    >
                                        Previous
                                    </Link>
                                </Button>
                            )}
                            {assignments.current_page <
                                assignments.last_page && (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={index({
                                            query: {
                                                ...filters,
                                                page:
                                                    assignments.current_page +
                                                    1,
                                            },
                                        })}
                                    >
                                        Next
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}
AssignmentIndex.layout = {
    breadcrumbs: [{ title: 'Assignments', href: index() }],
};
