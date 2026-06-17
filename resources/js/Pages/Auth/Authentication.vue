<script setup lang="ts">
import {ChevronLeft} from "lucide-vue-next";
import {navigateLink} from "@/lib/utils";
import {ref} from "vue";
import {vAutoAnimate} from "@formkit/auto-animate";
import FormSignUp from "@/Pages/Auth/FormSignUp.vue";
import FormSignIn from "@/Pages/Auth/FormSignIn.vue";
import {Head} from "@inertiajs/vue3";
import {Toaster} from "@/shadcn/ui/toast";
import CustomLoader from "@/Components/CustomLoader.vue";

const isSignUp = ref(false)
</script>

<template>
    <Head>{{ isSignUp ? 'Sign Up' : 'Sign In' }}</Head>
    <CustomLoader/>
    <Toaster />
    <div class="w-full lg:grid lg:grid-cols-2 min-h-screen" v-auto-animate>
        <div v-if="!isSignUp" class="flex items-center justify-center py-12">
            <div class="mx-4 md:mx-auto grid w-[350px] gap-6">
                <div @click="navigateLink(route('home'))"
                     class="inline-flex w-fit pr-2 rounded-lg py-2 cursor-pointer hover:bg-black hover:text-white">
                    <ChevronLeft/>
                    Home
                </div>
                <div class="grid gap-2 text-center">
                    <h1 class="text-3xl font-bold">
                        Sign In
                    </h1>
                    <p class="text-muted-foreground">
                        Enter your email below to sign in to your account
                    </p>
                </div>
                <div class="grid gap-4">
                    <FormSignIn/>
                </div>
<!--                <div class="mt-4 text-center text-sm">-->
<!--                    Don't have an account?-->
<!--&lt;!&ndash;                    <span @click="isSignUp = true" class="underline cursor-pointer">&ndash;&gt;-->
<!--&lt;!&ndash;                        Sign up&ndash;&gt;-->
<!--&lt;!&ndash;                    </span>&ndash;&gt;-->
<!--                    <span>-->
<!--                        Contact your administrator-->
<!--                    </span>-->
<!--                </div>-->
            </div>
        </div>
        <div class="hidden bg-muted lg:block">
            <img
                src="../../../img/hero-1/banner-1.jpg"
                alt="Image"
                class="h-full w-full object-cover dark:brightness-[0.2] dark:grayscale  bg-gray-800"
            >
        </div>

        <div v-if="isSignUp" class="flex items-center justify-center py-12">
            <div class="mx-4 md:mx-auto grid w-[350px] gap-6">
                <div @click="navigateLink(route('home'))"
                     class="inline-flex w-fit pr-2 rounded-lg py-2 cursor-pointer hover:bg-black hover:text-white">
                    <ChevronLeft/>
                    Home
                </div>
                <div class="grid gap-2 text-center">
                    <h1 class="text-3xl font-bold">
                        Sign Up
                    </h1>
                    <p class=" text-muted-foreground">
                        Enter your email below to create to your company account
                    </p>
                </div>
                <div class="grid gap-4">
                    <FormSignUp @registered="isSignUp=false"/>
                </div>
                <div class="mt-4 text-center text-sm">
                    Already have an account?
                    <span @click="isSignUp = false" class="underline cursor-pointer">
                        Sign in
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
