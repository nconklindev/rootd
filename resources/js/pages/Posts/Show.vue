<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

defineProps<{ post: any }>()
</script>

<template>
  <div class="container mx-auto px-6 py-10">
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold">Post: {{ post.slug }}</h1>
      <div class="flex items-center gap-3">
        <Link :href="route('posts.index')" class="text-gray-600 hover:underline">Back</Link>
        <Link v-if="$page.props.auth?.user && $page.props.auth.user.id === post.author?.id" :href="route('posts.edit', post.slug)" class="rounded bg-gray-800 px-3 py-1 text-white hover:bg-black">Edit</Link>
      </div>
    </div>

    <div class="rounded border p-6">
      <div class="text-sm text-gray-500">Type: {{ post.type }} · Status: {{ post.status }}</div>
      <div class="mt-4 whitespace-pre-wrap">{{ post.content }}</div>
      <div class="mt-6 text-sm text-gray-500">By {{ post.author?.name ?? 'Unknown' }}</div>
    </div>
  </div>
</template>
