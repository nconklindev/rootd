<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';

defineProps<{ posts: any; can: any }>();
</script>

<template>
    <div class="container mx-auto px-6 py-10">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Posts</h1>
            <Link v-if="can?.create" :href="route('posts.create')" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                New Post
            </Link>
        </div>

        <div v-if="posts?.data?.length" class="space-y-4">
            <div v-for="post in posts.data" :key="post.id" class="rounded border p-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500">Type: {{ post.type }} · Status: {{ post.status }}</div>
                    <Link :href="route('posts.show', post.slug)" class="text-blue-600 hover:underline">View</Link>
                </div>
                <div class="mt-2 text-gray-900">{{ post.excerpt }}</div>
                <div class="mt-1 text-sm text-gray-500">By {{ post.user?.name ?? 'Unknown' }}</div>
            </div>
        </div>

        <div v-else class="rounded border p-8 text-center text-gray-600">No posts yet.</div>

        <div v-if="posts?.links" class="mt-6 flex items-center gap-2">
            {{ posts.links() }}
            <!--      <template v-for="link in posts.links" :key="link.url + link.label">-->
            <!--        <Link v-if="link.url" :href="link.url" :class="['px-3 py-1 rounded border', link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50']">-->
            <!--          <span v-text="link.label" />-->
            <!--        </Link>-->
            <!--        <span v-else class="px-3 py-1 text-gray-400" v-text="link.label"></span>-->
            <!--      </template>-->
        </div>
    </div>
</template>
