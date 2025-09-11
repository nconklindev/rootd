<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { parseDate } from '@internationalized/date';
import {
    AlertTriangle,
    ArrowLeft,
    Bug,
    Calendar,
    CheckCircle,
    Clock,
    Copy,
    ExternalLink,
    Hash,
    Shield,
    ShieldAlert,
    User,
    XCircle,
} from 'lucide-vue-next';

interface Vulnerability {
    id: number;
    title: string;
    cve_id?: string;
    description: string;
    severity: 'critical' | 'high' | 'medium' | 'low' | 'info';
    affected_product: string;
    affected_versions?: string;
    status: 'open' | 'in_progress' | 'resolved' | 'wont_fix' | 'duplicate';
    reporter_name?: string;
    reporter_email?: string;
    cvss_score?: number;
    remediation?: string;
    discovered_at?: string;
    disclosed_at?: string;
    resolved_at?: string;
    references?: string[];
    created_at: string;
    updated_at: string;
    user?: {
        id: number;
        name: string;
        username: string;
    };
}

interface CanPermissions {
    delete_vulnerability?: boolean;
    edit_vulnerability?: boolean;
    delete_this_vulnerability?: boolean;
}

const props = defineProps<{
    vulnerability: Vulnerability;
    can?: CanPermissions;
}>();
defineOptions({ layout: SiteLayout });

const formatDate = (dateString: string | null) => {
    if (!dateString) return 'Not specified';

    try {
        // Handle date-only strings correctly without timezone issues
        const date = parseDate(dateString);
        const nativeDate = new Date(date.year, date.month - 1, date.day);

        return nativeDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    } catch (error) {
        // Fallback for datetime strings
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;

        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    }
};

const getSeverityIcon = (severity: string) => {
    switch (severity) {
        case 'critical':
        case 'high':
            return ShieldAlert;
        case 'medium':
            return AlertTriangle;
        case 'low':
        case 'info':
            return Shield;
        default:
            return Shield;
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'resolved':
            return CheckCircle;
        case 'in_progress':
            return Clock;
        case 'wont_fix':
        case 'duplicate':
            return XCircle;
        case 'open':
        default:
            return Bug;
    }
};

const getSeverityColor = (severity: string) => {
    switch (severity) {
        case 'critical':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        case 'high':
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
        case 'medium':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        case 'low':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'info':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'resolved':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'in_progress':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        case 'open':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        case 'wont_fix':
        case 'duplicate':
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
};

const copyCveId = (cveId: string) => {
    navigator.clipboard.writeText(cveId);
};

const deleteVulnerability = (): void => {
    if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
        router.delete(route('vulnerabilities.destroy', props.vulnerability.id));
    }
};
</script>

<template>
    <Head :title="vulnerability.title" />
    <div class="container mx-auto min-w-0 px-6 py-10">
        <!-- Back Navigation -->
        <div class="mb-6">
            <Link
                :href="route('vulnerabilities.index')"
                class="inline-flex items-center space-x-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="h-4 w-4" />
                <span>Back to Vulnerability Database</span>
            </Link>
        </div>

        <!-- Vulnerability Header -->
        <div class="mb-8">
            <div class="flex flex-1 items-center justify-between space-y-2">
                <div class="flex flex-col">
                    <div class="mb-2 flex items-center space-x-3">
                        <component :is="getSeverityIcon(vulnerability.severity)" class="h-8 w-8 text-destructive" />
                        <h1 class="text-3xl font-bold tracking-tight">{{ vulnerability.title }}</h1>
                    </div>

                    <!-- CVE ID if available -->
                    <div v-if="vulnerability.cve_id" class="mb-4 flex items-center space-x-2">
                        <Badge class="font-mono" variant="secondary">
                            {{ vulnerability.cve_id }}
                        </Badge>
                        <Button class="h-6 w-6 p-0" size="sm" variant="ghost" @click="copyCveId(vulnerability.cve_id!)">
                            <Copy class="h-3 w-3" />
                        </Button>
                    </div>

                    <!-- Status and Severity Badges -->
                    <div class="flex items-center space-x-3">
                        <Badge :class="getSeverityColor(vulnerability.severity)" class="capitalize">
                            {{ vulnerability.severity }}
                            <span v-if="vulnerability.cvss_score" class="ml-1"> ({{ vulnerability.cvss_score }}) </span>
                        </Badge>
                        <Badge :class="getStatusColor(vulnerability.status)" class="capitalize">
                            <component :is="getStatusIcon(vulnerability.status)" class="mr-1 h-3 w-3" />
                            {{ vulnerability.status.replace('_', ' ') }}
                        </Badge>
                    </div>
                </div>
                <div class="space-x-4">
                    <Button
                        v-if="$page.props.auth?.user && $page.props.auth.user.id === vulnerability.user?.id"
                        as-child
                        size="sm"
                        variant="secondary"
                    >
                        <Link :href="route('vulnerabilities.edit', vulnerability.id)">Edit</Link>
                    </Button>
                    <Button
                        v-if="$page.props.auth?.user && $page.props.auth.user.id === vulnerability.user?.id"
                        size="sm"
                        variant="destructive"
                        @click="deleteVulnerability"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Main Content -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Description -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <Hash class="h-5 w-5" />
                            <span>Description</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="prose prose-sm max-w-none dark:prose-invert">
                            <p class="whitespace-pre-wrap">{{ vulnerability.description }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Affected Product & Versions -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <Bug class="h-5 w-5" />
                            <span>Affected Software</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Product</label>
                                <p class="mt-1 rounded bg-muted px-2 py-1 font-mono text-sm">
                                    {{ vulnerability.affected_product }}
                                </p>
                            </div>
                            <div v-if="vulnerability.affected_versions">
                                <label class="text-sm font-medium text-muted-foreground">Affected Versions</label>
                                <p class="mt-1 rounded bg-muted px-2 py-1 font-mono text-sm">
                                    {{ vulnerability.affected_versions }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Remediation -->
                <Card v-if="vulnerability.remediation">
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <CheckCircle class="h-5 w-5" />
                            <span>Remediation</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="prose prose-sm max-w-none dark:prose-invert">
                            <p class="whitespace-pre-wrap">{{ vulnerability.remediation }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- References -->
                <Card v-if="vulnerability.references && vulnerability.references.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <ExternalLink class="h-5 w-5" />
                            <span>References</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <a
                                v-for="(reference, index) in vulnerability.references"
                                :key="index"
                                :href="reference"
                                class="flex items-center space-x-2 text-sm break-all text-primary hover:underline"
                                rel="noopener noreferrer"
                                target="_blank"
                            >
                                <ExternalLink class="h-3 w-3 flex-shrink-0" />
                                <span>{{ reference }}</span>
                            </a>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Timeline -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <Calendar class="h-5 w-5" />
                            <span>Timeline</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="vulnerability.discovered_at">
                            <label class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Discovered</label>
                            <p class="text-sm">{{ formatDate(vulnerability.discovered_at) }}</p>
                        </div>

                        <div v-if="vulnerability.disclosed_at">
                            <label class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Disclosed</label>
                            <p class="text-sm">{{ formatDate(vulnerability.disclosed_at) }}</p>
                        </div>

                        <div v-if="vulnerability.resolved_at">
                            <label class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Resolved</label>
                            <p class="text-sm">{{ formatDate(vulnerability.resolved_at) }}</p>
                        </div>

                        <Separator />

                        <div>
                            <label class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Reported</label>
                            <p class="text-sm">{{ formatDate(vulnerability.created_at) }}</p>
                        </div>

                        <div>
                            <label class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Last Updated</label>
                            <p class="text-sm">{{ formatDate(vulnerability.updated_at) }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Reporter Information -->
                <Card v-if="vulnerability.reporter_name || vulnerability.user">
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <User class="h-5 w-5" />
                            <span>Reporter</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-if="vulnerability.reporter_name">
                            <label class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Name</label>
                            <p class="text-sm">{{ vulnerability.reporter_name }}</p>
                        </div>

                        <div v-if="vulnerability.reporter_email">
                            <label class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Email</label>
                            <p class="font-mono text-sm">{{ vulnerability.reporter_email }}</p>
                        </div>

                        <div v-if="vulnerability.user">
                            <label class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Submitted By</label>
                            <p class="text-sm">{{ vulnerability.user.name }} (@{{ vulnerability.user.username }})</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Technical Details -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <Shield class="h-5 w-5" />
                            <span>Technical Details</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Vulnerability ID</label>
                            <p class="font-mono text-sm">{{ vulnerability.id }}</p>
                        </div>

                        <div v-if="vulnerability.cvss_score">
                            <label class="text-xs font-medium tracking-wide text-muted-foreground uppercase">CVSS Score</label>
                            <p class="font-mono text-sm">{{ vulnerability.cvss_score }}/10</p>
                        </div>

                        <div class="flex items-center space-x-2">
                            <label class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Severity Level</label>
                            <Badge :class="getSeverityColor(vulnerability.severity)" class="text-xs capitalize">
                                {{ vulnerability.severity }}
                            </Badge>
                        </div>

                        <div class="flex items-center space-x-2">
                            <label class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Status</label>
                            <Badge :class="getStatusColor(vulnerability.status)" class="text-xs capitalize">
                                <component :is="getStatusIcon(vulnerability.status)" class="mr-1 h-3 w-3" />
                                {{ vulnerability.status.replace('_', ' ') }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
