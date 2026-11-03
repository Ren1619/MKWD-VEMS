export type Assignment = {
    id: number;
    vehicle_id: number;
    driver_id: number;
    start_date: string;
    end_date: string | null;
    assignment_type: 'primary' | 'temporary';
    status: 'current' | 'scheduled' | 'ended';
    can_end: boolean;
    vehicle: { id: number; name: string; plate_number: string | null };
    driver: { id: number; employee_name: string };
};
