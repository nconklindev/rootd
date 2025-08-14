<script setup lang="ts">
import { reactive } from 'vue'
import { router, Link } from '@inertiajs/vue3'

const props = defineProps<{ post: any }>()

const form = reactive({
  slug: props.post.slug,
  content: props.post.content,
  excerpt: props.post.excerpt,
  type: props.post.type ?? 'article',
  status: props.post.status ?? 'draft',
})

function submit() {
  router.put(route('posts.update', props.post.slug), form)
}
</script>

<template>
  <div class="container mx-auto px-6 py-10">
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold">Edit Post</h1>
      <Link :href="route('posts.show', props.post.slug)" class="text-gray-600 hover:underline">Cancel</Link>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium">Slug</label>
        <input v-model="form.slug" class="mt-1 w-full rounded border px-3 py-2" />
      </div>

      <div>
        <label class="block text-sm font-medium">Excerpt</label>
        <input v-model="form.excerpt" class="mt-1 w-full rounded border px-3 py-2" />
      </div>

      <div>
        <label class="block text-sm font-medium">Content</label>
        <textarea v-model="form.content" rows="10" class="mt-1 w-full rounded border px-3 py-2" />
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
        <button class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" type="submit">Save Changes</button>
      </div>
    </form>
  </div>
</template>
