<script setup lang="ts">
import type { DisplayWidgetItem } from '@/types';
import ActivitiesWidget from './widgets/ActivitiesWidget.vue';
import CalendarWidget from './widgets/CalendarWidget.vue';
import ChildWidget from './widgets/ChildWidget.vue';
import MealPlanWidget from './widgets/MealPlanWidget.vue';
import PointsWidget from './widgets/PointsWidget.vue';
import SchoolMealsWidget from './widgets/SchoolMealsWidget.vue';
import ShoppingListWidget from './widgets/ShoppingListWidget.vue';
import TasksWidget from './widgets/TasksWidget.vue';
import WeekGridWidget from './widgets/WeekGridWidget.vue';
import WeekWidget from './widgets/WeekWidget.vue';

defineProps<{ widget: DisplayWidgetItem }>();
</script>

<!-- En widget per typ (docs/ARCHITECTURE.md, Widgets i koden). -->
<template>
    <ChildWidget v-if="widget.type === 'child'" :data="widget.data as never" />
    <TasksWidget
        v-else-if="widget.type === 'tasks'"
        :data="widget.data as never"
    />
    <ActivitiesWidget
        v-else-if="widget.type === 'activities'"
        title="Händer idag"
        empty="Inget särskilt idag."
        :data="widget.data as never"
    />
    <ActivitiesWidget
        v-else-if="widget.type === 'tomorrow'"
        title="Imorgon"
        empty="En vanlig dag imorgon."
        :data="widget.data as never"
    />
    <MealPlanWidget
        v-else-if="widget.type === 'meal_plan'"
        :data="widget.data as never"
    />
    <SchoolMealsWidget
        v-else-if="widget.type === 'school_meals'"
        :data="widget.data as never"
    />
    <WeekWidget
        v-else-if="widget.type === 'week'"
        :data="widget.data as never"
    />
    <ShoppingListWidget
        v-else-if="widget.type === 'shopping_list'"
        :data="widget.data as never"
    />
    <PointsWidget
        v-else-if="widget.type === 'points'"
        :data="widget.data as never"
    />
    <WeekGridWidget
        v-else-if="widget.type === 'week_grid'"
        :data="widget.data as never"
    />
    <CalendarWidget
        v-else-if="widget.type === 'calendar'"
        :data="widget.data as never"
    />
</template>
