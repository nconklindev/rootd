<script setup lang="ts">
import FormDescription from '@/components/FormDescription.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface PostType {
    value: string;
    label: string;
}

const props = defineProps<{
    post: any;
    postTypes: PostType[];
}>();

const form = useForm({
    title: props.post.title || '',
    content: props.post.content || '',
    body: props.post.body || '',
    excerpt: props.post.excerpt || '',
    type: props.post.type || 'article',
});

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
    form.put(route('posts.update', props.post.slug));
}
</script>

<template>
    <SiteLayout title="Edit Post">
        <div class="container mx-auto px-6 py-10">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold">Edit Post</h1>
                <Link :href="route('posts.show', props.post.slug)" class="text-primary underline-offset-4 hover:underline">Cancel</Link>
            </div>

            <div class="rounded border bg-card p-6">
                <form class="space-y-5" @submit.prevent="submit">
                    <!-- Title -->
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="title">Title</label>
                        <Input id="title" v-model="form.title" :placeholder="titlePlaceholder" required />
                        <InputError :message="form.errors.title" />
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="type">Type</label>
                        <Select id="type" v-model="form.type" class="w-full">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Select a type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="type in postTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.type" />
                    </div>

                    <!-- Content/Code Snippet -->
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="content">{{ form.type === 'code' ? 'Code' : 'Content' }}</label>
                        <Textarea
                            id="content"
                            v-model="form.content"
                            :class="[
                                'w-full rounded-md border border-input bg-background px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm',
                                form.type === 'code' ? 'font-mono text-sm leading-relaxed' : '',
                            ]"
                            :placeholder="form.type === 'code' ? 'Paste your code here...' : 'Write your content here...'"
                            required
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
                            placeholder="Brief one-line summary for previews and search results..."
                            maxlength="255"
                        />
                        <FormDescription message="This appears in post previews and search results. If empty, we'll auto-generate from your code." />
                        <InputError :message="form.errors.excerpt" />
                    </div>

                    <div class="pt-2">
                        <Button type="submit">Update Post</Button>
                    </div>
                </form>
            </div>
        </div>
    </SiteLayout>
</template>
