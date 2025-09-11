<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Activity, BarChart3, Clock, Eye, Heart, MessageSquare, PenTool, TrendingUp, User } from 'lucide-vue-next';

interface UserStats {
    followers_count: number;
    followers_count_formatted: string;
    following_count: number;
    following_count_formatted: string;
    total_posts: number;
    total_posts_formatted: string;
    total_views: number;
    total_views_formatted: string;
    total_likes_received: number;
    total_likes_received_formatted: string;
}

interface TopPost {
    id: number;
    title: string;
    slug: string;
    views_count: number;
    views_count_formatted: string;
    likes_count: number;
    comments_count: number;
    engagement_score: number;
    created_at: string;
}

interface RecentPost {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    type: {
        value: string;
    };
    user: {
        id: number;
        name: string;
    };
    category: {
        id: number;
        name: string;
        slug: string;
        color: string;
    };
    created_at: string;
    created_at_human: string;
    views_count: number;
    views_count_formatted: string;
    comments_count: number;
    likes_count: number;
    type_icon: string;
    type_label: string;
}

interface ActivityTimelineItem {
    type: string;
    action: string;
    title: string;
    slug: string;
    created_at: string;
    created_at_human: string;
}

interface CategoryStat {
    name: string;
    slug: string;
    color: string;
    posts_count: number;
    total_views: number;
    total_views_formatted: string;
}

interface ProfileUser {
    id: number;
    name: string;
    username: string;
}

interface PageProps {
    profileUser: ProfileUser;
    userStats: UserStats;
    recentPosts: RecentPost[];
    topPosts: TopPost[];
    categoryStats: CategoryStat[];
    recentActivity: ActivityTimelineItem[];
    flash?: {
        success?: string;
        message?: string;
    };
    isFollowing: boolean;
}

const props = defineProps<PageProps>();
defineOptions({ layout: SiteLayout });

const getActivityIcon = (type: string) => {
    switch (type) {
        case 'post':
            return PenTool;
        default:
            return Activity;
    }
};

const getActivityColor = (type: string) => {
    switch (type) {
        case 'post':
            return 'text-blue-500';
        default:
            return 'text-gray-500';
    }
};

// Flash message functionality
// FIXME: Linter complaining about this
// const page = usePage();
</script>

<template>
    <Head :title="`${profileUser.name}'s Profile`" />

    <div class="container mx-auto px-6 py-10">
        <div class="mb-6">
            <div class="mb-2 flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                    <User class="h-6 w-6 text-primary" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold">{{ profileUser.name }}</h1>
                    <p class="text-muted-foreground">User Profile</p>
                </div>
                <!-- TODO: Check if user already follows and change button to unfollow -->
                <div v-if="$page.props.auth.user?.id !== profileUser.id" class="ml-auto">
                    <Button as-child>
                        <Link :href="route('users.follow', { user: profileUser.username })" as="button" class="ml-auto cursor-pointer" method="post"
                            >{{ !props.isFollowing ? 'Follow' : 'Unfollow' }}
                        </Link>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-5">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Posts</CardTitle>
                    <PenTool class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ userStats.total_posts_formatted }}</div>
                    <p class="text-xs text-muted-foreground">total posts</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Views</CardTitle>
                    <Eye class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ userStats.total_views_formatted }}</div>
                    <p class="text-xs text-muted-foreground">total views</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Likes</CardTitle>
                    <Heart class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ userStats.total_likes_received_formatted }}</div>
                    <p class="text-xs text-muted-foreground">received</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Followers</CardTitle>
                    <TrendingUp class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ userStats.followers_count_formatted }}</div>
                    <p class="text-xs text-muted-foreground">followers</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Following</CardTitle>
                    <TrendingUp class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ userStats.following_count_formatted }}</div>
                    <p class="text-xs text-muted-foreground">following</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-3 lg:grid-rows-1 lg:items-stretch">
            <!-- Recent Posts -->
            <div class="lg:col-span-2">
                <Card class="flex h-full flex-col">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <PenTool class="h-5 w-5" />
                            Recent Posts
                        </CardTitle>
                        <CardDescription>{{ profileUser.name }}'s latest content</CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-1 flex-col">
                        <div v-if="recentPosts.length === 0" class="py-8 text-center text-muted-foreground">
                            <PenTool class="mx-auto mb-2 h-8 w-8 opacity-50" />
                            <p>No posts yet</p>
                        </div>

                        <div v-else class="flex flex-1 flex-col">
                            <div class="flex-1 space-y-4">
                                <div v-for="post in recentPosts" :key="post.id" class="rounded-lg border p-4 transition-colors hover:bg-muted/50">
                                    <div class="flex items-start justify-between">
                                        <div class="min-w-0 flex-1">
                                            <Link
                                                :href="`/posts/${post.slug}`"
                                                class="block font-medium text-foreground transition-colors hover:text-primary"
                                            >
                                                {{ post.title }}
                                            </Link>
                                            <p class="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                                {{ post.excerpt }}
                                            </p>
                                            <div class="mt-2 flex items-center gap-4 text-xs text-muted-foreground">
                                                <span class="flex items-center gap-1">
                                                    <Eye class="h-3 w-3" />
                                                    {{ post.views_count_formatted }}
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <Heart class="h-3 w-3" />
                                                    {{ post.likes_count }}
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <MessageSquare class="h-3 w-3" />
                                                    {{ post.comments_count }}
                                                </span>
                                                <span>{{ post.created_at_human }}</span>
                                            </div>
                                        </div>
                                        <Badge v-if="post.category" :style="{ borderColor: post.category?.color }" class="ml-2" variant="outline">
                                            {{ post.category?.name }}
                                        </Badge>
                                        <Badge v-else class="ml-2" variant="outline">Uncategorized</Badge>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto flex justify-center pt-4">
                                <Link
                                    :href="`/u/${profileUser.username}/posts`"
                                    class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm transition-colors hover:bg-muted"
                                >
                                    View All Posts
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Top Posts & Activity -->
            <div class="flex h-full flex-col gap-6">
                <!-- Top Posts -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BarChart3 class="h-5 w-5" />
                            Top Posts
                        </CardTitle>
                        <CardDescription>Most engaged content</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="topPosts.length === 0" class="py-8 text-center text-muted-foreground">
                            <BarChart3 class="mx-auto mb-2 h-8 w-8 opacity-50" />
                            <p>No posts yet</p>
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="(post, index) in topPosts"
                                :key="post.id"
                                class="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                            >
                                <div class="min-w-0 flex-1">
                                    <Link
                                        :href="`/posts/${post.slug}`"
                                        class="block truncate text-sm font-medium transition-colors hover:text-primary"
                                    >
                                        {{ post.title }}
                                    </Link>
                                    <div class="mt-1 flex items-center gap-4 text-xs text-muted-foreground">
                                        <span class="flex items-center gap-1">
                                            <Eye class="h-3 w-3" />
                                            {{ post.views_count_formatted }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <Heart class="h-3 w-3" />
                                            {{ post.likes_count }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <MessageSquare class="h-3 w-3" />
                                            {{ post.comments_count }}
                                        </span>
                                    </div>
                                </div>
                                <Badge class="ml-2" variant="outline"> #{{ index + 1 }} </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Activity -->
                <Card class="flex flex-1 flex-col">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Clock class="h-5 w-5" />
                            Recent Activity
                        </CardTitle>
                        <CardDescription>Last 30 days</CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-1 flex-col">
                        <div v-if="recentActivity.length === 0" class="py-8 text-center text-muted-foreground">
                            <Activity class="mx-auto mb-2 h-8 w-8 opacity-50" />
                            <p>No recent activity</p>
                        </div>

                        <div v-else class="max-h-80 space-y-3 overflow-y-auto">
                            <div
                                v-for="activity in recentActivity"
                                :key="`${activity.type}-${activity.created_at}`"
                                class="flex items-start gap-3 rounded-lg p-2 transition-colors hover:bg-muted/50"
                            >
                                <component
                                    :is="getActivityIcon(activity.type)"
                                    :class="getActivityColor(activity.type)"
                                    class="mt-0.5 h-4 w-4 flex-shrink-0"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm">
                                        {{ activity.action }}
                                        <Link :href="`/posts/${activity.slug}`" class="font-medium text-primary hover:underline">
                                            "{{ activity.title }}"
                                        </Link>
                                    </p>
                                    <p class="mt-1 text-xs text-muted-foreground">
                                        {{ activity.created_at_human }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Category Stats -->
        <div class="mt-6">
            <Card>
                <CardHeader>
                    <CardTitle>Content by Category</CardTitle>
                    <CardDescription>{{ profileUser.name }}'s posts across categories</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="categoryStats.length === 0" class="py-8 text-center text-muted-foreground">
                        <BarChart3 class="mx-auto mb-2 h-8 w-8 opacity-50" />
                        <p>No categories yet</p>
                    </div>

                    <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="category in categoryStats" :key="category.slug" class="flex items-center justify-between rounded-lg border p-4">
                            <div class="flex items-center gap-3">
                                <div :style="{ backgroundColor: category.color }" class="h-3 w-3 flex-shrink-0 rounded-full" />
                                <div>
                                    <p class="font-medium">{{ category.name }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ category.posts_count }} post{{ category.posts_count !== 1 ? 's' : '' }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-medium">{{ category.total_views_formatted }}</p>
                                <p class="text-xs text-muted-foreground">views</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
