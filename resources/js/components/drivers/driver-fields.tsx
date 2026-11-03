import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { humanizeStatus } from '@/lib/vehicles';
import type { Driver, DriverOptions } from '@/types/driver';

const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs focus-visible:outline-2 focus-visible:outline-ring';

export function DriverFields({
    driver,
    licenseTypes,
    statuses,
    errors,
    outsourced = false,
}: DriverOptions & {
    driver?: Driver;
    outsourced?: boolean;
    errors: Record<string, string | undefined>;
}) {
    return (
        <div className="grid gap-5">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="license_number">License number</Label>
                    <Input
                        id="license_number"
                        name="license_number"
                        required
                        maxLength={100}
                        defaultValue={driver?.license_number ?? ''}
                    />
                    <InputError message={errors.license_number} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="license_type">License type</Label>
                    <select
                        id="license_type"
                        name="license_type"
                        required
                        defaultValue={driver?.license_type ?? ''}
                        className={selectClass}
                    >
                        <option value="" disabled>
                            Select license type
                        </option>
                        {Object.entries(licenseTypes).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.license_type} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="license_expiry">License expiry</Label>
                    <Input
                        id="license_expiry"
                        name="license_expiry"
                        type="date"
                        required
                        defaultValue={driver?.license_expiry ?? ''}
                    />
                    <InputError message={errors.license_expiry} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="status">Driver status</Label>
                    <select
                        id="status"
                        name="status"
                        defaultValue={driver?.status ?? 'inactive'}
                        className={selectClass}
                    >
                        {statuses.map((status) => (
                            <option key={status} value={status}>
                                {humanizeStatus(status)}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.status} />
                </div>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="driving_authorized">Driving approval</Label>
                <select
                    id="driving_authorized"
                    name="driving_authorized"
                    defaultValue={driver?.driving_authorized ? '1' : '0'}
                    className={selectClass}
                >
                    <option value="0">Not approved</option>
                    <option value="1">Approved to drive</option>
                </select>
                <p className="text-xs text-muted-foreground">
                    Eligibility also requires active driver status and an
                    unexpired license
                    {outsourced
                        ? '.'
                        : ', and an employee record present in HRIS.'}{' '}
                    Approval alone does not make an inactive or expired-license
                    driver eligible.
                </p>
                <InputError message={errors.driving_authorized} />
            </div>
            <div className="grid gap-4 border-t pt-5 sm:grid-cols-2">
                <div className="sm:col-span-2">
                    <h3 className="text-sm font-medium">
                        Alternate contact information
                    </h3>
                    <p className="text-xs text-muted-foreground">
                        Optional contacts from another source. Leave blank to
                        use the {outsourced ? 'driver profile' : 'HRIS'}{' '}
                        contacts.
                    </p>
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="contact_phone">Alternate phone</Label>
                    <Input
                        id="contact_phone"
                        name="contact_phone"
                        type="tel"
                        maxLength={50}
                        defaultValue={driver?.contact_phone ?? ''}
                    />
                    <InputError message={errors.contact_phone} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="contact_email">Alternate email</Label>
                    <Input
                        id="contact_email"
                        name="contact_email"
                        type="email"
                        maxLength={255}
                        defaultValue={driver?.contact_email ?? ''}
                    />
                    <InputError message={errors.contact_email} />
                </div>
                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor="contact_source">Contact source</Label>
                    <Input
                        id="contact_source"
                        name="contact_source"
                        maxLength={255}
                        placeholder="e.g. Provided directly by the employee"
                        defaultValue={driver?.contact_source ?? ''}
                    />
                    <InputError message={errors.contact_source} />
                </div>
            </div>
        </div>
    );
}
