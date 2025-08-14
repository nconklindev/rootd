<script setup lang="ts">
import { reactive } from 'vue'
import { router, Link } from '@inertiajs/vue3'

const form = reactive({
  slug: '',
  content: '',
  excerpt: '',
  type: 'article',
  status: 'draft',
})

function submit() {
  router.post(route('posts.store'), form)
}
</script>

<template>
  <div class="container mx-auto px-6 py-10">
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold">New Post</h1>
      <Link :href="route('posts.index')" class="text-gray-600 hover:underline">Back</Link>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium">Slug</label>
        <input v-model="form.slug" class="mt-1 w-full rounded border px-3 py-2" placeholder="my-post" />
      </div>

      <div>
        <label class="block text-sm font-medium">Excerpt</label>
        <input v-model="form.excerpt" class="mt-1 w-full rounded border px-3 py-2" placeholder="Short summary" />
      </div>

      <div>
        <label class="block text-sm font-medium">Content</label>
        <textarea v-model="form.content" rows="10" class="mt-1 w-full rounded border px-3 py-2" placeholder="Write your content here..." />
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="block text-sm font-medium">Type</label>
          <select v-model="form.type" class="mt-1 w-full rounded border px-3 py-2">
            <option value="article">Article</option>
            <option value="note">Note</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium">Status</label>
          <select v-model="form.status" class="mt-1 w-full rounded border px-3 py-2">
            <option value="draft">Draft</option>
            <option value="published">Published</option>
          </select>
        </div>
      </div>

      <div class="pt-2">
        <button class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" type="submit">Create</button>
      </div>
    </form>
  </div>
</template>
