<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, Clock, Code, FileText, Lock, Search, Share2, Shield, TrendingUp, Upload, Users, Zap } from 'lucide-vue-next';
</script>

<template>
    <Head title="Home"></Head>

    <div class="min-h-screen">
        <!-- Navigation Header -->
        <header class="border-b border-white/20">
            <div class="container mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <Shield class="h-8 w-8 text-primary" />
                        <h1 class="text-xl font-bold">root.d</h1>
                    </div>
                    <NavigationMenu>
                        <NavigationMenuList>
                            <div v-if="$page.props.auth.user" class="space-x-2">
                                <NavigationMenuItem>
                                    <NavigationMenuLink :class="navigationMenuTriggerStyle()" href="/dashboard"> Dashboard </NavigationMenuLink>
                                </NavigationMenuItem>
                                <NavigationMenuItem>
                                    <NavigationMenuLink :class="navigationMenuTriggerStyle()" href="/scripts"> Scripts </NavigationMenuLink>
                                </NavigationMenuItem>
                                <NavigationMenuItem>
                                    <NavigationMenuLink :class="navigationMenuTriggerStyle()" href="/discussions"> Discussions </NavigationMenuLink>
                                </NavigationMenuItem>
                            </div>
                            <div v-else class="flex flex-row space-x-2">
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

        <!-- Hero Section -->
        <section class="px-6 py-8 md:py-20">
            <div class="container mx-auto">
                <div class="mx-auto max-w-4xl">
                    <h2 class="mb-6 text-left text-6xl font-bold">
                        root.d
                        <span
                            class="mt-1 block bg-gradient-to-r from-purple-400 to-purple-500 bg-clip-text text-left text-4xl text-transparent md:ml-10"
                        >
                            The Social Platform for Security</span
                        >
                    </h2>
                    <p class="mb-8 text-lg leading-relaxed text-muted-foreground">
                        Share files, scripts, and knowledge securely within your security team. Collaborate, learn, and strengthen your cybersecurity
                        arsenal together.
                    </p>
                    <div class="flex flex-col justify-center gap-4 sm:flex-row">
                        <Button asChild class="px-8 py-3" size="lg">
                            <Link :href="route('register')">
                                <Users class="mr-2 h-5 w-5" />
                                Join the Team
                            </Link>
                        </Button>
                        <Button asChild class="px-8 py-3" size="lg" variant="outline">
                            <Link href="/explore">
                                <Search class="mr-2 h-5 w-5" />
                                Explore Content
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Grid -->
        <section class="px-6 py-16">
            <div class="container mx-auto">
                <div class="mb-16 text-center">
                    <h3 class="mb-4 text-3xl font-bold">Everything Your Security Team Needs</h3>
                    <p class="mx-auto text-lg text-muted-foreground">
                        Built specifically for security professionals to share, collaborate, and grow together
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                    <!-- File Sharing -->
                    <Card class="group transition-all duration-200 hover:-translate-y-1 hover:shadow-lg">
                        <CardHeader>
                            <div class="flex items-center space-x-3">
                                <div
                                    class="rounded-lg bg-blue-100 p-2 transition-colors group-hover:bg-blue-200 dark:bg-blue-900/20 dark:group-hover:bg-blue-900/40"
                                >
                                    <Upload class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                </div>
                                <CardTitle>Secure File Sharing</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <CardDescription>
                                Upload and share security tools, reports, and documentation with your team securely. Version control and access
                                permissions included.
                            </CardDescription>
                        </CardContent>
                    </Card>

                    <!-- Script Repository -->
                    <Card class="group transition-all duration-200 hover:-translate-y-1 hover:shadow-lg">
                        <CardHeader>
                            <div class="flex items-center space-x-3">
                                <div
                                    class="rounded-lg bg-green-100 p-2 transition-colors group-hover:bg-green-200 dark:bg-green-900/20 dark:group-hover:bg-green-900/40"
                                >
                                    <Code class="h-6 w-6 text-green-600 dark:text-green-400" />
                                </div>
                                <CardTitle>Script Repository</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <CardDescription>
                                Share useful security scripts, automation tools, and code snippets. Syntax highlighting and collaborative editing
                                supported.
                            </CardDescription>
                        </CardContent>
                    </Card>

                    <!-- Knowledge Base -->
                    <Card class="group transition-all duration-200 hover:-translate-y-1 hover:shadow-lg">
                        <CardHeader>
                            <div class="flex items-center space-x-3">
                                <div
                                    class="rounded-lg bg-purple-100 p-2 transition-colors group-hover:bg-purple-200 dark:bg-purple-900/20 dark:group-hover:bg-purple-900/40"
                                >
                                    <BookOpen class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                                </div>
                                <CardTitle>Knowledge Base</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <CardDescription>
                                Build a comprehensive knowledge base of security procedures, incident responses, and best practices for your team.
                            </CardDescription>
                        </CardContent>
                    </Card>

                    <!-- Team Collaboration -->
                    <Card class="group transition-all duration-200 hover:-translate-y-1 hover:shadow-lg">
                        <CardHeader>
                            <div class="flex items-center space-x-3">
                                <div
                                    class="rounded-lg bg-orange-100 p-2 transition-colors group-hover:bg-orange-200 dark:bg-orange-900/20 dark:group-hover:bg-orange-900/40"
                                >
                                    <Share2 class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                                </div>
                                <CardTitle>Team Collaboration</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <CardDescription>
                                Comment, discuss, and collaborate on shared content. Stay connected with your security team members.
                            </CardDescription>
                        </CardContent>
                    </Card>

                    <!-- Advanced Search -->
                    <Card class="group transition-all duration-200 hover:-translate-y-1 hover:shadow-lg">
                        <CardHeader>
                            <div class="flex items-center space-x-3">
                                <div
                                    class="rounded-lg bg-teal-100 p-2 transition-colors group-hover:bg-teal-200 dark:bg-teal-900/20 dark:group-hover:bg-teal-900/40"
                                >
                                    <Zap class="h-6 w-6 text-teal-600 dark:text-teal-400" />
                                </div>
                                <CardTitle>Smart Search</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <CardDescription>
                                Find exactly what you need with advanced search capabilities. Search by content, tags, author, or date.
                            </CardDescription>
                        </CardContent>
                    </Card>

                    <!-- Security First -->
                    <Card class="group transition-all duration-200 hover:-translate-y-1 hover:shadow-lg">
                        <CardHeader>
                            <div class="flex items-center space-x-3">
                                <div
                                    class="rounded-lg bg-red-100 p-2 transition-colors group-hover:bg-red-200 dark:bg-red-900/20 dark:group-hover:bg-red-900/40"
                                >
                                    <Lock class="h-6 w-6 text-red-600 dark:text-red-400" />
                                </div>
                                <CardTitle>Security First</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <CardDescription>
                                Built with security in mind. End-to-end encryption, audit logs, and role-based access control protect your sensitive
                                data.
                            </CardDescription>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Recent Activity Section -->
        <section class="bg-accent/30 px-6 py-16">
            <div class="container mx-auto">
                <div class="mb-12 text-center">
                    <h3 class="mb-4 text-3xl font-bold">Latest Activity</h3>
                    <p class="text-lg text-muted-foreground">See what your team has been sharing recently</p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <!-- Sample Recent Items -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center space-x-2">
                                <FileText class="h-5 w-5 text-blue-600" />
                                <CardTitle class="text-lg">Network Scan Script</CardTitle>
                            </div>
                            <CardDescription class="flex items-center space-x-2">
                                <Clock class="h-4 w-4" />
                                <span>2 hours ago by Alex Chen</span>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm">Updated Python script for automated network vulnerability scanning...</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center space-x-2">
                                <BookOpen class="h-5 w-5 text-green-600" />
                                <CardTitle class="text-lg">Incident Response Guide</CardTitle>
                            </div>
                            <CardDescription class="flex items-center space-x-2">
                                <Clock class="h-4 w-4" />
                                <span>1 day ago by Sarah Johnson</span>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm">Comprehensive guide for handling security incidents and breaches...</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center space-x-2">
                                <Code class="h-5 w-5 text-purple-600" />
                                <CardTitle class="text-lg">Log Analysis Tool</CardTitle>
                            </div>
                            <CardDescription class="flex items-center space-x-2">
                                <Clock class="h-4 w-4" />
                                <span>3 days ago by Mike Rodriguez</span>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm">Bash script for parsing and analyzing security logs...</p>
                        </CardContent>
                    </Card>
                </div>

                <div class="mt-8 text-center">
                    <Button asChild variant="outline">
                        <Link href="/activity">
                            <TrendingUp class="mr-2 h-4 w-4" />
                            View All Activity
                        </Link>
                    </Button>
                </div>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="px-6 py-20">
            <div class="container mx-auto text-center">
                <div class="mx-auto max-w-4xl">
                    <h3 class="mb-4 text-3xl font-bold">Get Sharing Now</h3>
                    <p class="mb-8 text-lg text-muted-foreground">Join your security team and start sharing those awesome scripts.</p>
                    <Button as-child size="lg">
                        <Link :href="route('register')">
                            <Shield class="mr-2 h-5 w-5" />
                            Create Account
                        </Link>
                    </Button>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t px-6 py-8 backdrop-blur">
            <div class="container mx-auto">
                <div class="flex flex-col items-center justify-between md:flex-row">
                    <div class="mb-4 flex items-center space-x-2 md:mb-0">
                        <Shield class="h-6 w-6 text-lime-400" />
                        <span class="text-lg font-semibold text-slate-900 dark:text-white">root.d</span>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        © 2025 root.d. Secure knowledge sharing and social platform for our UKG security teams.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
