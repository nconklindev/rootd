<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface ThreatDetection {
    id: number;
    threat_type: string;
    severity: string;
    description: string;
    line_number: number;
    source_ip: string;
    confidence_score: number;
    timestamp_detected: string;
    metadata: any;
}

interface LogAnalysis {
    id: number;
    original_filename: string;
    formatted_file_size: string;
    log_format: string;
    total_entries: number;
    threats_detected: number;
    processing_status: string;
    progress_percentage: number;
    entries_processed: number;
    processed_at: string;
    processing_duration_seconds: number;
    created_at: string;
    threat_detections: ThreatDetection[];
}

interface ThreatSummary {
    by_severity: Array<{ severity: string; count: number }>;
    by_type: Array<{ threat_type: string; count: number }>;
}

const props = defineProps<{
    analysis: LogAnalysis;
    threatsSummary: ThreatSummary;
}>();

defineOptions({ layout: SiteLayout });

const formatTimestamp = (timestamp: string) => {
    return new Date(timestamp).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

const getSeverityColor = (severity: string) => {
    switch (severity) {
        case 'critical':
            return 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300';
        case 'high':
            return 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300';
        case 'medium':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-300';
        case 'low':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-950 dark:text-gray-300';
    }
};

const formatThreatType = (threatType: string) => {
    return threatType
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};

const formatProcessingTime = computed(() => {
    const seconds = props.analysis.processing_duration_seconds;
    if (seconds < 1) {
        return `${Math.round(seconds * 1000)} ms`;
    } else if (seconds < 60) {
        return `${seconds.toFixed(2)} seconds`;
    } else if (seconds < 3600) {
        return `${(seconds / 60).toFixed(1)} minutes`;
    } else {
        return `${(seconds / 3600).toFixed(1)} hours`;
    }
});
</script>

<template>
    <Head :title="`Log Analysis: ${analysis.original_filename}`" />

    <div class="container mx-auto px-6 py-10">
        <!-- Header -->
        <div class="mb-8">
            <div class="mb-4 flex items-center gap-4">
                <Button as-child size="sm" variant="outline">
                    <Link :href="route('tools.logs.index')" class="flex items-center gap-2"> ← Back to Log Analysis </Link>
                </Button>
            </div>

            <h1 class="text-2xl font-bold break-words md:text-3xl lg:text-4xl">{{ analysis.original_filename }}</h1>
            <p class="mt-2 text-muted-foreground">Detailed security analysis results and threat detections</p>
        </div>

        <!-- Analysis Overview -->
        <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardContent class="p-4">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ analysis.total_entries.toLocaleString() }}
                    </div>
                    <div class="text-sm text-muted-foreground">Log Entries</div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-4">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                        {{ analysis.threats_detected }}
                    </div>
                    <div class="text-sm text-muted-foreground">Threats Detected</div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-4">
                    <div class="text-lg font-semibold text-green-600 dark:text-green-400">
                        {{ analysis.formatted_file_size }}
                    </div>
                    <div class="text-sm text-muted-foreground">File Size</div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-4">
                    <div class="text-lg font-semibold text-purple-600 dark:text-purple-400">
                        {{ formatProcessingTime }}
                    </div>
                    <div class="text-sm text-muted-foreground">Processing Time</div>
                </CardContent>
            </Card>
        </div>

        <!-- Analysis Details and Threat Summary -->
        <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- File Details -->
            <Card>
                <CardHeader>
                    <CardTitle>File Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Format:</span>
                        <span class="font-medium">{{ analysis.log_format }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Status:</span>
                        <span class="font-medium capitalize">{{ analysis.processing_status }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Processed:</span>
                        <span class="font-medium">{{ formatTimestamp(analysis.processed_at) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Progress:</span>
                        <span class="font-medium">{{ analysis.progress_percentage }}%</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Threats by Severity -->
            <Card>
                <CardHeader>
                    <CardTitle>Threats by Severity</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div v-for="severity in threatsSummary.by_severity" :key="severity.severity" class="flex items-center justify-between">
                            <span :class="`rounded px-2 py-1 text-sm font-medium capitalize ${getSeverityColor(severity.severity)}`">
                                {{ severity.severity }}
                            </span>
                            <span class="font-medium">{{ severity.count }}</span>
                        </div>
                        <div v-if="threatsSummary.by_severity.length === 0" class="py-4 text-center text-muted-foreground">No threats detected</div>
                    </div>
                </CardContent>
            </Card>

            <!-- Threats by Type -->
            <Card>
                <CardHeader>
                    <CardTitle>Top Threat Types</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div v-for="type in threatsSummary.by_type" :key="type.threat_type" class="flex items-center justify-between">
                            <span class="text-sm font-medium">{{ formatThreatType(type.threat_type) }}</span>
                            <span class="font-medium">{{ type.count }}</span>
                        </div>
                        <div v-if="threatsSummary.by_type.length === 0" class="py-4 text-center text-muted-foreground">No threats detected</div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Detailed Threat Detections -->
        <Card>
            <CardHeader>
                <CardTitle>Threat Detections</CardTitle>
                <CardDescription> All security threats and anomalies detected in this log file </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <div
                        v-for="threat in analysis.threat_detections"
                        :key="threat.id"
                        class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                    >
                        <div class="mb-3 flex items-start justify-between">
                            <div>
                                <h4 class="font-medium">{{ formatThreatType(threat.threat_type) }}</h4>
                                <p class="text-sm text-muted-foreground">{{ threat.description }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span :class="`rounded px-2 py-1 text-xs font-medium ${getSeverityColor(threat.severity)}`">
                                    {{ threat.severity }}
                                </span>
                                <span class="rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800">
                                    {{ threat.confidence_score }}% confidence
                                </span>
                            </div>
                        </div>

                        <div class="mb-3 grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                            <div>
                                <span class="text-muted-foreground">Source IP:</span>
                                <span class="ml-1 font-medium">{{ threat.source_ip }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Line:</span>
                                <span class="ml-1 font-medium">{{ threat.line_number }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Detected:</span>
                                <span class="ml-1 font-medium">{{ formatTimestamp(threat.timestamp_detected) }}</span>
                            </div>
                        </div>

                        <!-- Metadata -->
                        <div v-if="threat.metadata" class="mt-3">
                            <details class="group">
                                <summary class="cursor-pointer text-sm text-accent hover:underline dark:text-purple-400">View Details</summary>
                                <div class="mt-2 rounded border-l-2 border-blue-400 bg-gray-50 p-3 dark:bg-gray-900">
                                    <pre class="overflow-x-auto text-xs">{{ JSON.stringify(threat.metadata, null, 2) }}</pre>
                                </div>
                            </details>
                        </div>
                    </div>

                    <div v-if="analysis.threat_detections.length === 0" class="py-12 text-center">
                        <div class="mb-4 text-6xl">🛡️</div>
                        <h3 class="mb-2 text-lg font-medium">No Threats Detected</h3>
                        <p class="text-muted-foreground">This log file appears to be clean with no suspicious activities detected.</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
