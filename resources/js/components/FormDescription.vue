<template>
    <div v-if="message" class="mt-1 text-xs text-muted-foreground">
        {{ message }}
    </div>
</template>

<script lang="ts" setup>
interface Props {
    message?: string;
}

defineProps<Props>();
</script>
