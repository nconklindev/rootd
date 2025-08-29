<script lang="ts" setup>
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

const sidebarNavItems: NavItem[] = [
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

const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';
</script>

<template>
    <div class="container mx-auto px-4 py-10">
        <div class="flex justify-center">
            <div class="w-full max-w-none lg:max-w-4xl">
                <div class="mb-8">
                    <Heading description="Manage your profile and account settings" title="Settings" />
                </div>
                
                <div class="flex flex-col lg:flex-row lg:space-x-12">
                    <aside class="w-full max-w-xl lg:w-48">
                        <nav class="flex flex-col space-y-1 space-x-0">
                            <Button
                                v-for="item in sidebarNavItems"
                                :key="item.href"
                                :class="['w-full justify-start', { 'bg-muted': currentPath === item.href }]"
                                as-child
                                variant="ghost"
                            >
                                <Link :href="item.href">
                                    {{ item.title }}
                                </Link>
                            </Button>
                        </nav>
                    </aside>

                    <Separator class="my-6 lg:hidden" />

                    <div class="flex-1 md:max-w-2xl">
                        <section class="mx-auto max-w-xl space-y-12">
                            <slot />
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
