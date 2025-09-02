<script lang="ts" setup>
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { NavigationMenu, NavigationMenuItem, NavigationMenuLink, NavigationMenuList } from '@/components/ui/navigation-menu';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useAppearance } from '@/composables/useAppearance';
import { getInitials } from '@/composables/useInitials';
import SidebarLayout from '@/layouts/SidebarLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Menu, Monitor, Moon, Search, Shield, Sun } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    title: String,
});

const page = usePage();
const auth = page.props.auth as { user?: any };

// Determine if sidebar should be shown
const showSidebar = computed(() => {
    // Don't show sidebar on welcome page
    if (page.url === '/' && page.component === 'Welcome') {
        return false;
    }
    // Show sidebar on all other pages
    return true;
});

// Dark mode functionality
const { appearance, updateAppearance } = useAppearance();

// Theme toggle function - cycles through light -> dark -> system
const toggleTheme = () => {
    if (appearance.value === 'light') {
        updateAppearance('dark');
    } else if (appearance.value === 'dark') {
        updateAppearance('system');
    } else {
        updateAppearance('light');
    }
};

// Get current theme icon
const getThemeIcon = () => {
    switch (appearance.value) {
        case 'light':
            return Sun;
        case 'dark':
            return Moon;
        default:
            return Monitor;
    }
};

// Mobile sidebar state
const showMobileSidebar = ref(false);
const toggleMobileSidebar = () => {
    showMobileSidebar.value = !showMobileSidebar.value;
};
</script>

<template>
    <Head :title="props.title || 'root.d'" />
    <div class="min-h-screen bg-background">
        <!-- Header -->
        <header class="fixed top-0 right-0 left-0 z-50 h-16 border-b border-border bg-background/80 backdrop-blur-md">
            <div class="flex h-full items-center justify-between px-6">
                <!-- Left side: Logo and sidebar toggle -->
                <div class="flex items-center space-x-4">
                    <Button v-if="showSidebar" class="h-8 w-8 lg:hidden" size="icon" variant="ghost" @click="toggleMobileSidebar">
                        <Menu class="h-5 w-5" />
                    </Button>
                    <Link :href="route('home')" class="flex items-center space-x-3">
                        <Shield class="h-7 w-7 text-primary" />
                        <h1 class="text-xl font-bold">root.d</h1>
                    </Link>
                </div>

                <!-- Center items -->
                <div v-if="auth.user" class="flex items-center justify-center space-x-2">
                    <NavigationMenu>
                        <NavigationMenuList>
                            <NavigationMenuItem>
                                <NavigationMenuLink :href="route('posts.index')">Browse</NavigationMenuLink>
                            </NavigationMenuItem>
                            <NavigationMenuItem>
                                <NavigationMenuLink href="#">Calendar</NavigationMenuLink>
                            </NavigationMenuItem>
                            <NavigationMenuItem>
                                <NavigationMenuLink href="#">Wiki</NavigationMenuLink>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <!-- Right side: Actions -->
                <div class="flex items-center space-x-2">
                    <TooltipProvider :delay-duration="0">
                        <!-- Search -->
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button class="h-9 w-9" size="icon" variant="ghost">
                                    <Search class="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Search</TooltipContent>
                        </Tooltip>

                        <!-- Theme Toggle -->
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button class="h-9 w-9" size="icon" variant="ghost" @click="toggleTheme">
                                    <component :is="getThemeIcon()" class="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Theme: {{ appearance }}</TooltipContent>
                        </Tooltip>

                        <!-- User Menu -->
                        <DropdownMenu v-if="auth.user">
                            <DropdownMenuTrigger as-child>
                                <Button class="h-9 w-9 rounded-full" size="icon" variant="ghost">
                                    <Avatar class="h-8 w-8">
                                        <AvatarImage v-if="auth.user.avatar" :alt="auth.user.name" :src="auth.user.avatar" />
                                        <AvatarFallback class="bg-muted text-xs font-semibold">
                                            {{ getInitials(auth.user?.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-56">
                                <UserMenuContent :user="auth.user" />
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <!-- Guest Actions -->
                        <div v-else class="flex items-center space-x-2">
                            <Button as-child size="sm" variant="ghost">
                                <Link :href="route('login')">Login</Link>
                            </Button>
                            <Button as-child size="sm" variant="outline">
                                <Link :href="route('register')">Register</Link>
                            </Button>
                        </div>
                    </TooltipProvider>
                </div>
            </div>
        </header>

        <!-- Sidebar -->
        <SidebarLayout 
            :show-mobile="showMobileSidebar" 
            @toggle-mobile="toggleMobileSidebar" 
        />

        <!-- Main Content -->
        <main :class="['pt-16 transition-all duration-300 ease-in-out', showSidebar ? 'lg:pl-70' : '']">
            <slot />
        </main>
    </div>
</template>
