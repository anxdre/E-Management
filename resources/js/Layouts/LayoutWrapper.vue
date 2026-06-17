<script lang="ts" setup>
import {LoaderCircle} from "lucide-vue-next";
import Navbar from "@/Components/Navbar.vue";
import {useGlobalLoaderStrore} from "@/lib/GlobalLoaderStore";
import {Toaster} from "@/shadcn/ui/toast";
import {router} from "@inertiajs/vue3";
import { navigateLink } from "@/lib/utils";


const loader = useGlobalLoaderStrore()
</script>

<template>
    <Head>
        <title>Teamway</title>
    </Head>
    <div class="flex min-h-screen w-full flex-col relative">
        <Toaster/>
        <div class="flex-col flex sticky top-0">
            <header class="flex h-16 items-center gap-4 border-b bg-background px-4 md:px-6">
                <Navbar v-bind="$attrs"/>
            </header>
            <div v-if="$attrs.auth?.user && $attrs.auth?.user.email === 'admin@teamway.com'"
                 class="flex h-16 items-center justify-center gap-4 bg-primary px-4 md:px-6 animate-pulse text-white">Company Profile hasn't set up yet
                <span @click="()=>{navigateLink(route('employee-account.detail',{id:$attrs.auth?.user.id}))}" class="italic underline cursor-pointer">Configure Now</span>
            </div>
        </div>
        <div v-if="loader.isLoading" :class="{'bg-gray-800/50':loader.darkenBg}"
             class="fixed inset-0 flex flex-col justify-center items-center gap-2 z-50">
            <LoaderCircle :stroke-width="2.5" :size="64" class="animate-spin text-primary-foreground/80"/>
            <p class="text-primary-foreground/80 animate-pulse">Loading</p>
        </div>
        <transition mode="out-in" name="fade">
            <div :key="$page.component">
                <slot/>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s;
}

.fade-enter,
.fade-leave-to {
    opacity: 0;
}
</style>
