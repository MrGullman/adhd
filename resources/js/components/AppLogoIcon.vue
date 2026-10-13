<script setup lang="ts">
import iconUrl from '../../images/logo-icon-128.png';

defineOptions({
    inheritAttrs: false,
});
</script>

<!-- Appens symbol: familjen och NPF (resources/images/logo_icon_npf.png). -->
<template>
    <img
        :src="iconUrl"
        alt="NPF-familjen"
        width="128"
        height="128"
        class="object-contain"
        v-bind="$attrs"
    />
</template>
