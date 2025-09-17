<script lang="ts" setup>
import FormDescription from '@/components/FormDescription.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SiteLayout from '@/layouts/SiteLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Key, Shield, ShieldCheck, ShieldX, Smartphone } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    twoFactorEnabled?: boolean;
    backupCodesGenerated?: boolean;
    isConfirming?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    twoFactorEnabled: false,
    backupCodesGenerated: false,
    isConfirming: false,
});

defineOptions({ layout: SiteLayout });

const form = useForm({
    two_factor_enabled: props.twoFactorEnabled,
    code: '',
});

const isConfirming = ref<boolean>(props.isConfirming);
const qrCodeSvg = ref<string | null>(null);
const secretKey = ref<string | null>(null);
const recoveryCodes = ref<string[]>([]);
const loadingQr = ref(false);
const loadingRecovery = ref(false);

const securityStatus = computed(() => {
    if (props.twoFactorEnabled) {
        return {
            text: 'SECURE',
            variant: 'default' as const,
            icon: ShieldCheck,
            color: 'text-green-600 dark:text-green-400',
        };
    }
    return {
        text: 'BASIC',
        variant: 'secondary' as const,
        icon: ShieldX,
        color: 'text-yellow-600 dark:text-yellow-400',
    };
});

async function fetchQrAndSecret() {
    try {
        loadingQr.value = true;
        const [qrRes, secretRes] = await Promise.all([axios.get(route('two-factor.qr-code')), axios.get(route('two-factor.secret-key'))]);
        qrCodeSvg.value = qrRes.data?.svg ?? null;
        secretKey.value = secretRes.data?.secretKey ?? null;
    } finally {
        loadingQr.value = false;
    }
}

async function loadRecoveryCodes() {
    try {
        let data = [];
        loadingRecovery.value = true;
        const response = await fetch(route('two-factor.recovery-codes'));

        if (response.ok) {
            data = await response.json();
        }

        recoveryCodes.value = Array.isArray(data) ? data : [];
    } catch (error) {
        console.error(error);
    } finally {
        loadingRecovery.value = false;
    }
}

async function regenerateRecoveryCodes() {
    router.post(route('two-factor.regenerate-recovery-codes'), {}, { preserveScroll: true });
    await loadRecoveryCodes();
}

function submit() {
    // Enable or disable based on checkbox vs. current props
    if (form.two_factor_enabled && !props.twoFactorEnabled) {
        form.post(route('two-factor.enable'), {
            preserveScroll: true,
            onSuccess: () => {
                isConfirming.value = true;
                fetchQrAndSecret();
            },
        });
    } else if (!form.two_factor_enabled && props.twoFactorEnabled) {
        form.delete(route('two-factor.disable'), {
            onSuccess: () => {
                isConfirming.value = false;
                qrCodeSvg.value = null;
                secretKey.value = null;
                recoveryCodes.value = [];
                form.code = '';
            },
        });
    }
}

function confirmTwoFactor() {
    form.post(route('two-factor.confirm'), {
        preserveScroll: true,
        onSuccess: () => {
            // Reload the page props to reflect the confirmed state
            router.reload({ only: ['twoFactorEnabled', 'isConfirming', 'backupCodesGenerated'] });
            isConfirming.value = false;
            form.code = '';
        },
    });
}

watch(
    () => props.isConfirming,
    (val) => {
        isConfirming.value = val;
        if (val) {
            fetchQrAndSecret();
        }
    },
    { immediate: true },
);

onMounted(() => {
    if (isConfirming.value) {
        fetchQrAndSecret();
    }

    // Check if we should auto-show recovery codes
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('show-recovery-codes') === '1' && props.twoFactorEnabled) {
        loadRecoveryCodes();
    }
});
</script>

<template>
    <SettingsLayout description="Manage your account security and two-factor authentication">
        <Head title="Security Settings" />

        <div class="space-y-6">
            <!-- Security Status Card -->
            <Card>
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <Shield class="h-5 w-5" />
                            <CardTitle class="text-lg">Security Status</CardTitle>
                        </div>
                        <Badge :variant="securityStatus.variant" class="flex items-center space-x-1">
                            <component :is="securityStatus.icon" class="h-3 w-3" />
                            <span>{{ securityStatus.text }}</span>
                        </Badge>
                    </div>
                    <CardDescription> Current security level of your account </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-start space-x-3 rounded-lg border bg-muted/30 p-4">
                        <component :is="securityStatus.icon" :class="[securityStatus.color, 'mt-0.5 h-5 w-5']" />
                        <div class="space-y-1">
                            <p class="text-sm font-medium">
                                {{ props.twoFactorEnabled ? 'Two-factor authentication is enabled' : 'Basic security active' }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    props.twoFactorEnabled
                                        ? 'Your account is protected with an additional layer of security.'
                                        : 'Consider enabling 2FA for enhanced account protection.'
                                }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Two-Factor Authentication Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-center space-x-2">
                        <Smartphone class="h-5 w-5" />
                        <CardTitle class="text-lg">Two-Factor Authentication</CardTitle>
                    </div>
                    <CardDescription> Add an additional layer of security to your account </CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-6" @submit.prevent="submit">
                        <div class="space-y-4">
                            <!-- 2FA Toggle -->
                            <div class="flex items-start space-x-3">
                                <Checkbox
                                    id="two_factor_enabled"
                                    v-model:checked="form.two_factor_enabled"
                                    :disabled="twoFactorEnabled"
                                    class="mt-1"
                                    name="two_factor_enabled"
                                    type="checkbox"
                                />
                                <div class="flex-1 space-y-1">
                                    <Label
                                        class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                        for="two_factor_enabled"
                                    >
                                        Enable Two-Factor Authentication
                                    </Label>
                                    <FormDescription
                                        class="text-xs"
                                        message="When enabled, you'll need to enter a verification code from your authenticator app every time you sign in."
                                    />
                                </div>
                            </div>

                            <!-- QR Code Section (shown when enabling 2FA and during confirmation) -->
                            <div
                                v-if="(form.two_factor_enabled && !props.twoFactorEnabled) || isConfirming"
                                class="rounded-lg border bg-muted/30 p-4"
                            >
                                <div class="space-y-3">
                                    <div class="flex items-center space-x-2">
                                        <Key class="h-4 w-4" />
                                        <h4 class="text-sm font-medium">Setup Authenticator App</h4>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Scan the QR code below with your authenticator app (like Google Authenticator, Authy, or 1Password).
                                    </p>
                                    <!-- QR Code -->
                                    <div class="flex justify-center p-6">
                                        <div
                                            v-if="loadingQr"
                                            class="flex h-48 w-48 items-center justify-center rounded-lg border border-dashed bg-muted text-xs text-muted-foreground"
                                        >
                                            Loading QR...
                                        </div>
                                        <div v-else-if="qrCodeSvg" class="rounded-lg bg-white p-2 shadow" v-html="qrCodeSvg"></div>
                                        <div
                                            v-else
                                            class="flex h-48 w-48 items-center justify-center rounded-lg border border-dashed bg-muted text-xs text-muted-foreground"
                                        >
                                            QR not available yet
                                        </div>
                                    </div>
                                    <div v-if="secretKey" class="text-center text-xs text-muted-foreground">
                                        Or enter this key manually: <span class="font-mono text-sm">{{ secretKey }}</span>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="verification_code">Verification Code</Label>
                                        <Input
                                            id="verification_code"
                                            v-model="form.code"
                                            class="text-center font-mono text-lg tracking-widest"
                                            maxlength="6"
                                            placeholder="Enter 6-digit code from your app"
                                        />
                                        <FormDescription
                                            class="text-xs"
                                            message="Enter the 6-digit code from your authenticator app to complete setup."
                                        />
                                        <div>
                                            <Button :disabled="form.processing || !form.code" size="sm" type="button" @click="confirmTwoFactor"
                                                >Confirm</Button
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Backup Codes Section -->
                            <div v-if="props.twoFactorEnabled" class="rounded-lg border bg-muted/30 p-4">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <Key class="h-4 w-4" />
                                            <h4 class="text-sm font-medium">Backup Codes</h4>
                                        </div>
                                        <Badge v-if="props.backupCodesGenerated" variant="outline"> Generated </Badge>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Backup codes can be used to access your account if you lose your authenticator device. Store them in a safe
                                        place.
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <Button :disabled="loadingRecovery" size="sm" type="button" variant="outline" @click="loadRecoveryCodes">
                                            {{ loadingRecovery ? 'Loading…' : 'Show Backup Codes' }}
                                        </Button>
                                        <Button
                                            :disabled="loadingRecovery"
                                            size="sm"
                                            type="button"
                                            variant="secondary"
                                            @click="regenerateRecoveryCodes"
                                        >
                                            Regenerate Codes
                                        </Button>
                                    </div>
                                    <ul v-if="recoveryCodes.length" class="mt-3 grid grid-cols-2 gap-2 font-mono text-xs">
                                        <li v-for="code in recoveryCodes" :key="code" class="rounded border bg-background px-2 py-1">{{ code }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3 pt-4">
                            <Button
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing || twoFactorEnabled || isConfirming"
                                class="cursor-pointer disabled:cursor-not-allowed"
                                type="submit"
                                @click="
                                    form.two_factor_enabled = true;
                                    submit();
                                "
                            >
                                {{ form.processing ? 'Updating...' : 'Update Security Settings' }}
                            </Button>

                            <Button
                                v-if="props.twoFactorEnabled && form.two_factor_enabled"
                                type="button"
                                variant="destructive"
                                @click="
                                    form.two_factor_enabled = false;
                                    submit();
                                "
                            >
                                Disable 2FA
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </SettingsLayout>
</template>
