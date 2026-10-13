<script setup lang="ts">
import logoUrl from '../../images/logo-wide.png';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<!-- Hela logotypen. När sidomenyn är ihopfälld visas bara symbolen. -->
<template>
    <img
        :src="logoUrl"
        alt="NPF-familjen"
        width="600"
        height="372"
        class="h-auto w-48 max-w-full object-contain group-data-[collapsible=icon]:hidden"
    />
    <AppLogoIcon
        class="hidden size-8 shrink-0 group-data-[collapsible=icon]:block"
    />
</template>
