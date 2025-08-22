<script lang="ts" setup>
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useAppearance } from '@/composables/useAppearance';
import { getInitials } from '@/composables/useInitials';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    FileText,
    HelpCircle,
    Home,
    Menu,
    MessageSquare,
    Monitor,
    Moon,
    PlusCircle,
    Search,
    Settings,
    Shield,
    Sun,
    TrendingUp,
    User,
} from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    title: String,
});

const page = usePage();
const siteData = page.props.siteData as { categories: any[] };
const auth = page.props.auth as { user?: any };

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

// Navigation items
const mainNavItems = [
    { href: '/', icon: Home, label: 'Home' },
    { href: route('posts.index'), icon: FileText, label: 'Posts' },
    { href: '/discussions', icon: MessageSquare, label: 'Discussions' },
];

const myStuffItems = [
    { href: '/dashboard', icon: User, label: 'Dashboard' },
    { href: route('posts.create'), icon: PlusCircle, label: 'Create Post' },
    { href: '/my-posts', icon: FileText, label: 'My Posts' },
];

const quickActionItems = auth.user
    ? [{ href: '/help', icon: HelpCircle, label: 'Help & Support' }]
    : [
          { href: route('login'), icon: User, label: 'Sign In' },
          { href: route('register'), icon: PlusCircle, label: 'Sign Up' },
          { href: '/help', icon: HelpCircle, label: 'Help & Support' },
      ];

// Get top 5 categories for display
const popularCategories = siteData?.categories?.slice(0, 5) || [];
</script>

<template>
    <Head :title="props.title || 'root.d'" />
    <div class="min-h-screen bg-background">
        <!-- Header -->
        <header class="fixed top-0 right-0 left-0 z-50 h-16 border-b border-border bg-background/80 backdrop-blur-md">
            <div class="flex h-full items-center justify-between px-6">
                <!-- Left side: Logo and sidebar toggle -->
                <div class="flex items-center space-x-4">
                    <Button class="h-8 w-8 lg:hidden" size="icon" variant="ghost" @click="toggleMobileSidebar">
                        <Menu class="h-5 w-5" />
                    </Button>
                    <Link :href="route('home')" class="flex items-center space-x-3">
                        <Shield class="h-7 w-7 text-primary" />
                        <h1 class="text-xl font-bold">root.d</h1>
                    </Link>
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
        <aside
            :class="[
                'fixed top-16 left-0 z-40 h-[calc(100vh-4rem)] w-64 transform transition-transform duration-300 ease-in-out',
                'border-r border-border bg-background',
                'lg:translate-x-0',
                showMobileSidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            ]"
        >
            <div class="sidebar-scroll flex h-full flex-col overflow-y-auto p-4">
                <!-- Main Navigation -->
                <div class="mb-6 space-y-1">
                    <Link
                        v-for="item in mainNavItems"
                        :key="item.href"
                        :class="[
                            'flex items-center space-x-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            $page.url === item.href || ($page.url.startsWith(item.href) && item.href !== '/')
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                        :href="item.href"
                    >
                        <component :is="item.icon" class="h-4 w-4" />
                        <span>{{ item.label }}</span>
                    </Link>
                </div>

                <!-- My Stuff Section -->
                <div v-if="auth.user" class="mb-6">
                    <h3 class="mb-2 px-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase">My Stuff</h3>
                    <div class="space-y-1">
                        <Link
                            v-for="item in myStuffItems"
                            :key="item.href"
                            :class="[
                                'flex items-center space-x-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                $page.url === item.href
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                            ]"
                            :href="item.href"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            <span>{{ item.label }}</span>
                        </Link>
                    </div>
                </div>

                <!-- Popular Categories -->
                <div v-if="popularCategories.length > 0" class="mb-6">
                    <h3 class="mb-2 px-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Popular Categories</h3>
                    <div class="space-y-1">
                        <Link
                            v-for="category in popularCategories"
                            :key="category.slug"
                            :href="`/categories/${category.slug}`"
                            class="flex items-center space-x-3 rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        >
                            <div :style="{ backgroundColor: category.color }" class="h-3 w-3 flex-shrink-0 rounded-full" />
                            <span class="flex-1 truncate">{{ category.name }}</span>
                            <span v-if="category.post_count > 0" class="text-xs">
                                {{ category.post_count }}
                            </span>
                        </Link>
                        <Link
                            class="flex items-center space-x-3 rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                            href="/categories"
                        >
                            <TrendingUp class="h-4 w-4" />
                            <span>Browse All Categories</span>
                        </Link>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mb-6">
                    <h3 class="mb-2 px-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Quick Actions</h3>
                    <div class="space-y-1">
                        <Link
                            v-for="item in quickActionItems"
                            :key="item.href"
                            :href="item.href"
                            class="flex items-center space-x-3 rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            <span>{{ item.label }}</span>
                        </Link>
                    </div>
                </div>

                <!-- Settings (Footer) -->
                <div v-if="auth.user" class="mt-auto border-t border-border pt-4">
                    <Link
                        :class="[
                            'flex items-center space-x-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            $page.url === route('profile.edit')
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                        :href="route('profile.edit')"
                    >
                        <Settings class="h-4 w-4" />
                        <span>Settings</span>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Mobile Sidebar Overlay -->
        <div v-if="showMobileSidebar" class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="showMobileSidebar = false" />

        <!-- Main Content -->
        <main :class="['pt-16 transition-all duration-300 ease-in-out', 'lg:pl-64']">
            <slot />
        </main>
    </div>
</template>

<style scoped>
/* Custom scrollbar styling for sidebar */
.sidebar-scroll::-webkit-scrollbar {
    width: 6px;
}

.sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-scroll::-webkit-scrollbar-thumb {
    background: hsl(var(--muted-foreground) / 0.3);
    border-radius: 3px;
    transition: background-color 0.2s ease;
}

.sidebar-scroll::-webkit-scrollbar-thumb:hover {
    background: hsl(var(--muted-foreground) / 0.6);
}

/* Firefox scrollbar styling */
.sidebar-scroll {
    scrollbar-width: thin;
    scrollbar-color: hsl(var(--muted-foreground) / 0.3) transparent;
}
</style>
