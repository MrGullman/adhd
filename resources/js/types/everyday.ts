export type DayPart = 'morning' | 'forenoon' | 'afternoon' | 'evening';

export type DayPartOption = {
    value: DayPart;
    label: string;
    start: string;
};

export type Member = {
    id: number;
    name: string;
    avatarColor: string;
    avatarUrl?: string | null;
};

export type PictureItem = {
    id: number;
    label: string;
    url: string;
};
