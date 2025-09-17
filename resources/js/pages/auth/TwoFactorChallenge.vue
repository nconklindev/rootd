<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    code: '',
    recovery_code: '',
});

function submit() {
    form.post(route('two-factor.login.store'));
}
</script>

<template>
    <AuthBase>
        <div class="mx-auto w-full max-w-md space-y-6 py-10">
            <Head title="Two-Factor Challenge" />
            <h1 class="text-center text-2xl font-semibold">Two-Factor Authentication</h1>
            <p class="text-center text-sm text-muted-foreground">Please enter the authentication code from your app or a recovery code.</p>
            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium" for="code">Authentication Code</label>
                    <input id="code" v-model="form.code" class="w-full rounded border px-3 py-2 font-mono" inputmode="numeric" maxlength="6" />
                </div>
                <div class="text-center text-xs text-muted-foreground">— or —</div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="recovery_code">Recovery Code</label>
                    <input id="recovery_code" v-model="form.recovery_code" class="w-full rounded border px-3 py-2 font-mono" />
                </div>
                <Button :disabled="form.processing" class="w-full cursor-pointer" type="submit">Continue</Button>
            </form>
        </div>
    </AuthBase>
</template>
