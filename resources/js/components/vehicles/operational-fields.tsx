import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { humanizeStatus } from '@/lib/vehicles';
import type { Vehicle } from '@/types';

type OperationalFieldsProps = {
    operationalStatuses: string[];
    fuelTypes: string[];
    errors: Record<string, string | undefined>;
    vehicle?: Vehicle;
};

export function OperationalFields({
    operationalStatuses,
    fuelTypes,
    errors,
    vehicle,
}: OperationalFieldsProps) {
    return (
        <div className="grid gap-5">
            <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="current_mileage">
                        Current mileage (km)
                    </Label>
                    <Input
                        id="current_mileage"
                        name="current_mileage"
                        type="number"
                        min={0}
                        step={1}
                        required
                        defaultValue={vehicle?.current_mileage ?? 0}
                    />
                    <InputError message={errors.current_mileage} />
                    {vehicle && (
                        <p className="text-xs text-muted-foreground">
                            Changing this value creates an authorized correction
                            in the odometer history.
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="operational_status">
                        Operational status
                    </Label>
                    <select
                        id="operational_status"
                        name="operational_status"
                        required
                        defaultValue={
                            vehicle?.operational_status ??
                            operationalStatuses[0] ??
                            ''
                        }
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                    >
                        {operationalStatuses.map((status) => (
                            <option key={status} value={status}>
                                {humanizeStatus(status)}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.operational_status} />
                </div>
            </div>

            <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="fuel_type">Fuel type</Label>
                    <select
                        id="fuel_type"
                        name="fuel_type"
                        defaultValue={vehicle?.fuel_type ?? ''}
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                    >
                        <option value="">Not recorded</option>
                        {fuelTypes.map((fuelType) => (
                            <option key={fuelType} value={fuelType}>
                                {fuelType}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.fuel_type} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="maintenance_interval_km">
                        Maintenance interval (km)
                    </Label>
                    <Input
                        id="maintenance_interval_km"
                        name="maintenance_interval_km"
                        type="number"
                        min={1}
                        step={1}
                        placeholder="e.g. 5000"
                        defaultValue={vehicle?.maintenance_interval_km ?? ''}
                    />
                    <InputError message={errors.maintenance_interval_km} />
                </div>
            </div>

            {vehicle && (
                <div className="grid gap-2">
                    <Label htmlFor="mileage_correction_reason">
                        Mileage correction reason
                    </Label>
                    <textarea
                        id="mileage_correction_reason"
                        name="mileage_correction_reason"
                        rows={3}
                        maxLength={2000}
                        placeholder="Required only when changing the current mileage."
                        className="min-h-20 w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                    />
                    <InputError message={errors.mileage_correction_reason} />
                </div>
            )}

            <div className="grid gap-2">
                <Label htmlFor="operational_notes">Operational notes</Label>
                <textarea
                    id="operational_notes"
                    name="operational_notes"
                    rows={4}
                    maxLength={5000}
                    defaultValue={vehicle?.operational_notes ?? ''}
                    placeholder="Record operational restrictions, assignment notes, or other context."
                    className="min-h-24 w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                />
                <InputError message={errors.operational_notes} />
            </div>
        </div>
    );
}
