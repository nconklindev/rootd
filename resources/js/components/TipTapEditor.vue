<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight';
import Document from '@tiptap/extension-document';
import { BulletList, ListItem } from '@tiptap/extension-list';
import Paragraph from '@tiptap/extension-paragraph';
import Text from '@tiptap/extension-text';
import { Color, TextStyle } from '@tiptap/extension-text-style';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import 'highlight.js/styles/tokyo-night-dark.css';
import { all, createLowlight } from 'lowlight';
import { Bold, Code, Italic, List, ListOrdered, Quote, Redo, Strikethrough, Undo } from 'lucide-vue-next';
import { watch } from 'vue';

interface Props {
    modelValue: string;
    placeholder?: string;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Write your content here...',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const lowlight = createLowlight(all);

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        Paragraph,
        Color,
        TextStyle,
        Document,
        Text,
        BulletList,
        ListItem,
        StarterKit,
        CodeBlockLowlight.configure({
            lowlight,
        }),
    ],
    editorProps: {
        attributes: {
            class: 'focus:outline-none max-w-none p-3 min-h-[120px]',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

// Watch for external changes to modelValue
watch(
    () => props.modelValue,
    (newValue) => {
        if (editor.value && editor.value.getHTML() !== newValue) {
            editor.value.commands.setContent(newValue);
        }
    },
);

// Define toolbar button style
const toolbarButtonClass =
    'h-8 w-8 p-0 hover:bg-accent hover:text-accent-foreground data-[active=true]:bg-accent data-[active=true]:text-accent-foreground';
</script>

<template>
    <div :class="cn('rounded-md border bg-background', props.class)">
        <!-- Toolbar -->
        <div v-if="editor" class="flex flex-wrap items-center gap-1 border-b p-2">
            <!-- Text formatting -->
            <Button
                :class="toolbarButtonClass"
                :data-active="editor.isActive('bold')"
                size="icon"
                type="button"
                variant="ghost"
                @click="editor.chain().focus().toggleBold().run()"
            >
                <Bold class="h-4 w-4" />
            </Button>

            <Button
                :class="toolbarButtonClass"
                :data-active="editor.isActive('italic')"
                size="icon"
                type="button"
                variant="ghost"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <Italic class="h-4 w-4" />
            </Button>

            <Button
                :class="toolbarButtonClass"
                :data-active="editor.isActive('strike')"
                size="icon"
                type="button"
                variant="ghost"
                @click="editor.chain().focus().toggleStrike().run()"
            >
                <Strikethrough class="h-4 w-4" />
            </Button>

            <!-- Separator -->
            <div class="mx-1 h-6 w-px bg-border"></div>

            <!-- Lists -->
            <Button
                :class="toolbarButtonClass"
                :data-active="editor.isActive('bulletList')"
                size="icon"
                type="button"
                variant="ghost"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <List class="h-4 w-4" />
            </Button>

            <Button
                :class="toolbarButtonClass"
                :data-active="editor.isActive('orderedList')"
                size="icon"
                type="button"
                variant="ghost"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="h-4 w-4" />
            </Button>

            <!-- Quote -->
            <Button
                :class="toolbarButtonClass"
                :data-active="editor.isActive('blockquote')"
                size="icon"
                type="button"
                variant="ghost"
                @click="editor.chain().focus().toggleBlockquote().run()"
            >
                <Quote class="h-4 w-4" />
            </Button>

            <Button
                :class="toolbarButtonClass"
                :data-active="editor.isActive('codeBlock')"
                size="icon"
                type="button"
                variant="ghost"
                @click="editor.chain().focus().toggleCodeBlock().run()"
                ><Code
            /></Button>

            <!-- Separator -->
            <div class="mx-1 h-6 w-px bg-border"></div>

            <!-- Undo/Redo -->
            <Button
                :class="toolbarButtonClass"
                :disabled="!editor.can().undo()"
                size="icon"
                type="button"
                variant="ghost"
                @click="editor.chain().focus().undo().run()"
            >
                <Undo class="h-4 w-4" />
            </Button>

            <Button
                :class="toolbarButtonClass"
                :disabled="!editor.can().redo()"
                size="icon"
                type="button"
                variant="ghost"
                @click="editor.chain().focus().redo().run()"
            >
                <Redo class="h-4 w-4" />
            </Button>
        </div>

        <!-- Editor Content -->
        <EditorContent :editor="editor" class="min-h-[120px] [&_.ProseMirror]:min-h-[120px] [&_.ProseMirror]:outline-none" />
    </div>
</template>

<style scoped>
/* Additional TipTap-specific styles if needed */
:deep(.ProseMirror p.is-editor-empty:first-child::before) {
    color: #adb5bd;
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}
</style>
