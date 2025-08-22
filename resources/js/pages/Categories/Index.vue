<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { TrendingUp } from 'lucide-vue-next';

defineProps<{ categories: any[] }>();
defineOptions({ layout: SiteLayout });
</script>

<template>
    <Head title="Categories" />
    <div class="container mx-auto px-6 py-10">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Browse Categories</h1>
            <p class="text-muted-foreground">Explore posts organized by topic</p>
        </div>

        <div v-if="categories?.length" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="category in categories"
                :key="category.slug"
                :href="route('categories.show', category.slug)"
                class="group rounded-lg border bg-card p-6 transition-colors hover:bg-muted"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-3">
                        <div 
                            :style="{ backgroundColor: category.color }" 
                            class="h-4 w-4 rounded-full"
                        />
                        <div>
                            <h3 class="font-semibold group-hover:text-primary">{{ category.name }}</h3>
                            <p v-if="category.description" class="text-sm text-muted-foreground">
                                {{ category.description }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                    <div class="flex space-x-4">
                        <span>{{ category.posts_count || 0 }} posts</span>
                        <span v-if="category.total_comments">{{ category.total_comments }} comments</span>
                        <span v-if="category.total_likes">{{ category.total_likes }} likes</span>
                    </div>
                </div>
            </Link>
        </div>

        <div v-else class="rounded border bg-card p-8 text-center text-muted-foreground">
            <TrendingUp class="mx-auto h-12 w-12 mb-4 opacity-50" />
            <h3 class="text-lg font-semibold mb-2">No categories yet</h3>
            <p>Categories will appear here as they're created.</p>
        </div>
    </div>
</template>