<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Link, usePage } from '@inertiajs/vue3';
import { FileText, HelpCircle, Home, PlusCircle, Rss, Settings, ShieldAlert, Tag, TrendingUp, User } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    title: String,
    showMobile: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['toggle-mobile']);

const page = usePage();
const auth = page.props.auth as { user?: any };
const siteData = page.props.siteData as { categories: any[] };

const isSidebarItemActive = (item: any) => {
    // First, check for exact component matches (higher precedence)
    if (page.component === item.component) {
        return true;
    }

    // Special handling for Posts - should be active on Posts/Index and Posts/Show only
    // NOT on Posts/Create (which should match "Create Post" instead)
    if (item.routeName === 'posts.index') {
        return page.component === 'Posts/Index' || page.component === 'Posts/Show';
    }

    return false;
};

// Determine if sidebar should be shown
const showSidebar = computed(() => {
    // Don't show sidebar on welcome page
    if (page.url === '/' && page.component === 'Welcome') {
        return false;
    }
    // Show sidebar on all other pages
    return true;
});

// Function to check if a category is active
const isCategoryActive = (categorySlug: string) => {
    // Check if we're on Categories/Show component and the URL matches
    if (page.component === 'Categories/Show') {
        // Try multiple ways to match the current category
        const currentUrl = page.url;
        const expectedUrl = `/categories/${categorySlug}`;

        // Direct URL comparison
        if (currentUrl === expectedUrl) {
            return true;
        }

        // Check if URL starts with the category path (handles query params)
        if (currentUrl.startsWith(expectedUrl)) {
            return true;
        }

        // Check route params if available
        if (page.props.ziggy?.route?.parameters?.category === categorySlug) {
            return true;
        }
    }

    return false;
};

// Navigation items with consistent route handling
const mainNavItems = [
    { href: '/', icon: Home, label: 'Home', routeName: 'home', component: 'Welcome' },
    { href: route('posts.index'), icon: FileText, label: 'Posts', routeName: 'posts.index', component: 'Posts/Index' },
    { href: route('tags.index'), icon: Tag, label: 'Tags', routeName: 'tags.index', component: 'Tags/Index' },
    {
        href: '/vulnerabilities',
        icon: ShieldAlert,
        label: 'Vulnerability Database',
        routeName: 'vulnerability.index',
        component: 'Vulnerabilities/Index',
    },
];

const myStuffItems = [
    { href: '/dashboard', icon: User, label: 'Dashboard', routeName: 'dashboard', component: 'Dashboard' },
    { href: '/feed', icon: Rss, label: 'Feed', routeName: 'feed', component: 'Feed' },
    { href: '/posts/create', icon: PlusCircle, label: 'Create Post', routeName: 'posts.create', component: 'Posts/Create' },
    { href: '/posts/me', icon: FileText, label: 'My Posts', routeName: 'posts.me', component: 'Posts/MyPosts' },
    // TODO: Route for user submitted vulns
];

// Helper function to check if navigation item is active

const quickActionItems = auth.user
    ? [{ href: '/help', icon: HelpCircle, label: 'Help & Support' }]
    : [
          { href: route('login'), icon: User, label: 'Sign In' },
          { href: route('register'), icon: PlusCircle, label: 'Sign Up' },
          { href: '/help', icon: HelpCircle, label: 'Help & Support' },
      ];

// Get top 5 categories for display
const popularCategories = siteData?.categories?.slice(0, 5) || [];

// Use the mobile sidebar state from props instead of local state
const toggleMobileSidebar = () => {
    emit('toggle-mobile');
};
</script>
<template>
    <!-- Mobile overlay -->
    <div v-if="showSidebar && props.showMobile" class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="toggleMobileSidebar" />

    <aside
        v-if="showSidebar"
        :class="[
            'fixed top-16 left-0 z-40 h-[calc(100vh-4rem)] w-70 transform transition-transform duration-300 ease-in-out',
            'border-r border-border bg-background',
            'lg:translate-x-0',
            props.showMobile ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
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
                        isSidebarItemActive(item)
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                    ]"
                    :href="item.href"
                    prefetch="click"
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
                            isSidebarItemActive(item)
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                        :href="item.href"
                        prefetch
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
                        :class="[
                            'flex items-center space-x-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            isCategoryActive(category.slug)
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                        :href="route('categories.show', category.slug)"
                        prefetch="click"
                    >
                        <div :style="{ backgroundColor: category.color }" class="h-3 w-3 flex-shrink-0 rounded-full" />
                        <span class="flex-1 truncate">{{ category.name }}</span>
                        <span v-if="category.post_count > 0" class="text-xs">
                            <Badge class="bg-accent text-accent-foreground">{{ category.post_count }}</Badge>
                        </span>
                    </Link>
                    <Link
                        :href="route('categories.index')"
                        class="flex items-center space-x-3 rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
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
                        prefetch="click"
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
                        route().current('profile.*') || route().current('settings.*') || route().current('password.*')
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                    ]"
                    :href="route('profile.edit')"
                    prefetch="click"
                >
                    <Settings class="h-4 w-4" />
                    <span>Settings</span>
                </Link>
            </div>
        </div>
    </aside>

    <!-- Mobile Sidebar Overlay -->
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
