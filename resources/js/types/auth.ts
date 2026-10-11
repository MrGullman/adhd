export type AvatarColor = 'teal' | 'orange' | 'green' | 'yellow' | 'purple';

export type User = {
    id: number;
    name: string;
    email: string | null;
    avatar_color: AvatarColor;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type FamilyRole = 'parent' | 'editor' | 'viewer' | 'child';

export type CurrentFamily = {
    id: number;
    name: string;
    role: FamilyRole | null;
    roleLabel: string | null;
    canEdit: boolean;
    canManage: boolean;
};

export type FamilyOption = {
    id: number;
    name: string;
};

export type Option = {
    value: string;
    label: string;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
