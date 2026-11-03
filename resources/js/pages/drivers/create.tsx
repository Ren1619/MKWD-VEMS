import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, UserPlus } from 'lucide-react';
import { useState } from 'react';
import { index, store } from '@/actions/App/Http/Controllers/DriverController';
import { DriverFields } from '@/components/drivers/driver-fields';
import { EmployeeCombobox } from '@/components/drivers/employee-combobox';
import { EmployeeProfileCard } from '@/components/drivers/employee-profile';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { DriverOptions, HrisEmployee } from '@/types/driver';

export default function DriverCreate(options: DriverOptions) {
    const [selection, setSelection] = useState<
        HrisEmployee | 'outsourced' | null
    >(null);
    const outsourced = selection === 'outsourced';
    const employee = outsourced ? null : selection;

    return (
        <>
            <Head title="Register driver" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Register driver
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Choose an HRIS employee or an outsourced driver,
                            then complete their details.
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={index()}>
                            <ArrowLeft />
                            Back to drivers
                        </Link>
                    </Button>
                </div>
                <Form {...store.form()}>
                    {({ errors, processing }) => (
                        <fieldset
                            disabled={processing}
                            className="grid min-w-0 gap-6 xl:grid-cols-2"
                        >
                            <div className="grid content-start gap-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Select employee</CardTitle>
                                        <CardDescription>
                                            Choose Outsourced driver for manual
                                            entry, or search the HRIS employee
                                            list.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="grid gap-3">
                                        <EmployeeCombobox
                                            onSelect={setSelection}
                                        />
                                        <input
                                            type="hidden"
                                            name="source_type"
                                            value={
                                                outsourced
                                                    ? 'outsourced'
                                                    : 'hris'
                                            }
                                        />
                                        {!outsourced && (
                                            <input
                                                type="hidden"
                                                name="hris_employee_id"
                                                value={employee?.id ?? ''}
                                            />
                                        )}
                                        <InputError
                                            message={errors.source_type}
                                        />
                                        <InputError
                                            message={errors.hris_employee_id}
                                        />
                                    </CardContent>
                                </Card>
                                <EmployeeProfileCard
                                    key={outsourced ? 'outsourced' : 'hris'}
                                    employee={employee}
                                    outsourced={outsourced}
                                    editable={outsourced}
                                    errors={errors}
                                />
                            </div>
                            <Card>
                                <CardHeader>
                                    <CardTitle>Driver details</CardTitle>
                                    <CardDescription>
                                        License, driving approval, and alternate
                                        contacts are maintained in VEMS.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="grid gap-5">
                                    <DriverFields
                                        {...options}
                                        outsourced={outsourced}
                                        errors={errors}
                                    />
                                    <Button
                                        disabled={processing || !selection}
                                        className="justify-self-end"
                                    >
                                        <UserPlus />
                                        {processing
                                            ? 'Registering…'
                                            : 'Register driver'}
                                    </Button>
                                </CardContent>
                            </Card>
                        </fieldset>
                    )}
                </Form>
            </div>
        </>
    );
}
DriverCreate.layout = {
    breadcrumbs: [
        { title: 'Drivers', href: index() },
        { title: 'Register driver', href: '#' },
    ],
};
