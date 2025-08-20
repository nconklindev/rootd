<script lang="ts" setup>
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import { User } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { Heart } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

// Define interface for the comment model
interface CommentModel {
    id: number;
    content: string;
    created_at: string | Date;
    created_at_human: string;
    user: User | null;
    likes_count?: number;
    is_liked?: boolean;
    parent_id?: number | null;
    children?: CommentModel[];
}

const props = withDefaults(
    defineProps<{
        comment: CommentModel;
        postSlug: string;
        depth?: number;
    }>(),
    { depth: 0 },
);

defineOptions({ name: 'CommentItem' });

const showReply = ref(false);

const form = useForm({
    content: '',
    parent_id: props.comment.id,
});

const { getInitials } = useInitials();

// Compute whether we should show the avatar image (based on the comment author's avatar)
const showAvatar = computed(() => !!(props.comment.user?.avatar && props.comment.user.avatar !== ''));

watch(
    () => props.comment.id,
    (id) => {
        form.parent_id = id;
    },
);

const submit = (): void => {
    form.post(route('posts.comments.store', { post: props.postSlug }), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('content');
            showReply.value = false;
        },
    });
};

const toggleLike = (): void => {
    if (props.comment.is_liked) {
        // Unlike the comment
        router.delete(route('comments.unlike', { comment: props.comment.id }), {
            preserveScroll: true,
        });
    } else {
        // Like the comment
        router.post(
            route('comments.like', { comment: props.comment.id }),
            {},
            {
                preserveScroll: true,
            },
        );
    }
};
</script>

<template>
    <div>
        <div class="group rounded-md transition-shadow duration-200 focus-within:ring-1 focus-within:ring-primary/40">
            <div class="p-4">
                <div class="mb-1 flex flex-wrap items-center gap-x-2.5 text-sm text-muted-foreground">
                    <Avatar class="h-8 w-8 overflow-hidden rounded-full">
                        <AvatarImage v-if="showAvatar" :alt="comment.user?.name ?? 'Unknown'" :src="comment.user?.avatar!" />
                        <AvatarFallback class="rounded-lg bg-accent/75 text-black dark:text-white">
                            {{ getInitials(comment.user?.name ?? 'Unknown') }}
                        </AvatarFallback>
                    </Avatar>
                    <span class="font-semibold text-foreground">{{ comment.user?.name ?? 'Unknown' }}</span>
                    <span class="opacity-60">•</span>
                    <span class="font-light">{{ comment.created_at_human }}</span>
                </div>

                <div class="mt-2.5 whitespace-pre-wrap text-foreground">
                    {{ comment.content }}
                </div>

                <!-- Like & Reply buttons -->
                <div class="mt-4 flex items-center space-x-4">
                    <div v-if="$page.props.auth.user && comment.user?.id !== $page.props.auth.user.id" class="flex flex-row items-center gap-2">
                        <Heart
                            :class="[
                                'size-5 cursor-pointer transition-all duration-200',
                                comment.is_liked ? 'fill-red-500 text-red-500' : 'text-zinc-300 hover:fill-red-500 hover:text-red-500',
                            ]"
                            @click="toggleLike"
                        />
                        {{ comment.likes_count ?? 0 }}
                    </div>
                    <div v-else-if="!$page.props.auth.user" class="flex flex-row items-center gap-2">
                        <Heart class="size-5 text-zinc-300" />
                        {{ comment.likes_count ?? 0 }}
                    </div>

                    <div
                        v-if="$page.props.auth.user"
                        :class="{ 'opacity-100': showReply }"
                        class="flex items-center gap-3 opacity-0 transition-opacity duration-200 group-hover:opacity-100 focus-within:opacity-100"
                    >
                        <Button class="cursor-pointer" size="sm" variant="secondary" @click="showReply = !showReply">
                            {{ showReply ? 'Cancel' : 'Reply' }}
                        </Button>
                    </div>
                </div>

                <form v-if="showReply" class="mt-3" @submit.prevent="submit">
                    <textarea v-model="form.content" class="w-full rounded border bg-background p-2" placeholder="Write a reply..." rows="2" />
                    <div class="mt-2">
                        <Button :disabled="form.processing || !(form.content && form.content.trim())" size="sm" type="submit"> Post Reply </Button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="comment.children?.length" class="mt-4 space-y-4 border-l-2 border-accent-foreground/25 pl-4 md:pl-4">
            <CommentItem v-for="child in comment.children" :key="child.id" :comment="child" :depth="props.depth + 1" :post-slug="postSlug" />
        </div>
    </div>
</template>
