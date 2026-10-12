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

export type RoutineSummary = {
    id: number;
    name: string;
    picture: PictureItem | null;
    doneBy: string | null;
    dayPart: DayPart | null;
    progress: { total: number; completed: number };
    completed: boolean;
    due: boolean;
    currentStep: string | null;
    child: Member;
};

export type RoutineStepItem = {
    id: number;
    label: string;
    picture: PictureItem | null;
    minutes: number | null;
    type: 'step' | 'packlist';
    optional: boolean;
    done: boolean;
    skipped: boolean;
};

export type RoutineFocus = {
    id: number;
    name: string;
    ordered: boolean;
    doneBy: string | null;
    minutesLeft: number | null;
    minutesNeeded: number;
    progress: { total: number; completed: number };
    completed: boolean;
    currentStepId: number | null;
    nextStepId: number | null;
    skippable: number[];
    steps: RoutineStepItem[];
};

export type ActivityType = 'activity' | 'away' | 'pickup' | 'day_off';

export type ChildPacking = {
    date: string;
    items: Array<{
        id: number;
        label: string;
        picture: PictureItem | null;
        packed: boolean;
    }>;
    packed: number;
    total: number;
    confirmed: boolean;
};

export type ChildPackingStatus = {
    child: string;
    packed: number;
    total: number;
    confirmed: boolean;
};

export type ActivityEntry = {
    id: number;
    type: ActivityType;
    typeLabel: string;
    name: string;
    picture: PictureItem | null;
    time: string | null;
    unusual: boolean;
    nightsLeft: number | null;
    participants: Member[];
    /** A child's own packing, or each child's status for the adults. */
    packing: ChildPacking | ChildPackingStatus[] | null;
};

export type DisplayWidgetItem = {
    id: number;
    type: string;
    label: string;
    x: number;
    y: number;
    w: number;
    h: number;
    settings: Record<string, number>;
    data: Record<string, unknown>;
};

export type DisplayHeaderData = {
    date: string;
    week: number;
    dayPart: { value: DayPart; label: string };
    evening: boolean;
};

export type Density = 'large' | 'medium' | 'compact';

export type SchoolLunchData = {
    label: string;
    date: string;
    holiday: string | null;
    courses: Array<{ name: string; option: string | null }>;
};

export type IngredientOption = {
    id: number;
    name: string;
    unit: string | null;
    picture: PictureItem | null;
};
