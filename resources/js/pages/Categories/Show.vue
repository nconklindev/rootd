<script lang="ts" setup>
import PostCard from '@/components/PostCard.vue';
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
import { Head, router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next';

const page = usePage();
defineProps<{ posts: any; category: any }>();
defineOptions({ layout: SiteLayout });
</script>

<template>
    <Head :title="category.name + '\'s Posts'" />
    <div class="container mx-auto px-6 py-10">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">{{ category.name }} Posts</h1>
        </div>

        <div v-if="posts?.data?.length" class="space-y-4">
            <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
        </div>

        <div v-else class="rounded border bg-card p-8 text-center text-muted-foreground">{{ category.name }} has no posts created for it yet.</div>

        <!-- Paginator -->
        <div v-if="posts?.total > 1 && posts?.last_page > 1" class="mt-8 flex">
            <Pagination
                v-slot="{ page }"
                :items-per-page="posts?.per_page"
                :page="posts?.current_page"
                :total="posts?.total"
                class="justify-end"
                @update:page="(p) => router.get(route('categories.show', category.slug), { page: p }, { preserveScroll: true, preserveState: true })"
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