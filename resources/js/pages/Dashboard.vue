<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Activity, ArrowUpRight, BarChart3, Clock, Eye, Heart, LineChart, MessageSquare, PenTool, TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';

interface UserStats {
    total_posts: number;
    total_comments: number;
    total_likes_given: number;
    total_likes_received: number;
    total_views: number;
    total_followers?: number;
    total_following?: number;
}

interface RecentActivity {
    posts_this_month: number;
    posts_this_week: number;
    comments_this_month: number;
    comments_this_week: number;
}

interface TopPost {
    id: number;
    title: string;
    slug: string;
    views_count: number;
    likes_count: number;
    comments_count: number;
    engagement_score: number;
    created_at: string;
}

interface ActivityTimelineItem {
    type: string;
    action: string;
    title: string;
    slug: string;
    content?: string;
    user_name?: string;
    created_at: string;
}

interface CategoryStat {
    name: string;
    slug: string;
    color: string;
    posts_count: number;
    total_views: number;
}

interface EngagementTrend {
    date: string;
    posts: number;
    comments: number;
    likes_received: number;
    views: number;
}

const props = defineProps<{
    userStats: UserStats;
    recentActivity: RecentActivity;
    topPosts: TopPost[];
    activityTimeline: ActivityTimelineItem[];
    categoryStats: CategoryStat[];
    engagementTrends: EngagementTrend[];
}>();

defineOptions({ layout: SiteLayout });

const formatNumber = (num: number) => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
};

const formatTimeAgo = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60));

    if (diffInHours < 1) return 'Just now';
    if (diffInHours < 24) return `${diffInHours}h ago`;
    if (diffInHours < 48) return '1 day ago';
    return `${Math.floor(diffInHours / 24)} days ago`;
};

const getActivityIcon = (type: string) => {
    switch (type) {
        case 'post':
            return PenTool;
        case 'comment':
            return MessageSquare;
        case 'like_received':
            return Heart;
        default:
            return Activity;
    }
};

const getActivityColor = (type: string) => {
    switch (type) {
        case 'post':
            return 'text-blue-500';
        case 'comment':
            return 'text-green-500';
        case 'like_received':
            return 'text-red-500';
        default:
            return 'text-gray-500';
    }
};

const last7Days = computed(() => {
    if (!props.engagementTrends || props.engagementTrends.length === 0) return [];
    return props.engagementTrends.slice(-7);
});

const maxEngagementValue = computed(() => {
    if (last7Days.value.length === 0) return 1;
    return Math.max(...last7Days.value.map((day) => day.posts + day.comments + day.likes_received)) || 1;
});

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
};

const getBarHeight = (value: number) => {
    return Math.max((value / maxEngagementValue.value) * 100, 2);
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="container mx-auto px-6 py-10">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Dashboard</h1>
            <p class="text-muted-foreground">Overview of your activity and performance</p>
        </div>

        <!-- Stats Overview -->
        <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Posts</CardTitle>
                    <PenTool class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ formatNumber(userStats.total_posts) }}</div>
                    <p class="text-xs text-muted-foreground">+{{ recentActivity.posts_this_week }} this week</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Views</CardTitle>
                    <Eye class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ formatNumber(userStats.total_views) }}</div>
                    <p class="text-xs text-muted-foreground">Across all posts</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Likes Received</CardTitle>
                    <Heart class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ formatNumber(userStats.total_likes_received) }}</div>
                    <p class="text-xs text-muted-foreground">On your content</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Comments</CardTitle>
                    <MessageSquare class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ formatNumber(userStats.total_comments) }}</div>
                    <p class="text-xs text-muted-foreground">+{{ recentActivity.comments_this_week }} this week</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Followers</CardTitle>
                    <TrendingUp class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ formatNumber(userStats.total_followers || 0) }}</div>
                    <p class="text-xs text-muted-foreground">followers</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Following</CardTitle>
                    <TrendingUp class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ formatNumber(userStats.total_following || 0) }}</div>
                    <p class="text-xs text-muted-foreground">following</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Top Performing Posts -->
            <div class="lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BarChart3 class="h-5 w-5" />
                            Top Performing Posts
                        </CardTitle>
                        <CardDescription>Your most engaged content</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="topPosts.length === 0" class="py-8 text-center text-muted-foreground">
                            <PenTool class="mx-auto mb-2 h-8 w-8 opacity-50" />
                            <p>No posts yet</p>
                            <p class="text-sm">
                                <Link class="text-primary hover:underline" href="/posts/create">Create your first post</Link>
                            </p>
                        </div>

                        <div v-else class="space-y-4">
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
                                            {{ formatNumber(post.views_count) }}
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
            </div>

            <!-- Activity Timeline -->
            <div>
                <Card class="h-fit">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Clock class="h-5 w-5" />
                            Recent Activity
                        </CardTitle>
                        <CardDescription>Last 2 weeks</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="activityTimeline.length === 0" class="py-8 text-center text-muted-foreground">
                            <Activity class="mx-auto mb-2 h-8 w-8 opacity-50" />
                            <p>No recent activity</p>
                        </div>

                        <div v-else class="max-h-96 space-y-3 overflow-y-auto">
                            <div
                                v-for="activity in activityTimeline"
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
                                        <span v-if="activity.user_name" class="font-medium">{{ activity.user_name }}</span>
                                        <span v-else>You</span>
                                        {{ activity.action }}
                                        <Link :href="`/posts/${activity.slug}`" class="font-medium text-primary hover:underline">
                                            "{{ activity.title }}"
                                        </Link>
                                    </p>
                                    <p v-if="activity.content" class="mt-1 text-xs text-muted-foreground">
                                        {{ activity.content }}
                                    </p>
                                    <p class="mt-1 text-xs text-muted-foreground">
                                        {{ formatTimeAgo(activity.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Category Stats & Community Insights -->
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <!-- Category Performance -->
            <Card>
                <CardHeader>
                    <CardTitle>Category Performance</CardTitle>
                    <CardDescription>Your posts by category</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="categoryStats.length === 0" class="py-8 text-center text-muted-foreground">
                        <BarChart3 class="mx-auto mb-2 h-8 w-8 opacity-50" />
                        <p>No categories yet</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="category in categoryStats" :key="category.slug" class="flex items-center justify-between rounded-lg border p-3">
                            <div class="flex items-center gap-3">
                                <div :style="{ backgroundColor: category.color }" class="h-3 w-3 flex-shrink-0 rounded-full" />
                                <div>
                                    <p class="text-sm font-medium">{{ category.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ category.posts_count }} post{{ category.posts_count !== 1 ? 's' : '' }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium">{{ formatNumber(category.total_views) }}</p>
                                <p class="text-xs text-muted-foreground">views</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Engagement Trends -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <LineChart class="h-5 w-5" />
                        Engagement Trends
                    </CardTitle>
                    <CardDescription>Last 7 days activity</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="last7Days.length === 0" class="py-8 text-center text-muted-foreground">
                        <LineChart class="mx-auto mb-2 h-8 w-8 opacity-50" />
                        <p>No engagement data yet</p>
                        <p class="text-sm">
                            <Link class="text-primary hover:underline" href="/posts/create">Create your first post</Link>
                        </p>
                    </div>

                    <div v-else class="space-y-4">
                        <!-- Chart Visualization -->
                        <TooltipProvider>
                            <div class="relative flex h-32 items-end justify-between gap-1 rounded-lg border bg-muted/20 p-2">
                                <div v-for="day in last7Days" :key="day.date" class="flex flex-1 flex-col items-center gap-1">
                                    <!-- Stacked bars with tooltip -->
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <div class="relative flex w-full max-w-8 cursor-pointer flex-col justify-end" style="height: 80px">
                                                <!-- Posts bar -->
                                                <div
                                                    v-if="day.posts > 0"
                                                    :style="{ height: getBarHeight(day.posts) + '%' }"
                                                    class="w-full rounded-t-sm bg-blue-500 transition-all hover:bg-blue-600"
                                                ></div>
                                                <!-- Comments bar -->
                                                <div
                                                    v-if="day.comments > 0"
                                                    :style="{ height: getBarHeight(day.comments) + '%' }"
                                                    class="w-full bg-green-500 transition-all hover:bg-green-600"
                                                ></div>
                                                <!-- Likes bar -->
                                                <div
                                                    v-if="day.likes_received > 0"
                                                    :style="{ height: getBarHeight(day.likes_received) + '%' }"
                                                    class="w-full rounded-b-sm bg-red-500 transition-all hover:bg-red-600"
                                                ></div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <div class="space-y-1">
                                                <div v-if="day.posts > 0" class="flex items-center gap-2">
                                                    <span>📝</span>
                                                    <span>{{ day.posts }} post{{ day.posts !== 1 ? 's' : '' }}</span>
                                                </div>
                                                <div v-if="day.comments > 0" class="flex items-center gap-2">
                                                    <span>💬</span>
                                                    <span>{{ day.comments }} comment{{ day.comments !== 1 ? 's' : '' }}</span>
                                                </div>
                                                <div v-if="day.likes_received > 0" class="flex items-center gap-2">
                                                    <span>❤️</span>
                                                    <span>{{ day.likes_received }} like{{ day.likes_received !== 1 ? 's' : '' }}</span>
                                                </div>
                                                <div v-if="day.posts === 0 && day.comments === 0 && day.likes_received === 0">No activity</div>
                                            </div>
                                        </TooltipContent>
                                    </Tooltip>
                                    <!-- Date label -->
                                    <span class="font-mono text-xs text-muted-foreground">
                                        {{ formatDate(day.date) }}
                                    </span>
                                </div>
                            </div>
                        </TooltipProvider>

                        <!-- Legend -->
                        <div class="flex items-center justify-center gap-6 text-xs">
                            <div class="flex items-center gap-2">
                                <div class="h-3 w-3 rounded-sm bg-blue-500"></div>
                                <span class="text-muted-foreground">Posts</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-3 w-3 rounded-sm bg-green-500"></div>
                                <span class="text-muted-foreground">Comments</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-3 w-3 rounded-sm bg-red-500"></div>
                                <span class="text-muted-foreground">Likes Received</span>
                            </div>
                        </div>

                        <!-- Quick Action -->
                        <div class="flex justify-center pt-2">
                            <Link
                                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground transition-colors hover:bg-primary/90"
                                href="/posts/create"
                            >
                                <PenTool class="h-4 w-4" />
                                Create New Post
                                <ArrowUpRight class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
