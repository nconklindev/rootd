<script lang="ts" setup>
import hljs from 'highlight.js/lib/core';
import 'highlight.js/styles/tokyo-night-dark.css';
import { computed, ref, watchEffect } from 'vue';

// Import only the languages you need to reduce bundle size
import bash from 'highlight.js/lib/languages/bash';
import c from 'highlight.js/lib/languages/c';
import csharp from 'highlight.js/lib/languages/csharp';
import css from 'highlight.js/lib/languages/css';
import go from 'highlight.js/lib/languages/go';
import java from 'highlight.js/lib/languages/java';
import javascript from 'highlight.js/lib/languages/javascript';
import json from 'highlight.js/lib/languages/json';
import php from 'highlight.js/lib/languages/php';
import python from 'highlight.js/lib/languages/python';
import rust from 'highlight.js/lib/languages/rust';
import sql from 'highlight.js/lib/languages/sql';
import typescript from 'highlight.js/lib/languages/typescript';
import html from 'highlight.js/lib/languages/xml';

// Register languages
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('typescript', typescript);
hljs.registerLanguage('python', python);
hljs.registerLanguage('php', php);
hljs.registerLanguage('sql', sql);
hljs.registerLanguage('bash', bash);
hljs.registerLanguage('css', css);
hljs.registerLanguage('html', html);
hljs.registerLanguage('xml', html);
hljs.registerLanguage('json', json);
hljs.registerLanguage('rust', rust);
hljs.registerLanguage('go', go);
hljs.registerLanguage('java', java);
hljs.registerLanguage('csharp', csharp);
hljs.registerLanguage('c', c);

// Language display mappings for better UX
const languageDisplayMap: Record<string, string> = {
    javascript: 'JavaScript',
    typescript: 'TypeScript',
    python: 'Python',
    php: 'PHP',
    sql: 'SQL',
    bash: 'Bash',
    css: 'CSS',
    html: 'HTML',
    xml: 'XML',
    json: 'JSON',
    rust: 'Rust',
    go: 'Go',
    java: 'Java',
    csharp: 'C#',
    c: 'C',
    text: 'Plain Text',
    plaintext: 'Plain Text',
};

interface Props {
    code: string;
    language?: string;
    autoDetect?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    language: undefined,
    autoDetect: true,
});

const detectedLanguage = ref<string>('');
const highlightedCode = ref<string>('');

// Normalize language input to handle case variations
function normalizeLanguage(lang: string): string {
    const normalized = lang.toLowerCase();
    // Handle common variations
    const variations: Record<string, string> = {
        js: 'javascript',
        ts: 'typescript',
        py: 'python',
        sh: 'bash',
        shell: 'bash',
        'c++': 'cpp',
        cpp: 'c',
        'c#': 'csharp',
        htm: 'html',
    };
    return variations[normalized] || normalized;
}

// Smart language detection and highlighting
watchEffect(() => {
    if (!props.code) {
        highlightedCode.value = '';
        detectedLanguage.value = '';
        return;
    }

    try {
        let result;

        if (props.language && props.language !== 'text') {
            // User specified a language, try to use it
            const normalizedLang = normalizeLanguage(props.language);
            if (hljs.getLanguage(normalizedLang)) {
                result = hljs.highlight(props.code, { language: normalizedLang });
                detectedLanguage.value = normalizedLang;
            } else {
                // Language not available, fall back to auto-detection
                result = hljs.highlightAuto(props.code);
                detectedLanguage.value = result.language || 'text';
            }
        } else {
            // Auto-detect language
            result = hljs.highlightAuto(props.code);
            detectedLanguage.value = result.language || 'text';
        }

        highlightedCode.value = result.value;
    } catch (error) {
        console.warn('Syntax highlighting failed:', error);
        highlightedCode.value = props.code;
        detectedLanguage.value = 'text';
    }
});

const displayLanguage = computed(() => {
    const lang = props.language || detectedLanguage.value || 'text';
    const normalizedLang = normalizeLanguage(lang);
    return languageDisplayMap[normalizedLang] || normalizedLang.charAt(0).toUpperCase() + normalizedLang.slice(1);
});

const copyToClipboard = async () => {
    try {
        await navigator.clipboard.writeText(props.code);
    } catch (error) {
        console.warn('Failed to copy to clipboard:', error);
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = props.code;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
    }
};
</script>

<template>
    <div class="relative rounded-lg border bg-gray-900 dark:bg-gray-950">
        <!-- Language indicator -->
        <div class="flex items-center justify-between border-b border-gray-700 px-4 py-2">
            <span class="text-sm font-medium text-gray-300">{{ displayLanguage }}</span>
            <button class="text-sm text-gray-400 transition-colors hover:text-gray-200" title="Copy to clipboard" @click="copyToClipboard">
                Copy
            </button>
        </div>

        <!-- Code content -->
        <div class="overflow-x-auto">
            <pre class="p-4 text-sm"><code
                :class="`language-${detectedLanguage || 'text'}`"
                class="text-gray-100"
                v-html="highlightedCode"
            /></pre>
        </div>
    </div>
</template>

<style scoped>
/* Override highlight.js styles for better integration */
pre {
    margin: 0;
    background: transparent !important;
}

code {
    background: transparent !important;
    font-family: 'JetBrains Mono', 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    line-height: 1.5;
}

/* Ensure proper scrolling */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: rgb(31 41 55);
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: rgb(75 85 99);
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: rgb(107 114 128);
}
</style>
