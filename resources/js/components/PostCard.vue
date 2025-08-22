<script lang="ts" setup>
import { Link, router } from '@inertiajs/vue3';
import { Code2, Eye, FileTerminal, FileText, Heart, Image, Link2, MessageSquareMoreIcon } from 'lucide-vue-next';

interface Post {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    created_at_human: string;
    views_count?: number;
    comments_count?: number;
    likes_count?: number;
    type_icon: string;
    user?: {
        name: string;
    };
    category?: {
        name: string;
        color: string;
    };
}

interface Props {
    post: Post;
}

defineProps<Props>();

// Icon mapping for post types
const getIconComponent = (iconName: string) => {
    const iconMap: Record<string, any> = {
        FileText,
        Image,
        Code2,
        FileTerminal,
        Link2,
    };
    return iconMap[iconName] || FileText;
};
</script>

<template>
    <div class="group cursor-pointer rounded border bg-card p-4 duration-200 hover:bg-muted" @click="router.visit(route('posts.show', post.slug))">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <Link :href="route('posts.show', post.slug)" class="cursor-pointer duration-200 group-hover:text-primary">{{ post.title }} </Link>
            </div>
            <div
                v-if="post.category"
                :style="{ backgroundColor: post.category.color + '20', borderColor: post.category.color + '40' }"
                class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium transition-colors"
            >
                <span :style="{ color: post.category.color }" class="font-semibold">{{ post.category.name }}</span>
            </div>
            <div v-else class="inline-flex items-center rounded-full border border-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
                Uncategorized
            </div>
        </div>
        <div class="mt-2.5 text-muted-foreground">{{ post.excerpt }}</div>
        <!-- Mobile-first responsive metadata layout -->
        <div class="mt-4 space-y-3 text-sm text-zinc-300 sm:space-y-0">
            <!-- Author and post info row -->
            <div class="flex items-center justify-between">
                <div class="flex min-w-0 flex-1 items-center space-x-2">
                    <span class="truncate"
                        >By
                        <Link 
                            v-if="post.user?.name" 
                            :href="route('users.posts', post.user.name)" 
                            class="transition-colors duration-200 hover:text-primary" 
                            @click.stop
                        >{{
                            post.user.name
                        }}</Link>
                        <span v-else class="text-muted-foreground">Unknown</span>
                        &bullet;
                        <span>{{ post.created_at_human }}</span>
                    </span>
                    <div class="flex-shrink-0 rounded p-1">
                        <component :is="getIconComponent(post.type_icon)" aria-hidden="true" class="size-4 shrink-0" />
                    </div>
                </div>
            </div>

            <!-- Stats row - responsive layout -->
            <div class="flex items-center justify-end">
                <div class="flex space-x-4 sm:space-x-6">
                    <div class="flex items-center gap-x-1.5">
                        <Eye class="size-4 text-zinc-300" />
                        <span class="text-xs sm:text-sm">{{ post.views_count ?? 0 }}</span>
                    </div>
                    <div class="flex items-center gap-x-1.5">
                        <MessageSquareMoreIcon class="size-4 text-zinc-300" />
                        <span class="text-xs sm:text-sm">{{ post.comments_count ?? 0 }}</span>
                    </div>
                    <div class="flex items-center gap-x-1.5">
                        <Heart class="size-4 text-zinc-300" />
                        <span class="text-xs sm:text-sm">{{ post.likes_count ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
