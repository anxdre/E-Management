<script setup lang="ts">
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import {ChevronsRight, UsersRound} from "lucide-vue-next";
import {Button} from "@/shadcn/ui/button";
import {Head} from "@inertiajs/vue3";
import {navigateLink} from "@/lib/utils";

defineOptions({
    layout: LayoutWrapper
})
</script>

<template>
    <Head title="Verify Email"/>
    <section title="hero-1">
        <div class="w-full min-h-screen bg-primary flex justify-center items-center">
            <div class="w-full h-full bg-black flex flex-col justify-evenly p-12">
                <div class="flex flex-col">
                    <UsersRound :stroke-width="2" class="size-16 text-white"/>
                    <h1 class="text-white text-pretty tracking-tight">Teamway®</h1>
                    <h2 class="text-white text-pretty tracking-tight mt-4">Your Account Successfully verified</h2>
                </div>
                <div class="flex-col flex gap-4">
                    <p class="text-white">Now you can access more feature from our application & feel free to share with
                        us about your experience at <a class="underline italic" href="mailto:support@teamway.com">support@teamway.com</a>
                    </p>
                    <Button @click="navigateLink(route('home'))"
                            class="py-6 w-fit pl-4 pr-8 hover:bg-black hover:cursor-pointer  border border-black hover:border-primary font-semibold gap-4 align-middle">
                        <ChevronsRight class="size-10 animate-pulse"/>
                        <span class="text-lg">Let's start</span>
                    </Button>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>

</style>
