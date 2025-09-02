<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Eye, Hash, MessageCircle, TrendingUp, Users } from 'lucide-vue-next';

defineOptions({ layout: SiteLayout });

interface Post {
    id: number;
    title: string;
    slug: string;
    excerpt?: string;
    content: string;
    created_at: string;
    updated_at: string;
    views_count?: number;
    comments_count?: number;
    likes_count?: number;
    user?: {
        id: number;
        name: string;
        username: string;
    };
}

interface Tag {
    id: number;
    name: string;
    slug: string;
    color: string;
    description?: string;
    created_at: string;
    last_used_at: string;
    posts_count: number;
    posts?: Post[];
}

const props = defineProps<{ tag: Tag }>();

const formatDate = (dateString: string) => {
    if (!dateString) return '';

    const date = new Date(dateString);

    // Check if date is valid
    if (isNaN(date.getTime())) return dateString;

    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const getPostExcerpt = (content: string, length = 150) => {
    if (!content) return '';
    const plainText = content.replace(/<[^>]*>/g, '');
    return plainText.length > length ? plainText.substring(0, length) + '...' : plainText;
};
</script>

<template>
    <Head :title="`${tag.name} Tag`" />
    <div class="container mx-auto min-w-0 px-6 py-10">
        <!-- Back Navigation -->
        <div class="mb-6">
            <Link class="inline-flex items-center space-x-2 text-sm text-muted-foreground transition-colors hover:text-foreground" href="/tags">
                <ArrowLeft class="h-4 w-4" />
                <span>Back to Tags</span>
            </Link>
        </div>

        <!-- Tag Header -->
        <div class="mb-8">
            <div class="flex items-center space-x-4">
                <div :style="{ backgroundColor: tag.color }" class="inline-flex h-12 w-12 items-center justify-center rounded-full">
                    <Hash class="h-6 w-6 text-white" />
                </div>
                <div class="flex flex-col space-y-1">
                    <h1 class="font-mono text-xl leading-tight font-bold tracking-tight">{{ tag.name }}</h1>
                    <p class="not-prose text-lg text-muted-foreground">Explore posts tagged with "{{ tag.name }}"</p>
                </div>
            </div>
        </div>

        <!-- Tag Statistics -->
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm leading-none font-medium">Total Posts</CardTitle>
                    <TrendingUp class="h-4 w-4 shrink-0 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ tag.posts_count }}</div>
                    <p class="text-xs text-muted-foreground">{{ tag.posts_count === 1 ? 'post' : 'posts' }} with this tag</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm leading-none font-medium">Tag Color</CardTitle>
                    <div :style="{ backgroundColor: tag.color }" class="h-4 w-4 shrink-0 rounded-full" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ tag.color.toUpperCase() }}</div>
                    <p class="text-xs text-muted-foreground">Unique color identifier</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm leading-none font-medium">Tag Created</CardTitle>
                    <Calendar class="h-4 w-4 shrink-0 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ formatDate(tag.created_at) }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm leading-none font-medium">Last Updated</CardTitle>
                    <Calendar class="h-4 w-4 shrink-0 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ formatDate(tag.last_used_at) }}</div>
                </CardContent>
            </Card>
        </div>

        <!-- Tag Information Card -->
        <Card class="mb-8">
            <CardHeader>
                <CardTitle class="flex items-center space-x-2">
                    <Hash class="h-5 w-5 shrink-0" />
                    <span class="leading-none">Tag Information</span>
                </CardTitle>
                <CardDescription> Detailed information about the "{{ tag.name }}" tag </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Name</label>
                        <div class="flex items-center space-x-2">
                            <div :style="{ backgroundColor: tag.color }" class="h-3 w-3 rounded-full" />
                            <span class="text-sm">{{ tag.name }}</span>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Color</label>
                        <div class="flex items-center space-x-2">
                            <div :style="{ backgroundColor: tag.color }" class="h-6 w-12 rounded border" />
                            <span class="font-mono text-sm">{{ tag.color }}</span>
                        </div>
                    </div>
                    <div class="space-y-2 space-x-2">
                        <label class="text-sm font-medium">Posts Count</label>
                        <Badge :style="{ backgroundColor: tag.color + '20', color: tag.color }" class="w-fit">
                            {{ tag.posts_count }} {{ tag.posts_count === 1 ? 'post' : 'posts' }}
                        </Badge>
                    </div>
                </div>
                <div v-if="tag.description" class="space-y-2">
                    <label class="text-sm font-medium">Description</label>
                    <p class="text-sm text-muted-foreground">{{ tag.description }}</p>
                </div>
            </CardContent>
        </Card>

        <!-- Posts with this tag -->
        <div v-if="tag.posts && tag.posts.length > 0">
            <div class="mb-6 flex items-center space-x-2">
                <TrendingUp class="h-5 w-5 shrink-0 text-primary" />
                <h2 class="text-xl leading-none font-semibold">Posts with this tag</h2>
                <Badge :style="{ backgroundColor: tag.color + '20', color: tag.color }">
                    {{ tag.posts.length }}
                </Badge>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card v-for="post in tag.posts" :key="post.id" class="group transition-all hover:shadow-md">
                    <CardHeader>
                        <CardTitle class="line-clamp-2 md:line-clamp-3">
                            <Link :href="`/posts/${post.slug}`" class="transition-colors group-hover:text-primary">
                                {{ post.title }}
                            </Link>
                        </CardTitle>
                        <CardDescription class="flex items-center-safe space-x-4 text-sm">
                            <div class="flex items-center-safe space-x-1.5">
                                <Calendar class="h-3 w-3" />
                                <span>{{ formatDate(post.created_at) }}</span>
                            </div>
                            <div v-if="post.user" class="flex items-center-safe space-x-1.5">
                                <Users class="h-3 w-3" />
                                <span>{{ post.user.name }}</span>
                            </div>
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p v-if="post.excerpt" class="line-clamp-3 text-sm text-muted-foreground">
                            {{ post.excerpt }}
                        </p>
                        <p v-else class="line-clamp-3 text-sm text-muted-foreground">
                            {{ getPostExcerpt(post.content) }}
                        </p>
                        <div class="mt-4 flex items-center justify-between">
                            <div class="flex items-center space-x-4 text-xs text-muted-foreground">
                                <div v-if="post.views_count" class="flex items-center space-x-1">
                                    <Eye class="h-3 w-3" />
                                    <span>{{ post.views_count }}</span>
                                </div>
                                <div v-if="post.comments_count" class="flex items-center space-x-1">
                                    <MessageCircle class="h-3 w-3" />
                                    <span>{{ post.comments_count }}</span>
                                </div>
                            </div>
                            <Button as-child size="sm" variant="outline">
                                <Link :href="`/posts/${post.slug}`">Read More</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Empty state for no posts -->
        <div v-else class="py-12 text-center">
            <Hash class="mx-auto h-12 w-12 text-muted-foreground" />
            <h3 class="mt-4 text-lg font-medium">No posts found</h3>
            <p class="mt-2 text-muted-foreground">There are no posts tagged with "{{ tag.name }}" yet.</p>
            <div class="mt-6">
                <Button as-child>
                    <Link href="/posts">Browse All Posts</Link>
                </Button>
            </div>
        </div>
    </div>
</template>
