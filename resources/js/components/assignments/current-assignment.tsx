import { Link } from '@inertiajs/react';
import { show as showDriver } from '@/actions/App/Http/Controllers/DriverController';
import { show as showVehicle } from '@/actions/App/Http/Controllers/VehicleController';
import { index } from '@/actions/App/Http/Controllers/VehicleDriverAssignmentController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, humanizeStatus } from '@/lib/vehicles';
import type { Assignment } from '@/types/assignment';

export function CurrentAssignment({
    assignment,
    vehicleId,
    driverId,
    canManage,
}: {
    assignment: Assignment | null;
    vehicleId?: number;
    driverId?: number;
    canManage: boolean;
}) {
    const query = vehicleId
        ? { vehicle_id: vehicleId }
        : { driver_id: driverId };

    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    {vehicleId
                        ? 'Current driver'
                        : 'Currently assigned vehicle'}
                </CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4">
                {assignment ? (
                    <div className="grid gap-1 text-sm">
                        <Link
                            className="font-medium underline underline-offset-4"
                            href={
                                vehicleId
                                    ? showDriver(assignment.driver_id)
                                    : showVehicle(assignment.vehicle_id)
                            }
                        >
                            {vehicleId
                                ? assignment.driver.employee_name
                                : [
                                      assignment.vehicle.name,
                                      assignment.vehicle.plate_number,
                                  ]
                                      .filter(Boolean)
                                      .join(' · ')}
                        </Link>
                        <p className="text-muted-foreground">
                            {humanizeStatus(assignment.assignment_type)} ·{' '}
                            {formatDate(assignment.start_date)} –{' '}
                            {assignment.end_date
                                ? formatDate(assignment.end_date)
                                : 'Open-ended'}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            An assignment does not override driver eligibility
                            or vehicle operating status.
                        </p>
                    </div>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        No current assignment.
                    </p>
                )}
                <div className="flex flex-wrap gap-2">
                    <Button asChild variant="outline">
                        <Link href={index({ query })}>Assignment history</Link>
                    </Button>
                    {canManage && (
                        <Button asChild>
                            <Link
                                href={index({ query: { ...query, assign: 1 } })}
                            >
                                Assign driver
                            </Link>
                        </Button>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
