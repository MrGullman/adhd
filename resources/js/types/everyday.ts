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

export type TaskRowData = {
    id: number;
    title: string;
    picture: PictureItem | null;
    time: string | null;
    dayPart: DayPart | null;
    completed: boolean;
    recurring: boolean;
    assignee: Member | null;
};

export type DayGroup = {
    value: DayPart | null;
    label: string;
    start: string | null;
    tasks: TaskRowData[];
};
