import {
    BookOpen,
    Brain,
    Clock,
    HeartHandshake,
    House,
    Puzzle,
    School,
    Sparkles,
    Sun,
    User,
    Users,
    Zap,
} from '@lucide/vue';
import type { Component } from 'vue';

/** The icons an Om NPF card can have (App\Models\Article::ICONS). */
export const infoIcons: Record<string, Component> = {
    brain: Brain,
    zap: Zap,
    puzzle: Puzzle,
    house: House,
    school: School,
    user: User,
    users: Users,
    'heart-handshake': HeartHandshake,
    sparkles: Sparkles,
    sun: Sun,
    clock: Clock,
    'book-open': BookOpen,
};
