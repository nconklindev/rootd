<script lang="ts" setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { type NavItem, type User } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();
defineOptions({ layout: SiteLayout });

// Settings navigation items
const settingsNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: '/settings/profile',
    },
    {
        title: 'Password',
        href: '/settings/password',
    },
    {
        title: 'Appearance',
        href: '/settings/appearance',
    },
];

const page = usePage();
const user = page.props.auth.user as User;

// Helper to check if settings nav item is active
const isSettingsItemActive = (href: string) => {
    return page.url === href;
};

const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Profile settings" />
    
    <!-- Settings page with consistent SiteLayout structure -->
    <div class="container mx-auto px-6 py-10">
        <!-- Settings page header -->
        <Heading title="Settings" description="Manage your profile and account settings" />

        <!-- Settings layout with navigation and content -->
        <div class="mt-8 flex flex-col space-y-8 lg:flex-row lg:space-x-12 lg:space-y-0">
            <!-- Settings navigation sidebar -->
            <aside class="w-full lg:w-64">
                <nav class="flex flex-col space-y-1">
                    <Button
                        v-for="item in settingsNavItems"
                        :key="item.href"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            isSettingsItemActive(item.href) ? 'bg-muted' : ''
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <!-- Mobile separator -->
            <Separator class="lg:hidden" />

            <!-- Main content area -->
            <div class="flex-1 max-w-2xl">
                <div class="space-y-8">
                    <!-- Profile information section -->
                    <div class="space-y-6">
                        <HeadingSmall 
                            description="Update your name and email address" 
                            title="Profile information" 
                        />

                        <form class="space-y-6" @submit.prevent="submit">
                            <div class="grid gap-2">
                                <Label for="name">Name</Label>
                                <Input 
                                    id="name" 
                                    v-model="form.name" 
                                    autocomplete="name" 
                                    class="mt-1 block w-full" 
                                    placeholder="Full name" 
                                    required 
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="email">Email address</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    autocomplete="username"
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
                                    <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                                </Transition>
                            </div>
                        </form>
                    </div>

                    <!-- Delete account section -->
                    <DeleteUser />
                </div>
            </div>
        </div>
    </div>
</template>
