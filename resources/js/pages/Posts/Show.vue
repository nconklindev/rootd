<script lang="ts" setup>
import CommentItem from '@/components/CommentItem.vue';
import { Button } from '@/components/ui/button';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import hljs from 'highlight.js';
import 'highlight.js/styles/tokyo-night-dark.css';
import { ArrowLeft, Download, Eye, FileText, Heart, MessageSquareMoreIcon } from 'lucide-vue-next';
import { computed, nextTick, onMounted } from 'vue';

// Highlight code blocks after component mounts and DOM is ready
onMounted(() => {
    nextTick(() => {
        hljs.highlightAll();
    });
});

// Define the layout for this page
// using InertiaJS Persistent Layouts
// https://inertiajs.com/pages#persistent-layouts
defineOptions({ layout: SiteLayout });

// Define props
const props = defineProps<{ post: any; title: string }>();

const created_at_human = computed(() => {
    const date = new Date(props.post.created_at);
    const options: Intl.DateTimeFormatOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    };
    return date.toLocaleDateString('en-US', options);
});

// Define the form for adding comments
const form = useForm({ content: '' });

const viewsCount = computed(() => props.post.views_count ?? 0);
const commentsCount = computed(() => props.post.comments_count ?? 0);
const likesCount = computed(() => props.post.likes_count ?? 0);

/**
 * Submits a comment for the specified post, ensuring that the content field is not empty or whitespace only.
 * Performs a form reset upon successful submission.
 *
 * @return {void} This function does not return a value.
 */
function submitComment(): void {
    if (!form.content || !form.content.trim()) {
        return;
    }
    form.post(route('posts.comments.store', props.post.slug), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
}

const toggleLike = (): void => {
    if (props.post.is_liked === true) {
        // Unlike the post
        router.delete(route('posts.unlike', { id: props.post.id }), {
            preserveScroll: true,
        });
    } else {
        // Like the post
        router.post(
            route('posts.like', { id: props.post.id }),
            {},
            {
                preserveScroll: true,
            },
        );
    }
};

// Helper function to check if file is an image
const isImage = (mimeType: string): boolean => {
    return mimeType.startsWith('image/');
};
</script>

<template>
    <Head :title="title" />
    <div class="container mx-auto px-6 py-10">
        <div class="mb-6 flex items-center justify-between">
            <div class="flex flex-col">
                <h1 class="text-2xl leading-tight font-bold tracking-tight">{{ title }}</h1>
                <h2 class="text-sm leading-tight tracking-tight text-muted-foreground">{{ created_at_human }}</h2>
            </div>
            <div class="flex items-center-safe justify-items-center-safe gap-3">
                <Button as-child variant="link">
                    <Link :href="route('posts.index')" class="cursor-pointer" prefetch>
                        <ArrowLeft class="mr-2 inline h-4 w-4" />
                        Back
                    </Link>
                </Button>

                <Button v-if="$page.props.auth?.user && $page.props.auth.user.id === post.author?.id" as-child size="sm" variant="secondary">
                    <Link :href="route('posts.edit', post.slug)">Edit</Link>
                </Button>
            </div>
        </div>

        <div class="rounded border bg-card p-6">
            <!-- Regular text content -->
            <article>
                <div class="prose max-w-none prose-zinc dark:prose-invert" v-html="post.content"></div>
            </article>

            <!-- Mobile-optimized post metadata -->
            <div class="mt-6 space-y-4 text-sm text-muted-foreground">
                <!-- Author row -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span
                            >By
                            <Link :href="route('users.show', post.author?.username)" class="font-medium text-zinc-200 hover:text-primary">{{
                                post.author?.name ?? 'Unknown'
                            }}</Link></span
                        >
                    </div>
                </div>

                <!-- Tags row (if any) -->
                <div v-if="post.tags.length > 0" class="flex flex-wrap gap-2">
                    <div
                        v-for="tag in post.tags"
                        :key="tag.name"
                        :style="{ backgroundColor: tag.color + '20', borderColor: tag.color, color: tag.color }"
                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset"
                    >
                        {{ tag.name }}
                    </div>
                </div>

                <!-- Stats row - mobile optimized -->
                <div class="flex flex-wrap items-center gap-4 border-t border-border pt-4">
                    <div class="flex items-center gap-2">
                        <Eye class="h-4 w-4" />
                        <span class="text-xs sm:text-sm">{{ viewsCount }} {{ viewsCount === 1 ? 'view' : 'views' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <MessageSquareMoreIcon class="h-4 w-4" />
                        <span class="text-xs sm:text-sm">{{ commentsCount }} {{ commentsCount === 1 ? 'comment' : 'comments' }}</span>
                    </div>
                    <button
                        v-if="$page.props.auth?.user"
                        class="group flex items-center gap-2 transition-colors hover:cursor-pointer hover:text-primary"
                        @click="toggleLike"
                    >
                        <Heart
                            :class="[
                                'h-4 w-4 transition-all duration-200',
                                props.post.is_liked ? 'fill-red-500 text-red-500' : 'text-muted-foreground hover:text-red-500',
                            ]"
                        />
                        <span class="text-xs sm:text-sm">{{ likesCount }} {{ likesCount === 1 ? 'like' : 'likes' }}</span>
                    </button>
                    <div v-else class="flex items-center gap-2 text-muted-foreground">
                        <Heart class="h-4 w-4" />
                        <span class="text-xs sm:text-sm">{{ likesCount }} {{ likesCount === 1 ? 'like' : 'likes' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attachments Section -->
        <div v-if="post.attachments && post.attachments.length > 0" class="mt-10 rounded border bg-card p-6">
            <h2 class="mb-4 text-xl font-semibold">Attachments</h2>

            <div class="space-y-4">
                <div v-for="attachment in post.attachments" :key="attachment.id" class="flex items-start space-x-4 rounded border p-4">
                    <!-- Image Preview -->
                    <div v-if="isImage(attachment.mime_type)" class="flex-shrink-0">
                        <img
                            :alt="attachment.original_filename"
                            :src="attachment.url"
                            class="max-h-64 max-w-xs rounded border object-cover"
                            loading="lazy"
                        />
                    </div>

                    <!-- File Icon for Non-Images -->
                    <div v-else class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded border bg-muted">
                        <FileText class="h-8 w-8 text-muted-foreground" />
                    </div>

                    <!-- File Details -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="truncate font-medium text-foreground">{{ attachment.original_filename }}</h3>
                                <p class="mt-1 text-sm text-muted-foreground">{{ attachment.file_size }} • {{ attachment.mime_type }}</p>
                                <p v-if="attachment.download_count > 0" class="mt-1 text-xs text-muted-foreground">
                                    Downloaded {{ attachment.download_count }}
                                    {{ attachment.download_count === 1 ? 'time' : 'times' }}
                                </p>
                            </div>

                            <!-- Download Button -->
                            <!-- TODO: Downloading doesn't increment the count -->
                            <a
                                :download="attachment.original_filename"
                                :href="attachment.url"
                                class="flex items-center gap-2 rounded border px-3 py-2 text-sm transition-colors hover:bg-muted"
                                rel="noopener"
                                target="_blank"
                            >
                                <Download class="h-4 w-4" />
                                Download
                            </a>
                        </div>

                        <!-- Image Caption for Images (if needed later) -->
                        <div v-if="isImage(attachment.mime_type)" class="mt-3">
                            <!-- You can add image-specific actions or captions here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="mt-10 rounded border bg-card p-6">
            <h2 class="mb-4 text-xl font-semibold">Comments</h2>

            <div v-if="post.comments?.length" class="space-y-4">
                <CommentItem v-for="c in post.comments" :key="c.id" :comment="c" :post-slug="post.slug" />
            </div>
            <div v-else class="text-sm text-muted-foreground">No comments yet.</div>

            <!-- Add Comment (Auth only) -->
            <div v-if="$page.props.auth?.user" class="mt-6">
                <label class="mb-2 block text-sm font-medium">Add a comment</label>
                <textarea v-model="form.content" class="w-full rounded border bg-background p-3" placeholder="Write your comment..." rows="3" />
                <div class="mt-3">
                    <Button :disabled="form.processing || !(form.content && form.content.trim())" size="sm" @click="submitComment"
                        >Post Comment
                    </Button>
                </div>
            </div>
            <div v-else class="mt-6 text-sm text-muted-foreground">
                <Link :href="route('login')" class="text-primary underline-offset-4 hover:underline">Log in</Link>
                to post a comment.
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Post Content Prose Styling - matches TipTap editor */
:deep(.prose) {
    /* Reduce list spacing */
    ul, ol {
        margin-top: 0.75rem;
        margin-bottom: 0.75rem;
    }
    
    li {
        margin-top: 0;
        margin-bottom: 0.25rem;
    }
    
    li:last-child {
        margin-bottom: 0;
    }
    
    /* Remove paragraph margins inside list items */
    li p {
        margin-top: 0;
        margin-bottom: 0;
    }
    
    /* Nested lists */
    li > ul, li > ol {
        margin-top: 0.25rem;
        margin-bottom: 0.25rem;
    }
    
    /* Paragraph spacing */
    p {
        margin-top: 0;
        margin-bottom: 0.75rem;
    }
    
    p:last-child {
        margin-bottom: 0;
    }
    
    /* Code blocks */
    pre {
        margin-top: 1rem;
        margin-bottom: 1rem;
        padding: 1rem;
        border-radius: 0.5rem;
        background-color: #1f2937;
        overflow-x: auto;
    }
    
    /* Inline code */
    code {
        padding: 0.125rem 0.375rem;
        border-radius: 0.25rem;
        font-size: 0.875em;
        background-color: #374151;
    }
    
    /* Blockquotes */
    blockquote {
        margin-top: 1rem;
        margin-bottom: 1rem;
        padding-left: 1rem;
        border-left-width: 4px;
        border-left-color: #6b7280;
        font-style: italic;
        color: #9ca3af;
    }
    
    /* First element margin */
    > :first-child {
        margin-top: 0;
    }
    
    /* Last element margin */
    > :last-child {
        margin-bottom: 0;
    }
    
    /* Hard breaks for proper line spacing */
    br {
        display: block;
        margin: 0.25rem 0;
        content: "";
    }
    
    /* Remove excessive spacing from prose defaults */
    h1, h2, h3, h4, h5, h6 {
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }
    
    h1:first-child, h2:first-child, h3:first-child, 
    h4:first-child, h5:first-child, h6:first-child {
        margin-top: 0;
    }
}
</style>
