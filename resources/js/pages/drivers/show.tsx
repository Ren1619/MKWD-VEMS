import { Form, Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, RefreshCw, ShieldCheck, TriangleAlert } from 'lucide-react';
import {
    index,
    sync,
    update,
} from '@/actions/App/Http/Controllers/DriverController';
import { CurrentAssignment } from '@/components/assignments/current-assignment';
import { DriverFields } from '@/components/drivers/driver-fields';
import { EmployeeProfileCard } from '@/components/drivers/employee-profile';
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
import { formatDate, humanizeStatus } from '@/lib/vehicles';
import type { Assignment } from '@/types/assignment';
import type { Driver, DriverOptions } from '@/types/driver';

type Props = DriverOptions & {
    driver: Driver;
    canManage: boolean;
    currentAssignment: Assignment | null;
};

export default function DriverShow({
    driver,
    canManage,
    currentAssignment,
    ...options
}: Props) {
    const { errors } = usePage().props;
    const outsourced = driver.source_type === 'outsourced';
    const details = [
        ['License number', driver.license_number],
        [
            'License type',
            options.licenseTypes[driver.license_type] ?? driver.license_type,
        ],
        ['License expiry', formatDate(driver.license_expiry)],
        ['Driver status', humanizeStatus(driver.status)],
        [
            'Driving approval',
            driver.driving_authorized ? 'Approved' : 'Not approved',
        ],
        ['Alternate phone', driver.contact_phone],
        ['Alternate email', driver.contact_email],
        ['Alternate contact source', driver.contact_source],
    ];

    return (
        <>
            <Head title={driver.employee_name} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="grid gap-2">
                        <h1 className="text-2xl font-semibold">
                            {driver.employee_name}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Driver profile ·{' '}
                            {outsourced
                                ? 'Outsourced driver'
                                : driver.employee_number ||
                                  driver.hris_employee_id}
                        </p>
                        <div className="flex gap-2">
                            <Badge variant="secondary">
                                {humanizeStatus(driver.status)}
                            </Badge>
                            <Badge
                                variant={
                                    driver.eligible_to_drive
                                        ? 'default'
                                        : 'outline'
                                }
                            >
                                {driver.eligible_to_drive
                                    ? 'Eligible to drive'
                                    : 'Not eligible to drive'}
                            </Badge>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href={index()}>
                                <ArrowLeft />
                                Back
                            </Link>
                        </Button>
                        {canManage && !outsourced && (
                            <Form
                                {...sync.form(driver.id)}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <Button
                                        disabled={processing}
                                        variant="outline"
                                    >
                                        <RefreshCw
                                            className={
                                                processing ? 'animate-spin' : ''
                                            }
                                        />
                                        {processing
                                            ? 'Refreshing…'
                                            : 'Refresh from HRIS'}
                                    </Button>
                                )}
                            </Form>
                        )}
                    </div>
                </div>
                {!outsourced &&
                    (errors.hris_sync || !driver.is_present_in_hris) && (
                        <Alert variant="destructive">
                            <TriangleAlert />
                            <AlertTitle>
                                HRIS refresh needs attention
                            </AlertTitle>
                            <AlertDescription>
                                {errors.hris_sync ||
                                    'Employee was not found in HRIS. This driver record is retained for review.'}{' '}
                                Last successful refresh:{' '}
                                {formatDate(driver.last_synced_at, true)}.
                            </AlertDescription>
                        </Alert>
                    )}
                <div className="grid items-start gap-6 xl:grid-cols-2">
                    <div className="xl:col-span-2">
                        <CurrentAssignment
                            assignment={currentAssignment}
                            driverId={driver.id}
                            canManage={canManage}
                        />
                    </div>
                    <div className="grid gap-6">
                        <EmployeeProfileCard
                            employee={driver}
                            outsourced={outsourced}
                            editable={canManage && outsourced}
                            formId="driver-details-form"
                            errors={errors}
                        />
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <ShieldCheck className="size-5" />
                                    Eligibility to drive
                                </CardTitle>
                                <CardDescription>
                                    Evaluated from the current driver details
                                    and license expiry.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-4">
                                {driver.eligible_to_drive ? (
                                    <p className="text-sm">
                                        This driver meets the current VEMS
                                        eligibility checks.
                                    </p>
                                ) : (
                                    <ul className="list-disc space-y-1 pl-5 text-sm">
                                        {driver.eligibility_reasons.map(
                                            (reason) => (
                                                <li key={reason}>{reason}</li>
                                            ),
                                        )}
                                    </ul>
                                )}
                                <dl className="grid gap-3 border-t pt-4 text-sm">
                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            Preferred phone
                                        </dt>
                                        <dd>
                                            {driver.contact_phone ||
                                                driver.employee_phone ||
                                                'Not recorded'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            Preferred email
                                        </dt>
                                        <dd className="break-words">
                                            {driver.contact_email ||
                                                driver.employee_email ||
                                                'Not recorded'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            {outsourced
                                                ? 'Profile source'
                                                : 'Last HRIS refresh'}
                                        </dt>
                                        <dd>
                                            {outsourced
                                                ? 'Outsourced · Managed in VEMS'
                                                : formatDate(
                                                      driver.last_synced_at,
                                                      true,
                                                  )}
                                        </dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>
                    </div>
                    <Card>
                        <CardHeader>
                            <CardTitle>Driver details</CardTitle>
                            <CardDescription>
                                Maintained in VEMS and preserved when employee
                                information is refreshed.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {canManage ? (
                                <Form
                                    id="driver-details-form"
                                    {...update.form(driver.id)}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ errors, processing }) => (
                                        <fieldset
                                            disabled={processing}
                                            className="grid min-w-0 gap-5"
                                        >
                                            <DriverFields
                                                driver={driver}
                                                outsourced={outsourced}
                                                {...options}
                                                errors={errors}
                                            />
                                            <Button
                                                disabled={processing}
                                                className="justify-self-end"
                                            >
                                                {processing
                                                    ? 'Saving…'
                                                    : 'Save driver details'}
                                            </Button>
                                        </fieldset>
                                    )}
                                </Form>
                            ) : (
                                <dl className="grid gap-4 sm:grid-cols-2">
                                    {details.map(([label, value]) => (
                                        <div key={label} className="min-w-0">
                                            <dt className="text-xs text-muted-foreground">
                                                {label}
                                            </dt>
                                            <dd className="text-sm break-words">
                                                {value || 'Not recorded'}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
DriverShow.layout = {
    breadcrumbs: [
        { title: 'Drivers', href: index() },
        { title: 'Driver profile', href: '#' },
    ],
};
