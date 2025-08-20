<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationFirst,
    PaginationItem,
    PaginationLast,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    Code2,
    Eye,
    FileTerminal,
    FileText,
    Heart,
    Image,
    Link2,
    MessageSquareMoreIcon,
} from 'lucide-vue-next';

// Define the layout for this page
// using InertiaJS Persistent Layouts
// https://inertiajs.com/pages#persistent-layouts
defineOptions({ layout: SiteLayout });

defineProps<{ posts: any; can: any }>();

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
    <Head title="Posts" />
    <div class="container mx-auto px-6 py-10">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Posts</h1>
            <Button v-if="can?.create" as-child>
                <Link :href="route('posts.create')"> New Post</Link>
            </Button>
        </div>

        <div v-if="posts?.data?.length" class="space-y-4">
            <div
                v-for="post in posts.data"
                :key="post.id"
                class="group cursor-pointer rounded border bg-card p-4 duration-200 hover:bg-muted"
                @click="router.visit(route('posts.show', post.slug))"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Link :href="route('posts.show', post.slug)" class="cursor-pointer duration-200 group-hover:text-primary"
                            >{{ post.title }}
                        </Link>
                    </div>
                </div>
                <div class="mt-2.5 text-muted-foreground">{{ post.excerpt }}</div>
                <div class="mt-4 flex items-center justify-between text-sm text-zinc-300">
                    <div class="flex items-center space-x-2">
                        <div>
                            By {{ post.user?.name ?? 'Unknown' }} &bullet; <span>{{ post.created_at_human }}</span>
                        </div>
                        <div class="rounded p-1">
                            <component :is="getIconComponent(post.type_icon)" aria-hidden="true" class="size-5 shrink-0" />
                        </div>
                    </div>
                    <div class="flex flex-row space-x-8">
                        <div class="flex gap-x-2">
                            {{ post.views_count ?? 0 }}
                            <Eye class="size-5 text-zinc-300" />
                        </div>
                        <div class="flex gap-x-2">
                            {{ post.comments_count ?? 0 }}
                            <MessageSquareMoreIcon class="size-5 text-zinc-300" />
                        </div>
                        <div class="flex gap-x-2">
                            {{ post.likes_count ?? 0 }}
                            <Heart class="size-5 text-zinc-300" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="rounded border bg-card p-8 text-center text-muted-foreground">No posts yet.</div>

        <!-- Paginator -->
        <div v-if="posts?.total > 1 && posts?.last_page > 1" class="mt-8 flex">
            <Pagination
                v-slot="{ page }"
                :items-per-page="posts?.per_page"
                :page="posts?.current_page"
                :total="posts?.total"
                class="justify-end"
                @update:page="(p) => router.get(route('posts.index'), { page: p }, { preserveScroll: true, preserveState: true })"
            >
                <PaginationContent v-slot="{ items }">
                    <PaginationFirst class="rounded">
                        <ChevronsLeft />
                    </PaginationFirst>
                    <PaginationPrevious class="rounded">
                        <ChevronLeft />
                    </PaginationPrevious>

                    <template v-for="(item, index) in items" :key="index">
                        <PaginationItem
                            v-if="item.type === 'page'"
                            :key="index"
                            :is-active="item.value === page"
                            :value="item.value"
                            class="rounded border border-secondary/40"
                        >
                            {{ item.value }}
                        </PaginationItem>
                        <PaginationEllipsis v-else :key="item.type" :index="index">&#8230;</PaginationEllipsis>
                    </template>

                    <PaginationNext class="rounded">
                        <ChevronRight />
                    </PaginationNext>
                    <PaginationLast class="rounded">
                        <ChevronsRight />
                    </PaginationLast>
                </PaginationContent>
            </Pagination>
        </div>
    </div>
</template>
