<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight';
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
        StarterKit.configure({
            // Configure code blocks
            codeBlock: false, // We'll use CodeBlockLowlight instead
            bulletList: {
                keepMarks: true,
            },
        }),
        Color,
        TextStyle,
        CodeBlockLowlight.configure({
            lowlight,
            enableTabIndentation: true,
        }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-zinc dark:prose-invert m-5 focus:outline-none focus:outline-none max-w-none min-h-[120px]',
        },
        handleKeyDown(view, event) {
            if (!editor.value) return false;

            const { state } = editor.value;
            const { selection } = state;
            const { $from } = selection;

            // Handle Enter key
            if (event.key === 'Enter') {
                // Check if we're inside a code block (anywhere in the ancestry)
                for (let i = $from.depth; i >= 0; i--) {
                    if ($from.node(i).type.name === 'codeBlock') {
                        return false; // Let default behavior handle code blocks
                    }
                }

                // Check if we're inside a list item (anywhere in the ancestry)
                for (let i = $from.depth; i >= 0; i--) {
                    if ($from.node(i).type.name === 'listItem') {
                        if (event.shiftKey) {
                            // Shift+Enter in lists = hard break within the list item
                            editor.value.commands.setHardBreak();
                            return true;
                        } else {
                            // Regular Enter in lists = let default behavior (new list item)
                            return false;
                        }
                    }
                }

                // Check if we're inside a blockquote (anywhere in the ancestry)
                for (let i = $from.depth; i >= 0; i--) {
                    if ($from.node(i).type.name === 'blockquote') {
                        return false; // Let default behavior handle blockquotes
                    }
                }

                // In regular paragraphs: Enter = hard break (unless Shift+Enter)
                if ($from.parent.type.name === 'paragraph') {
                    if (!event.shiftKey) {
                        editor.value.commands.setHardBreak();
                        return true;
                    }
                }
            }

            return false;
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
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
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
                    </TooltipTrigger>
                    <TooltipContent avoid-collisions>
                        <kbd><kbd>Ctrl</kbd>+<kbd>B</kbd></kbd>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
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
                    </TooltipTrigger>
                    <TooltipContent avoid-collisions>
                        <kbd><kbd>Ctrl</kbd>+<kbd>I</kbd></kbd>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
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
                    </TooltipTrigger>
                    <TooltipContent avoid-collisions>
                        <kbd><kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>S</kbd></kbd>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <!-- Separator -->
            <div class="mx-1 h-6 w-px bg-border"></div>

            <!-- Lists -->
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
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
                    </TooltipTrigger>
                    <TooltipContent avoid-collisions>
                        <kbd><kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>8</kbd></kbd>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
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
                    </TooltipTrigger>
                    <TooltipContent avoid-collisions>
                        <kbd><kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>7</kbd></kbd>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <!-- Quote -->
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
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
                    </TooltipTrigger>
                    <TooltipContent avoid-collisions>
                        <kbd><kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>B</kbd></kbd>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            :class="toolbarButtonClass"
                            :data-active="editor.isActive('codeBlock')"
                            size="icon"
                            type="button"
                            variant="ghost"
                            @click="editor.chain().focus().toggleCodeBlock().run()"
                            ><Code
                        /></Button>
                    </TooltipTrigger>
                    <TooltipContent avoid-collisions>
                        <kbd><kbd>Ctrl</kbd>+<kbd>Alt</kbd>+<kbd>C</kbd></kbd>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <!-- Separator -->
            <div class="mx-1 h-6 w-px bg-border"></div>

            <!-- Undo/Redo -->
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
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
                    </TooltipTrigger>
                    <TooltipContent avoid-collisions>
                        <kbd><kbd>Ctrl</kbd>+<kbd>Z</kbd></kbd>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
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
                    </TooltipTrigger>
                    <TooltipContent avoid-collisions>
                        <kbd><kbd>Ctrl</kbd>+<kbd>Y</kbd></kbd>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </div>

        <!-- Editor Content -->
        <EditorContent :editor="editor" class="min-h-[120px]" />
    </div>
</template>

<style scoped>
/* TipTap Editor Prose Styling */
:deep(.prose) {
    /* Reduce list spacing */

    ul,
    ol {
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

    li > ul,
    li > ol {
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
    }

    /* Inline code */

    code {
        padding: 0.125rem 0.375rem;
        border-radius: 0.25rem;
        font-size: 0.875em;
    }

    /* Blockquotes */

    blockquote {
        margin-top: 1rem;
        margin-bottom: 1rem;
        padding-left: 1rem;
        border-left-width: 4px;
    }

    /* First element margin */

    > :first-child {
        margin-top: 0;
    }

    /* Last element margin */

    > :last-child {
        margin-bottom: 0;
    }

    /* Hard breaks */

    br {
        display: block;
        margin: 0.25rem 0;
        content: '';
    }
}
</style>
