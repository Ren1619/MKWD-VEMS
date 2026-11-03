export type OdometerEntryType =
    'trip_departure' | 'trip_return' | 'manual_correction';

export type OdometerEntry = {
    id: number;
    vehicle_id: number;
    trip_id: number | null;
    recorded_by: number;
    entry_type: OdometerEntryType;
    previous_reading: number;
    reading: number;
    notes: string | null;
    recorded_at: string;
    vehicle?: { id: number; name: string; plate_number: string | null };
    trip: { id: number; reference_number: string } | null;
    recorder: { id: number; name: string };
};
