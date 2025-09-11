<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface RecentAnalysis {
    id: number;
    filename: string;
    size: string;
    format: string;
    entries: number;
    threats_detected: number;
    analyzed_at: string;
    status: string;
}

interface SupportedFormat {
    name: string;
    description: string;
    extensions: string[];
    sample: string;
}

interface AnalysisStats {
    total_logs_analyzed: number;
    total_entries_processed: number;
    threats_detected_total: number;
    most_common_threat: string;
    avg_processing_time: string;
}

defineProps<{
    recentAnalyses: RecentAnalysis[];
    supportedFormats: SupportedFormat[];
    analysisStats: AnalysisStats;
}>();

defineOptions({ layout: SiteLayout });

const page = usePage();
const flashMessage = computed(() => page.props.flash?.success);

const isDragOver = ref(false);
// const selectedFiles = ref<FileList | null>(null);

const form = useForm({
    files: [] as File[],
});

const isUploading = ref(false);
const uploadProgress = ref(0);

const handleDragOver = (e: DragEvent) => {
    e.preventDefault();
    isDragOver.value = true;
};

const handleDragLeave = (e: DragEvent) => {
    e.preventDefault();
    isDragOver.value = false;
};

const handleDrop = (e: DragEvent) => {
    e.preventDefault();
    isDragOver.value = false;
    if (e.dataTransfer?.files) {
        form.files = Array.from(e.dataTransfer.files);
    }
};

const handleFileSelect = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files) {
        form.files = Array.from(target.files);
    }
};

const formatTimestamp = (timestamp: string) => {
    return new Date(timestamp).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const getThreatBadgeColor = (count: number) => {
    if (count === 0) return 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300';
    if (count <= 3) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-300';
    if (count <= 10) return 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300';
    return 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300';
};

const uploadFiles = () => {
    if (!form.files || form.files.length === 0) return;

    form.post(route('tools.logs.upload'), {
        onProgress: (progress) => {
            uploadProgress.value = Math.round(progress?.percentage || 0);
        },
        onSuccess: () => {
            form.reset();
            // Page will redirect and refresh automatically
        },
        onError: (errors) => {
            console.error('Upload errors:', errors);
            alert('Upload failed. Please check your files and try again.');
        },
        onFinish: () => {
            isUploading.value = false;
            uploadProgress.value = 0;
        },
    });
};
</script>

<template>
    <Head title="Log Analysis Tools" />

    <div class="container mx-auto px-6 py-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold">Log Analysis Tools</h1>
            <p class="mt-2 text-muted-foreground">Upload and analyze security logs to detect threats, anomalies, and patterns.</p>
        </div>

        <!-- Success Message -->
        <div v-if="flashMessage" class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
            <div class="flex items-center gap-3">
                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500">
                    <span class="text-sm font-bold text-white">✓</span>
                </div>
                <p class="text-green-800 dark:text-green-200">{{ flashMessage }}</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
            <Card>
                <CardContent class="p-4">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ analysisStats.total_logs_analyzed }}
                    </div>
                    <div class="text-sm text-muted-foreground">Logs Analyzed</div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ analysisStats.total_entries_processed.toLocaleString() }}
                    </div>
                    <div class="text-sm text-muted-foreground">Log Entries</div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                        {{ analysisStats.threats_detected_total }}
                    </div>
                    <div class="text-sm text-muted-foreground">Threats Detected</div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="text-lg font-semibold text-orange-600 dark:text-orange-400">
                        {{ analysisStats.most_common_threat }}
                    </div>
                    <div class="text-sm text-muted-foreground">Top Threat</div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="text-2xl font-bold text-cyan-600 dark:text-cyan-400">
                        {{ analysisStats.avg_processing_time }}
                    </div>
                    <div class="text-sm text-muted-foreground">Avg. Time</div>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- File Upload Section -->
            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Upload Log Files</CardTitle>
                        <CardDescription>Drag and drop log files or click to browse</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="uploadFiles">
                            <div
                                :class="`rounded-lg border-2 border-dashed p-8 text-center transition-colors ${
                                    isDragOver
                                        ? 'border-purple-500 bg-blue-50 dark:bg-blue-950/20'
                                        : 'border-zinc-300 hover:border-zinc-400 dark:border-zinc-600 dark:hover:border-zinc-500'
                                }`"
                                @dragleave="handleDragLeave"
                                @dragover="handleDragOver"
                                @drop="handleDrop"
                            >
                                <input
                                    id="logFiles"
                                    accept=".log,.txt,.json,.jsonl,.evt,.evtx"
                                    class="hidden"
                                    multiple
                                    type="file"
                                    @change="handleFileSelect"
                                />
                                <label class="cursor-pointer" for="logFiles">
                                    <div class="mb-4 text-4xl">📄</div>
                                    <div class="mb-2 text-lg font-medium">Drop log files here or click to browse</div>
                                    <div class="text-sm text-muted-foreground">Supports: .log, .txt, .json, .jsonl, .evt, .evtx</div>
                                </label>
                            </div>
                        </form>

                        <div v-if="form.files && form.files.length > 0" class="mt-4">
                            <h4 class="mb-2 font-medium">Selected Files:</h4>
                            <ul class="space-y-1">
                                <li
                                    v-for="(file, index) in form.files"
                                    :key="index"
                                    class="flex items-center justify-between rounded bg-zinc-50 p-2 text-sm dark:bg-zinc-800"
                                >
                                    <span>{{ file.name }}</span>
                                    <span class="text-muted-foreground">{{ formatFileSize(file.size) }}</span>
                                </li>
                            </ul>
                            <div class="mt-4 flex gap-2">
                                <Button :disabled="isUploading" @click="uploadFiles">
                                    <span v-if="isUploading">Uploading... {{ uploadProgress }}%</span>
                                    <span v-else>Analyze Files</span>
                                </Button>
                                <Button as-child variant="ghost">
                                    <Link
                                        :href="route('tools.logs.parser')"
                                        class="rounded border border-zinc-300 px-4 py-2 transition-colors hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800"
                                    >
                                        Advanced Parser
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Supported Formats -->
                <Card>
                    <CardHeader>
                        <CardTitle>Supported Log Formats</CardTitle>
                        <CardDescription>Automatically detected formats and examples</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-for="format in supportedFormats"
                                :key="format.name"
                                class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700"
                            >
                                <div class="mb-2 flex items-start justify-between">
                                    <div>
                                        <h4 class="font-medium">{{ format.name }}</h4>
                                        <p class="text-sm text-muted-foreground">{{ format.description }}</p>
                                    </div>
                                    <div class="flex gap-1">
                                        <span
                                            v-for="ext in format.extensions"
                                            :key="ext"
                                            class="rounded bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-800"
                                        >
                                            {{ ext }}
                                        </span>
                                    </div>
                                </div>
                                <div class="rounded border-l-2 border-accent bg-zinc-50 p-2 font-mono text-xs dark:bg-zinc-900">
                                    {{ format.sample }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Analyses -->
            <div>
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Log Analyses</CardTitle>
                        <CardDescription>Previously analyzed log files and their results</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-for="analysis in recentAnalyses"
                                :key="analysis.id"
                                class="rounded-lg border border-zinc-200 p-4 transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/50"
                            >
                                <!-- Mobile-optimized header -->
                                <div class="mb-4 space-y-3">
                                    <div class="flex flex-col space-y-2 sm:flex-row sm:items-start sm:justify-between sm:space-y-0">
                                        <div class="min-w-0 flex-1">
                                            <h4 class="font-medium break-words">{{ analysis.filename }}</h4>
                                            <p class="text-sm text-muted-foreground">{{ analysis.format }} • {{ analysis.size }}</p>
                                        </div>
                                        <span
                                            :class="`flex-shrink-0 rounded px-2 py-1 text-xs font-medium ${getThreatBadgeColor(analysis.threats_detected)}`"
                                        >
                                            {{ analysis.threats_detected }} threats
                                        </span>
                                    </div>
                                </div>

                                <!-- Mobile-optimized stats -->
                                <div class="mb-4 grid grid-cols-1 gap-3 text-sm sm:mb-3 sm:grid-cols-2 sm:gap-4">
                                    <div class="flex justify-between sm:block">
                                        <span class="text-muted-foreground">Entries:</span>
                                        <span class="font-medium sm:ml-1">{{ analysis.entries.toLocaleString() }}</span>
                                    </div>
                                    <div class="flex justify-between sm:block">
                                        <span class="text-muted-foreground">Status:</span>
                                        <span class="font-medium capitalize sm:ml-1">{{ analysis.status }}</span>
                                    </div>
                                </div>

                                <!-- Mobile-optimized footer -->
                                <div class="flex flex-col space-y-2 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                                    <span class="text-xs text-muted-foreground"> Analyzed {{ formatTimestamp(analysis.analyzed_at) }} </span>
                                    <Link
                                        :href="route('tools.logs.show', analysis.id)"
                                        class="text-sm font-medium text-purple-500 hover:underline sm:text-xs dark:text-purple-400"
                                    >
                                        View Results →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8">
            <Card>
                <CardHeader>
                    <CardTitle>Quick Actions</CardTitle>
                    <CardDescription>Common log analysis tasks</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <button
                            class="rounded-lg border border-zinc-200 p-4 text-left transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
                        >
                            <div class="mb-2 text-lg">🔍</div>
                            <h4 class="font-medium">Failed Login Detection</h4>
                            <p class="text-sm text-muted-foreground">Identify suspicious authentication attempts</p>
                        </button>

                        <button
                            class="rounded-lg border border-zinc-200 p-4 text-left transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
                        >
                            <div class="mb-2 text-lg">⚡</div>
                            <h4 class="font-medium">Attack Pattern Analysis</h4>
                            <p class="text-sm text-muted-foreground">Detect common attack signatures and patterns</p>
                        </button>

                        <button
                            class="rounded-lg border border-zinc-200 p-4 text-left transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
                        >
                            <div class="mb-2 text-lg">📊</div>
                            <h4 class="font-medium">Traffic Analysis</h4>
                            <p class="text-sm text-muted-foreground">Analyze traffic patterns and anomalies</p>
                        </button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
