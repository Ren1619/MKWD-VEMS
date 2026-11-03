export type TripStatus =
    'pending' | 'approved' | 'rejected' | 'dispatched' | 'completed';

export type Trip = {
    id: number;
    reference_number: string;
    requested_by: number;
    vehicle_id: number | null;
    driver_id: number | null;
    status: TripStatus;
    destination: string;
    purpose: string;
    passengers: string | null;
    planned_departure: string;
    planned_return: string;
    decided_at: string | null;
    decision_notes: string | null;
    actual_departure: string | null;
    start_odometer: number | null;
    actual_return: string | null;
    end_odometer: number | null;
    requester: { id: number; name: string; email?: string };
    vehicle: {
        id: number;
        name: string;
        plate_number: string | null;
        current_mileage?: number;
        operational_status?: string;
    } | null;
    driver: {
        id: number;
        employee_name: string;
        employee_number?: string | null;
        license_number?: string;
        license_expiry?: string;
    } | null;
    decision_maker?: { id: number; name: string } | null;
    dispatcher?: { id: number; name: string } | null;
    return_recorder?: { id: number; name: string } | null;
};

export type TripVehicleOption = {
    id: number;
    name: string;
    plate_number: string | null;
    current_mileage: number;
    operational_status: string;
};

export type TripDriverOption = {
    id: number;
    employee_name: string;
    employee_number: string | null;
    license_number: string;
    license_expiry: string;
    eligible_to_drive: boolean;
};
