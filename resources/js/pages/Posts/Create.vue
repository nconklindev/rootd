<script lang="ts" setup>
import FormDescription from '@/components/FormDescription.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/components/ui/tags-input';
import { Textarea } from '@/components/ui/textarea';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
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
    type: 'article',
    file: null,
    language: '',
    tags: [],
    link: '',
    description: '',
});

// Define the list of languages for code snippets and sort it alphabetically for display in the select box
// I'm not going to worry about alphabetizing it here...
const languages = [
    { value: 'python', label: 'Python' },
    { value: 'c', label: 'C' },
    { value: 'csharp', label: 'C#' },
    { value: 'rust', label: 'Rust' },
    { value: 'go', label: 'Go' },
    { value: 'java', label: 'Java' },
    { value: 'javascript', label: 'JavaScript' },
    { value: 'typescript', label: 'TypeScript' },
    { value: 'php', label: 'PHP' },
    { value: 'sql', label: 'SQL' },
    { value: 'bash', label: 'Bash' },
    { value: 'perl', label: 'Perl' },
    { value: 'ruby', label: 'Ruby' },
    { value: 'swift', label: 'Swift' },
    { value: 'kotlin', label: 'Kotlin' },
    { value: 'scala', label: 'Scala' },
    { value: 'elixir', label: 'Elixir' },
    { value: 'lua', label: 'Lua' },
    { value: 'r', label: 'R' },
    { value: 'dart', label: 'Dart' },
    { value: 'objective-c', label: 'Objective-C' },
].toSorted((a, b) => a.label.localeCompare(b.label)) as { value: string; label: string }[];

// Create a computed property that updates the placeholder text for the title based on the selected post type
// Useful for UX
const titlePlaceholder = computed(() => {
    switch (form.type) {
        case 'article':
            return 'How to Use Burp Suite to Test Your API endpoints';
        case 'code':
            return 'POC: Exploiting MS17-010 in Windows 7';
        case 'link':
            return 'New security vulnerability discovered in popular library';
        case 'image':
            return 'The Software Development Life Cycle Infographic';
        case 'file':
            return 'New scanning tool written in Go';
        default:
            return 'My awesome post title';
    }
});

function submit(): void {
    form.post(route('posts.store'));
    form.resetAndClearErrors();
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
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="w-full">
                        <label class="mb-1 block text-sm font-medium" for="category">Category</label>
                        <Select id="category" v-model="form.category_id" class="w-full" size="sm">
                            <SelectTrigger class="w-full max-w-full min-w-0">
                                <SelectValue placeholder="Select a category" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="category in props.categories" :key="category.value" :value="category.value.toString()">
                                    <div class="flex items-center gap-2">
                                        <div :style="{ backgroundColor: category.color }" class="h-3 w-3 rounded-full"></div>
                                        {{ category.label }}
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.category_id" />
                    </div>
                </div>

                <!-- Type -->
                <div :class="{ 'grid-cols-2': form.type === 'code' }" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                        <InputError :message="form.errors.type" />
                    </div>

                    <!-- Language -->
                    <div v-if="form.type === 'code'">
                        <label class="mb-1 block text-sm font-medium" for="language">Language</label>
                        <Select id="language" v-model="form.language" :required="form.type === 'code'" class="w-full">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Select a language" />
                            </SelectTrigger>
                            <SelectContent :side-offset="4" align="start" class="max-w-[200px] min-w-[--reka-select-trigger-width]" position="popper">
                                <SelectItem v-for="language in languages" :key="language.value" :value="language.label">{{
                                    language.label
                                }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.language" />
                    </div>
                </div>

                <div v-if="form.type === 'link'">
                    <label class="mb-1 block text-sm font-medium" for="link">Link</label>
                    <Input
                        id="link"
                        v-model="form.link"
                        :required="form.type === 'link'"
                        placeholder="https://thehackernews.com/2025/08/uk-government-drops-apple-encryption.html"
                        type="url"
                    />
                    <InputError :message="form.errors.link" />
                </div>

                <!-- Content/Code Snippet -->
                <div>
                    <label class="mb-1 block text-sm font-medium" for="content">{{ form.type === 'code' ? 'Code Snippet' : 'Content' }}</label>
                    <Textarea
                        id="content"
                        v-model="form.content"
                        :class="[
                            'w-full rounded-md border border-input bg-background px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm',
                            form.type === 'code' ? 'font-mono text-sm leading-relaxed' : '',
                        ]"
                        :placeholder="form.type === 'code' ? 'Paste your code here...' : 'Write your content here...'"
                        :required="form.type === 'article' || form.type === 'code'"
                        rows="30"
                    />
                    <FormDescription
                        v-if="form.type === 'code'"
                        message="Your code will be automatically highlighted based on the selected language when displayed."
                    />
                    <InputError :message="form.errors.content" />
                </div>

                <!-- Code Body -->
                <div v-if="form.type === 'code'">
                    <label class="mb-1 block text-sm font-medium" for="body">Body & Context</label>
                    <Textarea
                        id="body"
                        v-model="form.body"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                        placeholder="Explain what this code does, how to use it, any dependencies, caveats, or additional context..."
                        rows="8"
                    />
                    <FormDescription message="Provide detailed description, usage instructions, or context about your code snippet." />
                    <InputError :message="form.errors.body" />
                </div>

                <!-- Short Summary -->
                <div v-if="form.type === 'code'">
                    <label class="mb-1 block text-sm font-medium" for="description"
                        >Short Summary <span class="text-muted-foreground">(Optional)</span></label
                    >
                    <Input
                        id="description"
                        v-model="form.excerpt"
                        maxlength="255"
                        placeholder="Brief one-line summary for previews and search results..."
                    />
                    <FormDescription message="This appears in post previews and search results. If empty, we'll auto-generate from your code." />
                    <InputError :message="form.errors.excerpt" />
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
                </div>

                <!-- File Attachment -->
                <div v-if="form.type === 'code' || form.type === 'image' || form.type === 'file'" class="grid w-full max-w-sm items-center gap-1.5">
                    <label class="mb-1 block text-sm font-medium" for="file">Attachment</label>
                    <Input
                        id="file"
                        :required="form.type === 'image' || form.type === 'file'"
                        class="w-full"
                        type="file"
                        @input="form.file = $event.target.files[0]"
                    />
                    <InputError :message="form.errors.file" />
                </div>

                <progress v-if="form.progress" :value="form.progress.percentage" max="100">{{ form.progress.percentage }}%</progress>

                <div class="pt-2">
                    <Button type="submit">Create</Button>
                </div>
            </form>
        </div>
    </div>
</template>
