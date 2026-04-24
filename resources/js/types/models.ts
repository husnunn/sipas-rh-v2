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

export interface User extends AuthUser {
    username: string;
    roles: ('admin' | 'teacher' | 'student')[];
    is_active: boolean;
    must_change_password: boolean;
    last_login_at?: string | null;
}
