<script lang="ts" setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { type User } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();
defineOptions({ layout: SiteLayout });

const page = usePage();
const user = page.props.auth.user as User;

const form = useForm({
    name: user.name,
    username: user.username,
    email: user.email,
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Profile Settings" />

    <!-- Settings page with consistent SiteLayout structure -->
    <SettingsLayout description="Manage your profile settings" title="Profile Settings">
        <!-- Main content area -->
        <!-- Profile information section -->
        <div class="flex flex-col space-y-6">
            <HeadingSmall description="Update your name, email address, or username" title="Profile information" />

            <form class="space-y-6" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" autocomplete="name" class="mt-1 block w-full" placeholder="Full name" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>
                <div class="grid gap-2">
                    <Label for="username">Username</Label>
                    <Input
                        id="username"
                        v-model="form.username"
                        autocomplete="username"
                        class="mt-1 block w-full"
                        placeholder="HackedByRobert1337"
                        required
                    />
                    <InputError :message="form.errors.username" class="mt-2" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        autocomplete="email"
                        class="mt-1 block w-full"
                        placeholder="Email address"
                        required
                        type="email"
                    />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>

                <div v-if="mustVerifyEmail && !user.email_verified_at">
                    <p class="-mt-4 text-sm text-muted-foreground">
                        Your email address is unverified.
                        <Link
                            :href="route('verification.send')"
                            as="button"
                            class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            method="post"
                        >
                            Click here to resend the verification email.
                        </Link>
                    </p>

                    <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                        A new verification link has been sent to your email address.
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="form.processing">Save</Button>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p v-show="form.recentlySuccessful" class="text-sm text-muted-foreground">Saved.</p>
                    </Transition>
                </div>
            </form>
        </div>

        <!-- Delete account section -->
        <DeleteUser />
    </SettingsLayout>
</template>
