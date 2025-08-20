<script lang="ts" setup>
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { Head, Link } from '@inertiajs/vue3';
import { Shield } from 'lucide-vue-next';

const props = defineProps({
    title: String,
});
</script>

<template>
    <Head :title="props.title || 'root.d'" />
    <div class="min-h-screen">
        <header class="border-b border-white/20">
            <div class="container mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <Link :href="route('home')" class="flex items-center space-x-2">
                        <Shield class="h-8 w-8 text-primary" />
                        <h1 class="text-xl font-bold">root.d</h1>
                    </Link>
                    <NavigationMenu>
                        <NavigationMenuList>
                            <NavigationMenuItem v-if="$page.props.auth.user">
                                <NavigationMenuLink :class="navigationMenuTriggerStyle()" href="/dashboard"> Dashboard </NavigationMenuLink>
                            </NavigationMenuItem>
                            <NavigationMenuItem>
                                <NavigationMenuLink :class="navigationMenuTriggerStyle()" :href="route('posts.index')"> Posts </NavigationMenuLink>
                            </NavigationMenuItem>
                            <NavigationMenuItem>
                                <NavigationMenuLink :class="navigationMenuTriggerStyle()" href="/discussions"> Discussions </NavigationMenuLink>
                            </NavigationMenuItem>
                            <div v-if="$page.props.auth.user">
                                <NavigationMenuItem>
                                    <NavigationMenuLink :class="navigationMenuTriggerStyle()" as-child class="cursor-pointer">
                                        <Link :href="route('logout')" as="button" method="post"> Logout</Link>
                                    </NavigationMenuLink>
                                </NavigationMenuItem>
                            </div>
                            <div v-else class="flex flex-row">
                                <NavigationMenuItem>
                                    <NavigationMenuLink
                                        :active="$page.component === 'Login'"
                                        :class="navigationMenuTriggerStyle()"
                                        :href="route('login')"
                                    >
                                        Login
                                    </NavigationMenuLink>
                                </NavigationMenuItem>
                                <NavigationMenuItem>
                                    <Link :class="navigationMenuTriggerStyle()" :href="route('register')"> Register </Link>
                                </NavigationMenuItem>
                            </div>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>
            </div>
        </header>

        <!-- Page content -->
        <main>
            <slot />
        </main>
    </div>
</template>

<style scoped></style>
