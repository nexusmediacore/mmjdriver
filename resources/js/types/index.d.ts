export interface Role {
    id: number;
    name: string;
    display_name: string;
}

export interface Department {
    id: number;
    name: string;
    code: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    phone?: string;
    role?: Role;
    department?: Department;
}

export interface Flash {
    success?: string;
    error?: string;
    info?: string;
    warning?: string;
    whatsapp_link?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash: Flash;
};