<script lang="ts" setup>
import { LoaderCircle } from "lucide-vue-next";
import Navbar from "@/Components/Navbar.vue";
import { useGlobalLoaderStrore } from "@/lib/GlobalLoaderStore";

const loader = useGlobalLoaderStrore()
</script>

<template>
    <div class="flex min-h-screen w-full flex-col relative">
        <header class="sticky top-0 flex h-16 items-center gap-4 border-b bg-background px-4 md:px-6">
            <Navbar/>
        </header>
        <div v-if="loader.isLoading" :class="{'bg-gray-800/50':loader.darkenBg}"
             class="fixed inset-0 flex flex-col justify-center items-center gap-2 z-50">
            <LoaderCircle :stroke-width="2.5" :size="64" class="animate-spin text-primary-foreground/80"/>
            <p class="text-primary-foreground/80 animate-pulse">Loading</p>
        </div>
        <transition name="fade">
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
