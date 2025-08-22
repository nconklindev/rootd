<script lang="ts" setup>
import PostCard from '@/components/PostCard.vue';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{ posts: any; user: any }>();
defineOptions({ layout: SiteLayout });
</script>

<template>
    <Head :title="user?.name + '\'s Posts'" />
    <div class="container mx-auto px-6 py-10">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">{{ user?.name }}'s Posts</h1>
        </div>

        <div v-if="posts?.length" class="space-y-4">
            <PostCard v-for="post in posts" :key="post.id" :post="post" />
        </div>

        <div v-else class="rounded border bg-card p-8 text-center text-muted-foreground">{{ user?.name }} hasn't created any posts yet.</div>
    </div>
</template>
