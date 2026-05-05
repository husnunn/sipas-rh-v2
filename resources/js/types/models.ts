import type { User as AuthUser } from './auth';

export interface SchoolYear {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
}

export interface Subject {
    id: number;
    code: string;
    name: string;
    description?: string | null;
    is_active: boolean;
}

export interface TeacherProfile {
    id: number;
    user_id: number;
    nip?: string | null;
    full_name: string;
    gender?: string | null;
    phone?: string | null;
    address?: string | null;
    user?: AuthUser;
    subjects?: Subject[];
}

export interface ClassRoom {
    id: number;
    school_year_id: number;
    name: string;
    level: number;
    homeroom_teacher_id?: number | null;
    is_active: boolean;
    school_year?: SchoolYear;
    homeroom_teacher?: TeacherProfile;
}

export interface StudentProfile {
    id: number;
    user_id: number;
    nis: string;
    nisn?: string | null;
    full_name: string;
    gender?: string | null;
    birth_date?: string | null;
    birth_place?: string | null;
    phone?: string | null;
    address?: string | null;
    parent_name?: string | null;
    parent_phone?: string | null;
    user?: AuthUser;
    classes?: ClassRoom[];
    attendance_records?: AttendanceRecord[];
}

export interface Schedule {
    id: number;
    school_year_id: number;
    semester: number;
    class_id: number;
    subject_id: number;
    teacher_profile_id: number;
    day_of_week: string | number;
    start_time: string;
    end_time: string;
    room?: string | null;
    notes?: string | null;
    is_active: boolean;
    school_year?: SchoolYear;
    class_room?: ClassRoom;
    subject?: Subject;
    teacher_profile?: TeacherProfile;
}

export interface PasswordResetAudit {
    id: number;
    user_id: number;
    reset_by_admin_id: number;
    reason?: string | null;
    ip_address?: string | null;
    created_at: string;
    reset_by_admin?: { id: number; name: string };
}

export interface AttendanceSiteWifiRule {
    id: number;
    attendance_site_id: number;
    ssid: string;
    bssid?: string | null;
    ip_subnet?: string | null;
    is_active: boolean;
}

export interface AttendanceSite {
    id: number;
    name: string;
    latitude: number;
    longitude: number;
    radius_m: number;
    check_in_open_at?: string | null;
    check_in_on_time_until?: string | null;
    check_in_close_at?: string | null;
    check_out_open_at?: string | null;
    check_out_close_at?: string | null;
    is_active: boolean;
    notes?: string | null;
    wifi_rules?: AttendanceSiteWifiRule[];
}

export interface AcademicCalendarEvent {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    event_type: string;
    is_active: boolean;
    allow_attendance: boolean;
    override_schedule: boolean;
    notes?: string | null;
}

export interface AttendanceDayOverride {
    id: number;
    name: string;
    date: string;
    event_type: string;
    is_active: boolean;
    attendance_site_id?: number | null;
    attendance_site?: { id: number; name: string } | null;
    override_attendance_policy: boolean;
    override_schedule: boolean;
    allow_check_in: boolean;
    allow_check_out: boolean;
    waive_check_out: boolean;
    dismiss_students_early: boolean;
    check_in_open_at?: string | null;
    check_in_on_time_until?: string | null;
    check_in_close_at?: string | null;
    check_out_open_at?: string | null;
    check_out_close_at?: string | null;
    notes?: string | null;
    created_by?: number;
    updated_by?: number | null;
    created_by_user?: { id: number; name: string } | null;
    updated_by_user?: { id: number; name: string } | null;
}

/**
 * Absensi: bentuk `AttendanceRecordResource` (API mobile & portal).
 * Halaman Inertia admin siswa bisa masih mengirim model Eloquent mentah (+ `attendance_at`, `attendance_site`).
 */
export interface AttendanceRecord {
    id: number;
    attendance_type: 'check_in' | 'check_out';
    status: 'approved' | 'rejected';
    /** ISO8601 timezone sekolah (sama seperti API). */
    attendance_time?: string;
    reason_code?: string | null;
    reason_detail?: string | null;
    distance_m?: number | null;
    site?: { id: number; name: string } | null;
    schedule_id?: number | null;
    network?: Record<string, unknown> | null;
    location?: Record<string, unknown> | null;
    created_at?: string | null;
    /** Hanya payload Inertia mentah (bukan API). */
    client_time?: string | null;
    attendance_at?: string | null;
    attendance_site?: { id: number; name: string } | null;
    location_payload?: Record<string, unknown> | null;
}

/** Absensi harian fisik (`daily_attendances`). */
export interface DailyAttendanceRow {
    id: number;
    user_id: number;
    student_profile_id: number;
    attendance_site_id: number;
    date: string;
    check_in_at: string | null;
    check_out_at: string | null;
    status: 'present' | 'late' | null;
    late_minutes: number | null;
    check_in_reason_code?: string | null;
    check_in_reason_detail?: string | null;
    attendance_site?: { id: number; name: string } | null;
}

/** Status manual izin/sakit/dispensasi. */
export interface AttendanceManualStatusRow {
    id: number;
    user_id: number;
    student_profile_id: number;
    attendance_site_id: number | null;
    date: string;
    type: 'excused' | 'sick' | 'dispensation';
    reason: string;
    notes?: string | null;
    status: 'approved' | 'cancelled';
    attendance_site?: { id: number; name: string } | null;
    created_by_user?: { id: number; name: string } | null;
}

/** Satu baris rekap status akhir harian (gabungan kalender + manual + fisik). */
export interface DailyAttendanceSummaryRow {
    date: string;
    status: string;
    label: string;
    source: string;
    check_in_at: string | null;
    check_out_at: string | null;
    late_minutes: number | null;
    message?: string | null;
    site?: { id: number; name: string } | null;
    can_check_in?: boolean;
    can_check_out?: boolean;
    override?: {
        active: boolean;
        id: number;
        name: string;
        event_type: string;
        dismiss_students_early: boolean;
        waive_check_out: boolean;
        allow_check_in: boolean;
        allow_check_out: boolean;
    } | null;
    effective_policy?: {
        check_in_open_at: string;
        check_in_on_time_until: string;
        check_in_close_at: string;
        check_out_open_at: string;
        check_out_close_at: string;
    } | null;
}

/**
 * Baris monitoring admin: gabungan absensi siswa (jadwal mapel + harian + manual).
 */
export interface AdminAttendanceMonitoringRecord {
    id: string;
    row_key: string;
    feed_source: string;
    feed_source_label: string;
    attendance_type: string;
    attendance_type_label: string;
    status: string;
    attendance_time?: string | null;
    reason_detail?: string | null;
    user: {
        id?: number;
        name?: string | null;
        username?: string | null;
        roles: string[];
    };
    civitas: string;
    nis?: string | null;
    student_profile_id?: number | null;
    class: { id: number; name: string } | null;
    subject: { id: number; name: string } | null;
    school_year: { id: number; name: string } | null;
    /** Label tampilan, mis. "Semester 1" atau "—". */
    schedule_semester?: string | null;
    site?: { id: number; name: string } | null;
}

export interface User extends AuthUser {
    username: string;
    roles: ('admin' | 'teacher' | 'student')[];
    is_active: boolean;
    must_change_password: boolean;
    plain_password?: string | null;
    last_login_at?: string | null;
    teacher_profile?: TeacherProfile | null;
    student_profile?: StudentProfile | null;
    password_reset_audits?: PasswordResetAudit[];
}
