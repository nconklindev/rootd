<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { cn } from '@/lib/utils';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CalendarDate, DateFormatter, getLocalTimeZone, parseDate, today } from '@internationalized/date';
import { CalendarIcon } from 'lucide-vue-next';
import { toDate } from 'reka-ui/date';
import { computed, ref } from 'vue';

const df = new DateFormatter('en-US', {
    dateStyle: 'medium',
});

// Date picker computed properties following shadcn pattern
const discoveredDate = computed({
    get: () => (form.discovered_at ? parseDate(form.discovered_at) : undefined),
    set: (val) => val,
});

const disclosedDate = computed({
    get: () => (form.disclosed_at ? parseDate(form.disclosed_at) : undefined),
    set: (val) => val,
});

const resolvedDate = computed({
    get: () => (form.resolved_at ? parseDate(form.resolved_at) : undefined),
    set: (val) => val,
});

defineOptions({ layout: SiteLayout });

// Severity options based on the model constraints
const severityOptions = [
    { value: 'critical', label: 'Critical', description: 'Requires immediate attention' },
    { value: 'high', label: 'High', description: 'Should be addressed soon' },
    { value: 'medium', label: 'Medium', description: 'Should be addressed eventually' },
    { value: 'low', label: 'Low', description: 'Nice to have fixed' },
    { value: 'info', label: 'Information', description: 'Informational only' },
];

// Status options based on the model constraints
const statusOptions = [
    { value: 'open', label: 'Open', description: 'Newly reported, needs investigation' },
    { value: 'in_progress', label: 'In Progress', description: 'Currently being worked on' },
    { value: 'resolved', label: 'Resolved', description: 'Issue has been fixed' },
    { value: 'wont_fix', label: "Won't Fix", description: 'Decision made not to fix' },
    { value: 'duplicate', label: 'Duplicate', description: 'Already reported elsewhere' },
];

const references = ref<string[]>(['']);

const form = useForm({
    title: '',
    cve_id: '',
    description: '',
    severity: '',
    affected_product: '',
    affected_versions: '',
    status: 'open', // Default to open
    reporter_name: '',
    reporter_email: '',
    cvss_score: undefined as number | undefined,
    remediation: '',
    discovered_at: '',
    disclosed_at: '',
    resolved_at: '',
    references: [] as string[],
});

const isFormValid = computed(() => {
    return form.title && form.description && form.severity && form.affected_product && form.status;
});

// Get the display class for severity
const getSeverityClass = (severity: string): string => {
    const classes = {
        critical: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        high: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        low: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        info: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
    };
    return classes[severity as keyof typeof classes] || '';
};

function addReference(): void {
    references.value.push('');
}

function removeReference(index: number): void {
    if (references.value.length > 1) {
        references.value.splice(index, 1);
        updateFormReferences();
    }
}

function updateFormReferences(): void {
    form.references = references.value.filter((ref) => ref.trim() !== '');
}

function submit(): void {
    // Update references before submission
    updateFormReferences();

    form.post(route('vulnerabilities.store'), {
        preserveState: (page) => Object.keys(page.props.errors).length > 0,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Report Vulnerability" />
    <div class="container mx-auto min-w-0 px-6 py-10">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Report New Vulnerability</h1>
            <Link :href="route('vulnerabilities.index')" class="text-primary underline-offset-4 hover:underline">Back to List</Link>
        </div>

        <div class="overflow-x-hidden rounded border bg-card p-6">
            <form class="space-y-6" @submit.prevent="submit">
                <!-- Basic Information Section -->
                <div class="space-y-4">
                    <h2 class="border-b pb-2 text-lg font-semibold">Basic Information</h2>

                    <!-- Title -->
                    <div>
                        <Label for="title">Title *</Label>
                        <Input id="title" v-model="form.title" class="mt-1" placeholder="e.g., SQL Injection in User Authentication" required />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <!-- CVE ID -->
                    <div>
                        <Label for="cve_id">CVE ID</Label>
                        <Input id="cve_id" v-model="form.cve_id" class="mt-1" placeholder="e.g., CVE-2024-1234" />
                        <InputError :message="form.errors.cve_id" class="mt-2" />
                        <p class="mt-1 text-sm text-muted-foreground">Leave empty if not yet assigned</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <Label for="description">Description *</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            class="mt-1"
                            placeholder="Detailed description of the vulnerability, including how it can be exploited..."
                            required
                            rows="4"
                        />
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>
                </div>

                <!-- Classification Section -->
                <div class="space-y-4">
                    <h2 class="border-b pb-2 text-lg font-semibold">Classification</h2>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- Severity -->
                        <div>
                            <Label for="severity">Severity *</Label>
                            <Select v-model="form.severity" required>
                                <SelectTrigger class="mt-1 min-w-40">
                                    <SelectValue placeholder="Select severity level">
                                        <template v-if="form.severity">
                                            <div class="flex items-center gap-2">
                                                <span :class="getSeverityClass(form.severity)" class="rounded-full px-2 py-1 text-xs font-medium">
                                                    {{ severityOptions.find((s) => s.value === form.severity)?.label }}
                                                </span>
                                            </div>
                                        </template>
                                    </SelectValue>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="severity in severityOptions" :key="severity.value" :value="severity.value">
                                        <div class="flex items-center gap-2">
                                            <span :class="getSeverityClass(severity.value)" class="rounded-full px-2 py-1 text-xs font-medium">
                                                {{ severity.label }}
                                            </span>
                                            <span class="text-sm text-muted-foreground">{{ severity.description }}</span>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.severity" class="mt-2" />
                        </div>

                        <!-- Status -->
                        <div>
                            <Label for="status">Status *</Label>
                            <Select v-model="form.status" required>
                                <SelectTrigger class="mt-1 min-w-40">
                                    <SelectValue placeholder="Select status">
                                        <template v-if="form.status">
                                            {{ statusOptions.find((s) => s.value === form.status)?.label }}
                                        </template>
                                    </SelectValue>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="status in statusOptions" :key="status.value" :value="status.value">
                                        <div class="flex flex-col gap-1">
                                            <span>{{ status.label }}</span>
                                            <span class="text-xs text-muted-foreground">{{ status.description }}</span>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.status" class="mt-2" />
                        </div>
                    </div>

                    <!-- CVSS Score -->
                    <div>
                        <Label for="cvss_score">CVSS Score (0.0 - 10.0)</Label>
                        <Input
                            id="cvss_score"
                            v-model="form.cvss_score"
                            class="mt-1 max-w-xs"
                            max="10"
                            min="0"
                            placeholder="e.g., 7.5"
                            step="0.1"
                            type="number"
                        />
                        <InputError :message="form.errors.cvss_score" class="mt-2" />
                        <p class="mt-1 text-sm text-muted-foreground">Common Vulnerability Scoring System score</p>
                    </div>
                </div>

                <!-- Product Information Section -->
                <div class="space-y-4">
                    <h2 class="border-b pb-2 text-lg font-semibold">Affected Product</h2>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- Affected Product -->
                        <div>
                            <Label for="affected_product">Product Name *</Label>
                            <Input
                                id="affected_product"
                                v-model="form.affected_product"
                                class="mt-1"
                                placeholder="e.g., WordPress, Apache HTTP Server"
                                required
                            />
                            <InputError :message="form.errors.affected_product" class="mt-2" />
                        </div>

                        <!-- Affected Versions -->
                        <div>
                            <Label for="affected_versions">Affected Versions</Label>
                            <Input id="affected_versions" v-model="form.affected_versions" class="mt-1" placeholder="e.g., 5.0-6.3, < 2.4.41" />
                            <InputError :message="form.errors.affected_versions" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Reporter Information Section -->
                <div class="space-y-4">
                    <h2 class="border-b pb-2 text-lg font-semibold">Reporter Information</h2>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- Reporter Name -->
                        <div>
                            <Label for="reporter_name">Reporter Name</Label>
                            <Input id="reporter_name" v-model="form.reporter_name" class="mt-1" placeholder="John Doe" />
                            <InputError :message="form.errors.reporter_name" class="mt-2" />
                        </div>

                        <!-- Reporter Email -->
                        <div>
                            <Label for="reporter_email">Reporter Email</Label>
                            <Input id="reporter_email" v-model="form.reporter_email" class="mt-1" placeholder="john@example.com" type="email" />
                            <InputError :message="form.errors.reporter_email" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Timeline Section -->
                <div class="space-y-4">
                    <h2 class="border-b pb-2 text-lg font-semibold">Timeline</h2>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <!-- Discovered At -->
                        <div>
                            <Label for="discovered_at">Discovery Date</Label>
                            <Popover>
                                <PopoverTrigger as-child>
                                    <Button
                                        :class="cn('mt-1 w-full ps-3 text-start font-normal', !discoveredDate && 'text-muted-foreground')"
                                        variant="outline"
                                    >
                                        <span>{{ discoveredDate ? df.format(toDate(discoveredDate)) : 'Pick discovery date' }}</span>
                                        <CalendarIcon class="ms-auto h-4 w-4 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent align="start" class="w-auto p-0">
                                    <Calendar
                                        :max-value="today(getLocalTimeZone())"
                                        :min-value="new CalendarDate(1900, 1, 1)"
                                        :model-value="discoveredDate"
                                        calendar-label="Discovery date"
                                        initial-focus
                                        @update:model-value="
                                            (v) => {
                                                if (v) {
                                                    form.discovered_at = v.toString();
                                                } else {
                                                    form.discovered_at = '';
                                                }
                                            }
                                        "
                                    />
                                </PopoverContent>
                            </Popover>
                            <InputError :message="form.errors.discovered_at" class="mt-2" />
                        </div>

                        <!-- Disclosed At -->
                        <div>
                            <Label for="disclosed_at">Disclosure Date</Label>
                            <Popover>
                                <PopoverTrigger as-child>
                                    <Button
                                        :class="cn('mt-1 w-full ps-3 text-start font-normal', !disclosedDate && 'text-muted-foreground')"
                                        variant="outline"
                                    >
                                        <span>{{ disclosedDate ? df.format(toDate(disclosedDate)) : 'Pick disclosure date' }}</span>
                                        <CalendarIcon class="ms-auto h-4 w-4 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent align="start" class="w-auto p-0">
                                    <Calendar
                                        :max-value="today(getLocalTimeZone())"
                                        :min-value="new CalendarDate(1900, 1, 1)"
                                        :model-value="disclosedDate"
                                        calendar-label="Disclosure date"
                                        initial-focus
                                        @update:model-value="
                                            (v) => {
                                                if (v) {
                                                    form.disclosed_at = v.toString();
                                                } else {
                                                    form.disclosed_at = '';
                                                }
                                            }
                                        "
                                    />
                                </PopoverContent>
                            </Popover>
                            <InputError :message="form.errors.disclosed_at" class="mt-2" />
                        </div>

                        <!-- Resolved At -->
                        <div>
                            <Label for="resolved_at">Resolution Date</Label>
                            <Popover>
                                <PopoverTrigger as-child>
                                    <Button
                                        :class="cn('mt-1 w-full ps-3 text-start font-normal', !resolvedDate && 'text-muted-foreground')"
                                        variant="outline"
                                    >
                                        <span>{{ resolvedDate ? df.format(toDate(resolvedDate)) : 'Pick resolution date' }}</span>
                                        <CalendarIcon class="ms-auto h-4 w-4 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent align="start" class="w-auto p-0">
                                    <Calendar
                                        :max-value="today(getLocalTimeZone())"
                                        :min-value="new CalendarDate(1900, 1, 1)"
                                        :model-value="resolvedDate"
                                        calendar-label="Resolution date"
                                        initial-focus
                                        @update:model-value="
                                            (v) => {
                                                if (v) {
                                                    form.resolved_at = v.toString();
                                                } else {
                                                    form.resolved_at = '';
                                                }
                                            }
                                        "
                                    />
                                </PopoverContent>
                            </Popover>
                            <InputError :message="form.errors.resolved_at" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Solution Section -->
                <div class="space-y-4">
                    <h2 class="border-b pb-2 text-lg font-semibold">Remediation</h2>

                    <!-- Remediation -->
                    <div>
                        <Label for="remediation">Remediation Steps</Label>
                        <Textarea
                            id="remediation"
                            v-model="form.remediation"
                            class="mt-1"
                            placeholder="Describe the steps to fix or mitigate this vulnerability..."
                            rows="4"
                        />
                        <InputError :message="form.errors.remediation" class="mt-2" />
                    </div>
                </div>

                <!-- References Section -->
                <div class="space-y-4">
                    <h2 class="border-b pb-2 text-lg font-semibold">References</h2>

                    <div class="space-y-3">
                        <div v-for="(reference, index) in references" :key="index" class="flex items-center gap-2">
                            <Input
                                v-model="references[index]"
                                :placeholder="`Reference URL ${index + 1}`"
                                class="flex-1"
                                type="url"
                                @input="updateFormReferences"
                            />
                            <Button v-if="references.length > 1" size="sm" type="button" variant="outline" @click="removeReference(index)">
                                Remove
                            </Button>
                        </div>

                        <Button size="sm" type="button" variant="outline" @click="addReference"> Add Reference </Button>
                    </div>
                    <InputError :message="form.errors.references" class="mt-2" />
                </div>

                <!-- Progress Bar -->
                <progress v-if="form.progress" :value="form.progress.percentage" class="w-full" max="100">{{ form.progress.percentage }}%</progress>

                <!-- Submit Button -->
                <div class="border-t pt-4">
                    <div class="flex items-center gap-4">
                        <Button :disabled="!isFormValid || form.processing" class="min-w-32" type="submit">
                            <span v-if="form.processing">Creating...</span>
                            <span v-else>Report Vulnerability</span>
                        </Button>

                        <Link :href="route('vulnerabilities.index')" class="text-muted-foreground underline-offset-4 hover:underline"> Cancel </Link>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
