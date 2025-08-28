<script lang="ts" setup>
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { FileText, Heart, MessageSquare, User } from 'lucide-vue-next';

interface ActivityItem {
    id: string;
    type: 'post' | 'comment' | 'like';
    user: {
        id: number;
        name: string;
        username: string;
    };
    timestamp: string;
    human_time: string;
    data: {
        id: number;
        title?: string;
        slug?: string;
        excerpt?: string;
        body?: string;
        post?: {
            id: number;
            title: string;
            slug: string;
        };
    };
}

defineProps<{ activityFeed: ActivityItem[] }>();
defineOptions({ layout: SiteLayout });
</script>

<template>
    <Head title="Feed" />

    <div class="container mx-auto px-6 py-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold">Activity Feed</h1>
            <p class="text-muted-foreground">See what people you follow have been up to</p>
        </div>

        <div v-if="activityFeed.length === 0" class="py-12 text-center">
            <User class="mx-auto h-12 w-12 text-muted-foreground" />
            <h3 class="mt-4 text-lg font-medium">No activity yet</h3>
            <p class="mt-2 text-muted-foreground">Follow some users to see their activity here</p>
        </div>

        <div v-else class="space-y-6">
            <div v-for="activity in activityFeed" :key="activity.id" class="rounded-lg border bg-card p-6 transition-colors hover:bg-muted/50">
                <div class="flex items-start space-x-4">
                    <!-- Activity Icon -->
                    <div class="flex-shrink-0">
                        <div
                            :class="{
                                'bg-blue-100 text-blue-600': activity.type === 'post',
                                'bg-green-100 text-green-600': activity.type === 'comment',
                                'bg-red-100 text-red-600': activity.type === 'like',
                            }"
                            class="flex h-10 w-10 items-center justify-center rounded-full"
                        >
                            <FileText v-if="activity.type === 'post'" class="h-5 w-5" />
                            <MessageSquare v-else-if="activity.type === 'comment'" class="h-5 w-5" />
                            <Heart v-else class="h-5 w-5" />
                        </div>
                    </div>

                    <!-- Activity Content -->
                    <div class="min-w-0 flex-1">
                        <div class="mb-2 flex items-center space-x-2">
                            <Link :href="route('users.posts', { user: activity.user.username })" class="font-medium text-primary hover:underline">
                                {{ activity.user.name }}
                            </Link>
                            <span class="text-muted-foreground">
                                <span v-if="activity.type === 'post'">published a post</span>
                                <span v-else-if="activity.type === 'comment'">commented on</span>
                                <span v-else>liked</span>
                            </span>
                            <span class="text-sm text-muted-foreground">
                                {{ activity.human_time }}
                            </span>
                        </div>

                        <!-- Post Activity -->
                        <div v-if="activity.type === 'post'">
                            <Link :href="route('posts.show', activity.data.slug!)" class="block hover:underline">
                                <h3 class="mb-2 text-lg font-semibold">{{ activity.data.title }}</h3>
                                <p v-if="activity.data.excerpt" class="text-muted-foreground">
                                    {{ activity.data.excerpt }}
                                </p>
                            </Link>
                        </div>

                        <!-- Comment Activity -->
                        <div v-else-if="activity.type === 'comment'">
                            <Link :href="route('posts.show', activity.data.post!.slug)" class="font-medium hover:underline">
                                {{ activity.data.post!.title }}
                            </Link>
                            <div class="mt-2 rounded border-l-4 border-primary bg-muted/50 p-3">
                                <p class="text-sm">{{ activity.data.body }}</p>
                            </div>
                        </div>

                        <!-- Like Activity -->
                        <div v-else>
                            <Link :href="route('posts.show', activity.data.post!.slug)" class="font-medium hover:underline">
                                {{ activity.data.post!.title }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
