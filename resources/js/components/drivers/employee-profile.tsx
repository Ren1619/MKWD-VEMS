import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { EmployeeProfile } from '@/types/driver';

export function EmployeeProfileCard({
    employee,
    outsourced = false,
    editable = false,
    formId,
    errors = {},
}: {
    employee?: EmployeeProfile | null;
    outsourced?: boolean;
    editable?: boolean;
    formId?: string;
    errors?: Record<string, string | undefined>;
}) {
    const fields: {
        name: keyof EmployeeProfile;
        label: string;
        type?: string;
        maxLength: number;
    }[] = [
        {
            name: 'employee_name',
            label: outsourced ? 'Driver name' : 'Employee name',
            maxLength: 255,
        },
        {
            name: 'employee_number',
            label: outsourced
                ? 'Reference number (optional)'
                : 'Employee number',
            maxLength: 255,
        },
        { name: 'department', label: 'Department', maxLength: 255 },
        { name: 'position', label: 'Position', maxLength: 255 },
        {
            name: 'employee_email',
            label: outsourced ? 'Email' : 'HRIS email',
            type: 'email',
            maxLength: 255,
        },
        {
            name: 'employee_phone',
            label: outsourced ? 'Phone' : 'HRIS phone',
            type: 'tel',
            maxLength: 50,
        },
    ];
    const visibleFields = outsourced
        ? fields.filter(
              ({ name }) => name !== 'department' && name !== 'position',
          )
        : fields;

    return (
        <Card>
            <CardHeader>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <CardTitle>
                        {outsourced
                            ? 'Outsourced driver profile'
                            : 'Employee profile'}
                    </CardTitle>
                    <Badge variant="secondary">
                        {outsourced
                            ? 'Managed in VEMS'
                            : 'From HRIS · Read-only'}
                    </Badge>
                </div>
                <CardDescription>
                    {outsourced
                        ? 'Enter the driver’s profile and contact information directly.'
                        : employee
                          ? 'Employee information is maintained in HRIS.'
                          : 'Select an employee to fill in their profile.'}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {editable && outsourced ? (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {visibleFields.map(
                            ({ name, label, type, maxLength }) => (
                                <div key={name} className="grid gap-2">
                                    <Label htmlFor={name}>{label}</Label>
                                    <Input
                                        id={name}
                                        name={name}
                                        form={formId}
                                        type={type ?? 'text'}
                                        maxLength={maxLength}
                                        required={name === 'employee_name'}
                                        defaultValue={employee?.[name] ?? ''}
                                    />
                                    <InputError message={errors[name]} />
                                </div>
                            ),
                        )}
                    </div>
                ) : (
                    <dl className="grid gap-4 sm:grid-cols-2">
                        {visibleFields.map(({ name, label }) => (
                            <div
                                key={name}
                                className="min-w-0 rounded-lg border bg-muted/30 p-3"
                            >
                                <dt className="text-xs text-muted-foreground">
                                    {label}
                                </dt>
                                <dd className="mt-1 text-sm break-words">
                                    {employee?.[name] || 'Not recorded'}
                                </dd>
                            </div>
                        ))}
                    </dl>
                )}
            </CardContent>
        </Card>
    );
}
