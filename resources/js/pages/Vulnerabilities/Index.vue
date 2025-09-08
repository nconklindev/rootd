<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationFirst,
    PaginationItem,
    PaginationLast,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, Calendar, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, Clock, Plus, Search, Shield, User } from 'lucide-vue-next';
import { ref, watch } from 'vue';

defineOptions({ layout: SiteLayout });

const props = defineProps<{
    vulnerabilities: any;
    can: any;
    filters: {
        search?: string;
        severity?: string;
        status?: string;
        product?: string;
    };
}>();

// Local reactive filters for immediate UI updates
const searchQuery = ref(props.filters.search || '');
const selectedSeverity = ref(props.filters.severity || '');
const selectedStatus = ref(props.filters.status || '');
const selectedProduct = ref(props.filters.product || '');

// Debounced search function
let searchTimeout: NodeJS.Timeout;
const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('vulnerabilities.index'),
            {
                search: searchQuery.value || undefined,
                severity: selectedSeverity.value === 'all' ? undefined : selectedSeverity.value || undefined,
                status: selectedStatus.value === 'all' ? undefined : selectedStatus.value || undefined,
                product: selectedProduct.value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    }, 300);
};

// Watch for filter changes
watch([searchQuery, selectedSeverity, selectedStatus, selectedProduct], performSearch);

const getSeverityBadgeVariant = (severity: string) => {
    switch (severity) {
        case 'critical':
            return 'destructive';
        case 'high':
            return 'destructive';
        case 'medium':
            return 'secondary';
        case 'low':
            return 'outline';
        case 'info':
            return 'secondary';
        default:
            return 'secondary';
    }
};

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'open':
            return 'destructive';
        case 'in_progress':
            return 'secondary';
        case 'resolved':
            return 'default';
        case 'wont_fix':
            return 'outline';
        case 'duplicate':
            return 'outline';
        default:
            return 'secondary';
    }
};

const formatDate = (dateString: string) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Security Vulnerabilities" />

    <div class="container mx-auto px-6 py-10">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Shield class="h-8 w-8 text-red-600" />
                    <div>
                        <h1 class="text-3xl font-bold">Security Vulnerabilities</h1>
                        <p class="text-muted-foreground">Track and manage security vulnerabilities in your software products</p>
                    </div>
                </div>
                <Button v-if="can?.create" as-child size="lg">
                    <Link :href="route('vulnerabilities.create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Report Vulnerability
                    </Link>
                </Button>
            </div>

            <!-- Statistics Cards -->
            <div class="mb-6 grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Vulnerabilities</CardTitle>
                        <Shield class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ vulnerabilities?.total || 0 }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Critical/High</CardTitle>
                        <AlertTriangle class="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">
                            {{ vulnerabilities?.data?.filter((v) => ['critical', 'high'].includes(v.severity)).length || 0 }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Open Issues</CardTitle>
                        <Clock class="h-4 w-4 text-orange-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-600">
                            {{ vulnerabilities?.data?.filter((v) => v.status === 'open').length || 0 }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Resolved</CardTitle>
                        <Shield class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">
                            {{ vulnerabilities?.data?.filter((v) => v.status === 'resolved').length || 0 }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters Section -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle class="text-lg font-semibold">Search & Filters</CardTitle>
                    <CardDescription>Find the specific vulnerabilities you're looking for</CardDescription>
                </CardHeader>
                <CardContent class="pt-6">
                    <div class="flex flex-wrap gap-4">
                        <!-- Search -->
                        <div class="relative min-w-64 flex-1">
                            <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="searchQuery" class="pl-10" placeholder="Search vulnerabilities, CVEs, products..." />
                        </div>

                        <!-- Severity Filter -->
                        <Select id="severity" v-model="selectedSeverity">
                            <SelectTrigger class="w-fit">
                                <SelectValue placeholder="All Severities" />
                            </SelectTrigger>
                            <SelectContent class="max-w-xs min-w-[--reka-select-trigger-width]" position="popper">
                                <SelectItem value="all">All Severities</SelectItem>
                                <SelectItem value="critical">Critical</SelectItem>
                                <SelectItem value="high">High</SelectItem>
                                <SelectItem value="medium">Medium</SelectItem>
                                <SelectItem value="low">Low</SelectItem>
                                <SelectItem value="info">Info</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Status Filter -->
                        <Select v-model="selectedStatus">
                            <SelectTrigger class="w-40">
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem value="open">Open</SelectItem>
                                <SelectItem value="in_progress">In Progress</SelectItem>
                                <SelectItem value="resolved">Resolved</SelectItem>
                                <SelectItem value="wont_fix">Won't Fix</SelectItem>
                                <SelectItem value="duplicate">Duplicate</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Vulnerabilities List -->
        <div v-if="vulnerabilities?.data?.length" class="space-y-4">
            <Card v-for="vulnerability in vulnerabilities.data" :key="vulnerability.id" class="transition-shadow hover:shadow-md">
                <CardContent>
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <!-- Title and CVE -->
                            <div class="mb-2 flex items-start gap-3">
                                <Link
                                    :href="route('vulnerabilities.show', vulnerability.id)"
                                    class="text-lg font-semibold transition-colors hover:text-primary"
                                >
                                    {{ vulnerability.title }}
                                </Link>
                                <Badge v-if="vulnerability.cve_id" class="text-xs" variant="outline">
                                    {{ vulnerability.cve_id }}
                                </Badge>
                            </div>

                            <!-- Description -->
                            <p class="mb-4 line-clamp-2 text-muted-foreground">
                                {{ vulnerability.description }}
                            </p>

                            <!-- Meta Information -->
                            <div class="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                                <div class="flex items-center gap-1">
                                    <User class="h-4 w-4" />
                                    Product: {{ vulnerability.affected_product }}
                                </div>
                                <div v-if="vulnerability.affected_versions" class="flex items-center gap-1">
                                    Versions: {{ vulnerability.affected_versions }}
                                </div>
                                <div class="flex items-center gap-1">
                                    <Calendar class="h-4 w-4" />
                                    Discovered: {{ formatDate(vulnerability.discovered_at) }}
                                </div>
                                <div v-if="vulnerability.cvss_score" class="flex items-center gap-1">CVSS: {{ vulnerability.cvss_score }}/10</div>
                            </div>
                        </div>

                        <!-- Right side badges -->
                        <div class="ml-4 flex flex-col items-end gap-2">
                            <div class="flex gap-2">
                                <Badge :variant="getSeverityBadgeVariant(vulnerability.severity)">
                                    {{ vulnerability.severity.toUpperCase() }}
                                </Badge>
                                <Badge :variant="getStatusBadgeVariant(vulnerability.status)">
                                    {{ vulnerability.status.replace('_', ' ').toUpperCase() }}
                                </Badge>
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ formatDate(vulnerability.updated_at) }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Empty State -->
        <Card v-else>
            <CardContent class="pt-12 pb-12">
                <div class="text-center">
                    <Shield class="mx-auto mb-4 h-16 w-16 text-muted-foreground" />
                    <h3 class="mb-2 text-lg font-medium">No vulnerabilities found</h3>
                    <p class="mb-6 text-muted-foreground">
                        {{
                            filters.search || filters.severity || filters.status
                                ? 'No vulnerabilities match your current filters.'
                                : 'No security vulnerabilities have been reported yet.'
                        }}
                    </p>
                    <Button v-if="can?.create && !filters.search && !filters.severity && !filters.status" as-child>
                        <Link :href="route('vulnerabilities.create')">
                            <Plus class="mr-2 h-4 w-4" />
                            Report First Vulnerability
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Pagination -->
        <div v-if="vulnerabilities?.total > 1 && vulnerabilities?.last_page > 1" class="mt-8 flex">
            <Pagination
                v-slot="{ page }"
                :items-per-page="vulnerabilities?.per_page"
                :page="vulnerabilities?.current_page"
                :total="vulnerabilities?.total"
                class="justify-end"
                @update:page="
                    (p) =>
                        router.get(
                            route('vulnerabilities.index'),
                            {
                                page: p,
                                search: searchQuery || undefined,
                                severity: selectedSeverity || undefined,
                                status: selectedStatus || undefined,
                                product: selectedProduct || undefined,
                            },
                            { preserveScroll: true, preserveState: true },
                        )
                "
            >
                <PaginationContent v-slot="{ items }">
                    <PaginationFirst class="rounded">
                        <ChevronsLeft />
                    </PaginationFirst>
                    <PaginationPrevious class="rounded">
                        <ChevronLeft />
                    </PaginationPrevious>

                    <template v-for="(item, index) in items" :key="index">
                        <PaginationItem
                            v-if="item.type === 'page'"
                            :key="index"
                            :is-active="item.value === page"
                            :value="item.value"
                            class="rounded border border-secondary/40"
                        >
                            {{ item.value }}
                        </PaginationItem>
                        <PaginationEllipsis v-else :key="item.type" :index="index">&#8230;</PaginationEllipsis>
                    </template>

                    <PaginationNext class="rounded">
                        <ChevronRight />
                    </PaginationNext>
                    <PaginationLast class="rounded">
                        <ChevronsRight />
                    </PaginationLast>
                </PaginationContent>
            </Pagination>
        </div>
    </div>
</template>
