import { Form, Head, Link } from '@inertiajs/react';
import { Search, UserPlus, Users } from 'lucide-react';
import {
    create,
    index,
    show,
} from '@/actions/App/Http/Controllers/DriverController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { humanizeStatus, formatDate } from '@/lib/vehicles';
import type { Paginated } from '@/types';
import type { Driver } from '@/types/driver';

type Props = {
    drivers: Paginated<Driver>;
    filters: { search?: string; status?: string };
    statuses: string[];
    canManage: boolean;
};

export default function DriverIndex({
    drivers,
    filters,
    statuses,
    canManage,
}: Props) {
    return (
        <>
            <Head title="Drivers" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">Drivers</h1>
                        <p className="text-sm text-muted-foreground">
                            Manage employee driver profiles, licenses, and
                            eligibility to drive.
                        </p>
                    </div>
                    {canManage && (
                        <Button asChild>
                            <Link href={create()}>
                                <UserPlus />
                                Register driver
                            </Link>
                        </Button>
                    )}
                </div>
                <Form
                    {...index.form()}
                    className="flex flex-wrap items-end gap-3 rounded-xl border bg-card p-4"
                >
                    <div className="grid min-w-56 flex-1 gap-2">
                        <Label htmlFor="driver-search">Search</Label>
                        <Input
                            id="driver-search"
                            name="search"
                            defaultValue={filters.search ?? ''}
                            placeholder="Name, employee number, or license"
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="driver-status">Status</Label>
                        <select
                            id="driver-status"
                            name="status"
                            defaultValue={filters.status ?? ''}
                            className="h-9 rounded-md border bg-background px-3 text-sm"
                        >
                            <option value="">All statuses</option>
                            {statuses.map((status) => (
                                <option key={status} value={status}>
                                    {humanizeStatus(status)}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary">
                        <Search />
                        Search
                    </Button>
                    <Button asChild variant="ghost">
                        <Link href={index()}>Clear</Link>
                    </Button>
                </Form>
                <section className="overflow-hidden rounded-xl border bg-card">
                    {drivers.data.length === 0 ? (
                        <div className="grid min-h-64 place-items-center p-8 text-center">
                            <div className="grid justify-items-center gap-2">
                                <Users className="size-9 text-muted-foreground" />
                                <h2 className="font-semibold">
                                    No drivers found
                                </h2>
                                <p className="max-w-md text-sm text-muted-foreground">
                                    Register an HRIS employee as a driver, or
                                    adjust your search filters.
                                </p>
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-muted/40 text-xs text-muted-foreground">
                                    <tr>
                                        {[
                                            'Employee',
                                            'License',
                                            'Expiry',
                                            'Status',
                                            'Eligibility',
                                            '',
                                        ].map((label) => (
                                            <th
                                                key={label}
                                                className="p-4 font-medium"
                                            >
                                                {label}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {drivers.data.map((driver) => (
                                        <tr
                                            key={driver.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-4">
                                                <Link
                                                    href={show(driver.id)}
                                                    className="font-medium hover:underline"
                                                >
                                                    {driver.employee_name}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">
                                                    {driver.source_type ===
                                                    'outsourced'
                                                        ? 'Outsourced driver'
                                                        : driver.employee_number ||
                                                          'Employee number not recorded'}
                                                </p>
                                            </td>
                                            <td className="p-4">
                                                {driver.license_number}
                                            </td>
                                            <td className="p-4 whitespace-nowrap">
                                                {formatDate(
                                                    driver.license_expiry,
                                                )}
                                            </td>
                                            <td className="p-4">
                                                <Badge
                                                    variant={
                                                        driver.status ===
                                                        'active'
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {humanizeStatus(
                                                        driver.status,
                                                    )}
                                                </Badge>
                                            </td>
                                            <td className="p-4">
                                                <Badge
                                                    variant={
                                                        driver.eligible_to_drive
                                                            ? 'outline'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {driver.eligible_to_drive
                                                        ? 'Eligible'
                                                        : 'Not eligible'}
                                                </Badge>
                                            </td>
                                            <td className="p-4">
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <Link
                                                        href={show(driver.id)}
                                                    >
                                                        View profile
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <div className="flex flex-wrap items-center justify-between gap-3 border-t p-4 text-sm">
                        <p className="text-muted-foreground">
                            {drivers.total} driver
                            {drivers.total === 1 ? '' : 's'} · Page{' '}
                            {drivers.current_page} of {drivers.last_page}
                        </p>
                        <div className="flex gap-2">
                            {drivers.current_page > 1 && (
                                <Button asChild variant="outline" size="sm">
                                    <Link
                                        href={index({
                                            query: {
                                                ...filters,
                                                page: drivers.current_page - 1,
                                            },
                                        })}
                                    >
                                        Previous
                                    </Link>
                                </Button>
                            )}
                            {drivers.current_page < drivers.last_page && (
                                <Button asChild variant="outline" size="sm">
                                    <Link
                                        href={index({
                                            query: {
                                                ...filters,
                                                page: drivers.current_page + 1,
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
DriverIndex.layout = { breadcrumbs: [{ title: 'Drivers', href: index() }] };
