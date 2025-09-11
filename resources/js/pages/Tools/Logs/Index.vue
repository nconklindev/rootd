<script lang="ts" setup>
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';

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
const selectedFiles = ref<FileList | null>(null);
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
        selectedFiles.value = e.dataTransfer.files;
    }
};

const handleFileSelect = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files) {
        selectedFiles.value = target.files;
    }
};

const formatTimestamp = (timestamp: string) => {
    return new Date(timestamp).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
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
    if (!selectedFiles.value || selectedFiles.value.length === 0) return;

    isUploading.value = true;

    const formData = new FormData();
    for (let i = 0; i < selectedFiles.value.length; i++) {
        formData.append('files[]', selectedFiles.value[i]);
    }

    router.post(route('tools.logs.upload'), formData, {
        onProgress: (progress) => {
            uploadProgress.value = Math.round(progress.percentage || 0);
        },
        onSuccess: () => {
            selectedFiles.value = null;
            // Page will redirect and refresh automatically
        },
        onError: (errors) => {
            console.error('Upload errors:', errors);
            alert('Upload failed. Please check your files and try again.');
        },
        onFinish: () => {
            isUploading.value = false;
            uploadProgress.value = 0;
        }
    });
};
</script>

<template>
    <Head title="Log Analysis Tools" />

    <div class="container mx-auto px-6 py-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold">Log Analysis Tools</h1>
            <p class="text-muted-foreground mt-2">
                Upload and analyze security logs to detect threats, anomalies, and patterns. All processing happens
                locally - your logs never leave your browser.
            </p>
        </div>

        <!-- Success Message -->
        <div v-if="flashMessage"
             class="mb-6 bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                    <span class="text-white text-sm font-bold">✓</span>
                </div>
                <p class="text-green-800 dark:text-green-200">{{ flashMessage }}</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
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
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                        {{ analysisStats.avg_processing_time }}
                    </div>
                    <div class="text-sm text-muted-foreground">Avg. Time</div>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- File Upload Section -->
            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Upload Log Files</CardTitle>
                        <CardDescription>Drag and drop log files or click to browse</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div
                            :class="`border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
                                isDragOver
                                    ? 'border-blue-400 bg-blue-50 dark:bg-blue-950/20'
                                    : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'
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
                            >
                            <label class="cursor-pointer" for="logFiles">
                                <div class="text-4xl mb-4">📄</div>
                                <div class="text-lg font-medium mb-2">Drop log files here or click to browse</div>
                                <div class="text-sm text-muted-foreground">
                                    Supports: .log, .txt, .json, .jsonl, .evt, .evtx
                                </div>
                            </label>
                        </div>

                        <div v-if="selectedFiles && selectedFiles.length > 0" class="mt-4">
                            <h4 class="font-medium mb-2">Selected Files:</h4>
                            <ul class="space-y-1">
                                <li v-for="(file, index) in Array.from(selectedFiles)" :key="index"
                                    class="text-sm flex justify-between items-center bg-gray-50 dark:bg-gray-800 p-2 rounded">
                                    <span>{{ file.name }}</span>
                                    <span class="text-muted-foreground">{{ formatFileSize(file.size) }}</span>
                                </li>
                            </ul>
                            <div class="mt-4 flex gap-2">
                                <Button
                                    :disabled="isUploading"
                                    @click="uploadFiles">
                                    <span v-if="isUploading">Uploading... {{ uploadProgress }}%</span>
                                    <span v-else>Analyze Files</span>
                                </Button>
                                <Button as-child variant="ghost">
                                    <Link :href="route('tools.logs.parser')"
                                          class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
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
                            <div v-for="format in supportedFormats" :key="format.name"
                                 class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h4 class="font-medium">{{ format.name }}</h4>
                                        <p class="text-sm text-muted-foreground">{{ format.description }}</p>
                                    </div>
                                    <div class="flex gap-1">
                                        <span v-for="ext in format.extensions" :key="ext"
                                              class="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">
                                            {{ ext }}
                                        </span>
                                    </div>
                                </div>
                                <div
                                    class="text-xs font-mono bg-gray-50 dark:bg-gray-900 p-2 rounded border-l-2 border-blue-400">
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
                            <div v-for="analysis in recentAnalyses" :key="analysis.id"
                                 class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <!-- Mobile-optimized header -->
                                <div class="space-y-3 mb-4">
                                    <div class="flex flex-col space-y-2 sm:flex-row sm:justify-between sm:items-start sm:space-y-0">
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-medium break-words">{{ analysis.filename }}</h4>
                                            <p class="text-sm text-muted-foreground">{{ analysis.format }} • {{ analysis.size }}</p>
                                        </div>
                                        <span
                                            :class="`px-2 py-1 rounded text-xs font-medium flex-shrink-0 ${getThreatBadgeColor(analysis.threats_detected)}`">
                                            {{ analysis.threats_detected }} threats
                                        </span>
                                    </div>
                                </div>

                                <!-- Mobile-optimized stats -->
                                <div class="grid grid-cols-1 gap-3 text-sm mb-4 sm:grid-cols-2 sm:gap-4 sm:mb-3">
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
                                <div class="flex flex-col space-y-2 sm:flex-row sm:justify-between sm:items-center sm:space-y-0">
                                    <span class="text-xs text-muted-foreground">
                                        Analyzed {{ formatTimestamp(analysis.analyzed_at) }}
                                    </span>
                                    <Link :href="route('tools.logs.show', analysis.id)" 
                                          class="text-sm text-blue-600 dark:text-blue-400 hover:underline font-medium sm:text-xs">
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
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button
                            class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors text-left">
                            <div class="text-lg mb-2">🔍</div>
                            <h4 class="font-medium">Failed Login Detection</h4>
                            <p class="text-sm text-muted-foreground">Identify suspicious authentication attempts</p>
                        </button>

                        <button
                            class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors text-left">
                            <div class="text-lg mb-2">⚡</div>
                            <h4 class="font-medium">Attack Pattern Analysis</h4>
                            <p class="text-sm text-muted-foreground">Detect common attack signatures and patterns</p>
                        </button>

                        <button
                            class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors text-left">
                            <div class="text-lg mb-2">📊</div>
                            <h4 class="font-medium">Traffic Analysis</h4>
                            <p class="text-sm text-muted-foreground">Analyze traffic patterns and anomalies</p>
                        </button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
