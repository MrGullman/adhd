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
};
