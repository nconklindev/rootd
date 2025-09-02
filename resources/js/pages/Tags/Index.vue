<script lang="ts" setup>
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link, usePage, WhenVisible } from '@inertiajs/vue3';
import { Search, Tag as TagIcon, TrendingUp, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

defineOptions({ layout: SiteLayout });

interface Tag {
    id: number;
    name: string;
    slug: string;
    color: string;
    description?: string;
    posts_count: number;
}

interface Pagination {
    current_page: number;
    last_page: number;
    has_more_pages: boolean;
    per_page: number;
    total: number;
}

const props = defineProps<{
    tags: Tag[];
    pagination: Pagination;
    allTagsCount: Tag[];
    totalTags?: number;
    totalPosts?: number;
}>();

const page = usePage();
const searchQuery = ref('');

watch(
    () => props.pagination.current_page,
    (newPage, oldPage) => {
        // Skip on initial load
        if (oldPage === undefined) return;

        // If we moved to a higher page, append the new tags
        if (newPage > oldPage) {
            const existingIds = new Set(allLoadedTags.value.map((tag) => tag.id));
            const newTags = props.tags.filter((tag) => !existingIds.has(tag.id));
            allLoadedTags.value.push(...newTags);
        }
    },
);

// All loaded tags (from all pages)
const allLoadedTags = ref<Tag[]>(props.tags);

const filteredTags = computed(() => {
    if (!searchQuery.value) {
        return allLoadedTags.value;
    }

    return allLoadedTags.value.filter(
        (tag) =>
            tag.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            tag.description?.toLowerCase().includes(searchQuery.value.toLowerCase()),
    );
});

const popularTags = computed(() => {
    return filteredTags.value.filter((tag) => tag.posts_count > 0).slice(0, 12);
});

const allTags = computed(() => {
    return filteredTags.value.sort((a, b) => a.name.localeCompare(b.name));
});

const totalPosts = computed(() => {
    return props.totalPosts || allLoadedTags.value.reduce((total, tag) => total + tag.posts_count, 0);
});

const reachedEnd = computed(() => {
    return props.pagination.current_page >= props.pagination.last_page;
});

const getTagSizeClass = (postsCount: number) => {
    if (postsCount >= 50) return 'text-2xl px-6 py-3';
    if (postsCount >= 20) return 'text-xl px-5 py-2.5';
    if (postsCount >= 10) return 'text-lg px-4 py-2';
    if (postsCount >= 5) return 'text-base px-3 py-2';
    return 'text-sm px-3 py-1.5';
};

const clearSearch = () => {
    searchQuery.value = '';
};

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && searchQuery.value) {
        clearSearch();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <Head title="Tags" />
    <div class="container mx-auto min-w-0 px-6 py-10">
        <div class="mb-8">
            <Heading description="Explore topics and discover content organized by tags" title="Tags" />
        </div>

        <!-- Search Bar -->
        <div class="mb-8 flex justify-center">
            <div class="relative w-full max-w-md">
                <Input v-model="searchQuery" class="pr-10 pl-10" placeholder="Search tags..." />
                <span class="absolute inset-y-0 start-0 flex items-center justify-center px-2">
                    <Search class="size-5 text-muted-foreground" />
                </span>
                <button
                    v-if="searchQuery"
                    class="absolute inset-y-0 end-0 flex cursor-pointer items-center justify-center rounded-md px-2 text-muted-foreground transition-colors hover:text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-1 focus:outline-none"
                    type="button"
                    @click="clearSearch"
                >
                    <X class="size-4 transition-transform hover:scale-110" />
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-lg border bg-card p-6 text-center">
                <div class="text-2xl font-bold text-primary">{{ allTagsCount }}</div>
                <div class="text-sm text-muted-foreground">Total Tags</div>
            </div>
            <div class="rounded-lg border bg-card p-6 text-center">
                <div class="text-2xl font-bold text-primary">{{ totalPosts }}</div>
                <div class="text-sm text-muted-foreground">Total Posts</div>
            </div>
            <div class="rounded-lg border bg-card p-6 text-center">
                <div class="text-2xl font-bold text-primary">{{ popularTags.length }}</div>
                <div class="text-sm text-muted-foreground">Active Tags</div>
            </div>
        </div>

        <!-- Popular Tags Cloud -->
        <div v-if="popularTags.length > 0" class="mb-12">
            <div class="mb-6 flex items-center space-x-2">
                <TrendingUp class="h-5 w-5 text-primary" />
                <h2 class="text-xl font-semibold">Popular Tags</h2>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-3 rounded-lg border bg-card p-8">
                <Link
                    v-for="tag in popularTags"
                    :key="tag.id"
                    :class="[
                        'inline-flex items-center space-x-2 rounded-full border-2 font-medium transition-all hover:scale-105 hover:shadow-md',
                        getTagSizeClass(tag.posts_count),
                    ]"
                    :href="`/tags/${tag.slug}`"
                    :style="{ backgroundColor: tag.color + '20', borderColor: tag.color }"
                >
                    <div :style="{ backgroundColor: tag.color }" class="h-2 w-2 rounded-full" />
                    <span>{{ tag.name }}</span>
                    <Badge :style="{ backgroundColor: tag.color, color: 'white' }" class="ml-1 text-xs">
                        {{ tag.posts_count }}
                    </Badge>
                </Link>
            </div>
        </div>

        <!-- All Tags Grid -->
        <div class="mb-6 flex items-center space-x-2">
            <TagIcon class="h-5 w-5 text-primary" />
            <h2 class="text-xl font-semibold">All Tags</h2>
            <span class="text-sm text-muted-foreground">({{ filteredTags.length }})</span>
        </div>

        <div v-if="filteredTags.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <Link
                v-for="tag in filteredTags"
                :key="tag.id"
                :href="`/tags/${tag.slug}`"
                class="group block rounded-lg border bg-card p-4 transition-all hover:border-primary/50 hover:shadow-md"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3">
                            <div :style="{ backgroundColor: tag.color }" class="h-4 w-4 flex-shrink-0 rounded-full" />
                            <h3 class="font-medium transition-colors group-hover:text-primary">
                                {{ tag.name }}
                            </h3>
                        </div>
                        <p v-if="tag.description" class="mt-2 line-clamp-2 text-sm text-muted-foreground">
                            {{ tag.description }}
                        </p>
                    </div>
                    <Badge :style="{ backgroundColor: tag.color + '20', color: tag.color }" class="ml-2 flex-shrink-0">
                        {{ tag.posts_count }} {{ tag.posts_count === 1 ? 'post' : 'posts' }}
                    </Badge>
                </div>
            </Link>
        </div>

        <!-- Infinite Scroll Loader -->
        <WhenVisible
            :always="!reachedEnd"
            :buffer="100"
            :params="{
                only: ['tags', 'pagination', 'allTags'],
                data: {
                    page: pagination.current_page + 1,
                },
                preserveUrl: true,
            }"
        >
            <template #fallback>
                <div class="flex items-center justify-center py-8 text-center">
                    <div class="inline-flex items-center space-x-2 text-center text-muted-foreground">
                        <div class="h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
                        <span class="text-sm">Loading more tags...</span>
                    </div>
                </div>
            </template>
        </WhenVisible>

        <!-- Empty State -->
        <div v-if="filteredTags.length === 0" class="py-12 text-center">
            <TagIcon class="mx-auto h-12 w-12 text-muted-foreground" />
            <h3 class="mt-4 text-lg font-medium">No tags found</h3>
            <p class="mt-2 text-muted-foreground">
                {{ searchQuery ? 'Try adjusting your search query.' : 'No tags have been created yet.' }}
            </p>
            <div v-if="searchQuery" class="mt-4">
                <Button variant="outline" @click="clearSearch"> Clear search </Button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
