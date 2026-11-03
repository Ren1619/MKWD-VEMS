export type EmployeeProfile = {
    employee_name: string;
    employee_number: string | null;
    employee_email: string | null;
    employee_phone: string | null;
    department: string | null;
    position: string | null;
};

export type HrisEmployee = EmployeeProfile & {
    id: string;
    is_registered: boolean;
};

export type Driver = EmployeeProfile & {
    id: number;
    source_type: 'hris' | 'outsourced';
    hris_employee_id: string | null;
    license_number: string;
    license_type: string;
    license_expiry: string;
    status: string;
    driving_authorized: boolean;
    eligible_to_drive: boolean;
    eligibility_reasons: string[];
    contact_email: string | null;
    contact_phone: string | null;
    contact_source: string | null;
    is_present_in_hris: boolean;
    last_synced_at: string | null;
};

export type DriverOptions = {
    licenseTypes: Record<string, string>;
    statuses: string[];
};
