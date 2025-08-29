<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import TipTapEditor from '@/components/TipTapEditor.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/components/ui/tags-input';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Define the layout for this page
// using InertiaJS Persistent Layouts
// https://inertiajs.com/pages#persistent-layouts
defineOptions({ layout: SiteLayout });

interface PostType {
    value: string;
    label: string;
}

interface Category {
    value: number;
    label: string;
    slug: string;
    color: string;
}
const page = usePage();
// Define props
const props = defineProps<{
    postTypes: PostType[];
    categories: Category[];
}>();

// Define the form for submitting the post
const form = useForm({
    title: '',
    content: '',
    excerpt: '',
    category_id: '',
    body: '',
    type: 'text',
    file: null,
    tags: [],
    link: '',
    description: '',
});

const isFormValid = computed(() => {
    return form.title && form.content.length > 0 && form.category_id && form.type;
});

// Create a computed property that updates the placeholder text for the title based on the selected post type
// Useful for UX
const titlePlaceholder = computed(() => {
    switch (form.type) {
        case 'text':
            return 'How to Use Burp Suite to Test Your API endpoints';
        case 'media':
            return 'The Software Development Life Cycle Infographic';
        default:
            return 'My awesome post title';
    }
});

function submit(): void {
    form.post(route('posts.store'), {
        preserveState: (page) => Object.keys(page.props.errors).length > 0,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Create Post" />
    <div class="container mx-auto min-w-0 px-6 py-10">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">New Post</h1>
            <Link :href="route('posts.index')" class="text-primary underline-offset-4 hover:underline">Back</Link>
        </div>

        <div class="overflow-x-hidden rounded border bg-card p-6">
            <form class="space-y-5" @submit.prevent="submit">
                <!-- Title -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium" for="title">Title</label>
                        <Input id="title" v-model="form.title" :placeholder="titlePlaceholder" required />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <div class="w-full">
                        <label class="mb-1 block text-sm font-medium" for="category">Category</label>
                        <Select id="category" v-model="form.category_id" class="w-full" size="sm">
                            <SelectTrigger class="w-full max-w-full min-w-0">
                                <SelectValue placeholder="Select a category" />
                            </SelectTrigger>
                            <SelectContent class="max-w-xs min-w-[--reka-select-trigger-width]" position="popper">
                                <SelectItem v-for="category in props.categories" :key="category.value" :value="category.value.toString()">
                                    <div class="flex items-center gap-2">
                                        <div :style="{ backgroundColor: category.color }" class="h-3 w-3 rounded-full"></div>
                                        {{ category.label }}
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.category_id" class="mt-2" />
                    </div>
                </div>

                <!-- Type -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="type">Type</label>
                        <Select id="type" v-model="form.type" class="w-full">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Select a type" />
                            </SelectTrigger>
                            <SelectContent
                                :side-offset="4"
                                align="start"
                                avoid-collisions
                                class="min-w-[--reka-select-trigger-width]"
                                position="popper"
                            >
                                <SelectItem v-for="type in postTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.type" class="mt-2" />
                    </div>
                </div>

                <div v-if="form.type === 'media'">
                    <label class="mb-1 block text-sm font-medium" for="link">Link <span class="text-muted-foreground">(Optional)</span></label>
                    <Input
                        id="link"
                        v-model="form.link"
                        placeholder="https://thehackernews.com/2025/08/uk-government-drops-apple-encryption.html"
                        type="url"
                    />
                    <InputError :message="form.errors.link" class="mt-2" />
                </div>

                <!-- Content/Code Snippet -->
                <div>
                    <label class="mb-1 block text-sm font-medium" for="content">Content</label>
                    <TipTapEditor
                        id="content"
                        ref="contentInput"
                        v-model="form.content"
                        class="w-full shadow-xs transition-[color,box-shadow] outline-none focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50"
                        placeholder="Write your content here..."
                    />
                    <InputError :message="form.errors.content" class="mt-2" />
                </div>

                <!-- Tags -->
                <div>
                    <label class="mb-1 block text-sm font-medium" for="tags">Tags</label>
                    <TagsInput id="tags" v-model="form.tags" :max="5" class="max-w-sm !bg-transparent dark:!bg-input/30">
                        <TagsInputItem v-for="item in form.tags" :key="item" :value="item">
                            <TagsInputItemText />
                            <TagsInputItemDelete />
                        </TagsInputItem>

                        <TagsInputInput placeholder="Tags..." />
                    </TagsInput>
                    <InputError :message="form.errors.tags" class="mt-2" />
                </div>

                <!-- File Attachment -->
                <div class="grid w-full max-w-sm items-center gap-1.5">
                    <label class="mb-1 block text-sm font-medium" for="file">Attachment</label>
                    <Input id="file" :required="form.type === 'media'" class="w-full" type="file" @input="form.file = $event.target.files[0]" />
                    <InputError :message="form.errors.file" class="mt-2" />
                </div>

                <progress v-if="form.progress" :value="form.progress.percentage" max="100">{{ form.progress.percentage }}%</progress>

                <div class="pt-2">
                    <Button :disabled="!isFormValid || form.processing" type="submit">Create</Button>
                </div>
            </form>
        </div>
    </div>
</template>
