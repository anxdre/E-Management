<script setup lang="ts">

import {toast, Toaster} from "@/shadcn/ui/toast";
import {vAutoAnimate} from "@formkit/auto-animate";
import {Head, router} from "@inertiajs/vue3";
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from "@/shadcn/ui/card";
import {ChevronLeft, UsersRound} from "lucide-vue-next";
import {FormControl, FormField, FormItem, FormLabel, FormMessage} from "@/shadcn/ui/form";
import {Input} from "@/shadcn/ui/input";
import {useForm} from "vee-validate";
import {toTypedSchema} from "@vee-validate/zod";
import {z} from "zod";
import {Button} from "@/shadcn/ui/button";
import {navigateLink} from "@/lib/utils";
import {useGlobalLoaderStrore} from "@/lib/GlobalLoaderStore";
import CustomLoader from "@/Components/CustomLoader.vue";

const formSchema = toTypedSchema(z.object({
    email: z.string({required_error: 'This field is required'}).email()
}))
//vee-validate init
const {handleSubmit, isFieldDirty, setErrors, setFieldValue, values} = useForm({
    validationSchema: formSchema
})

const onSubmit = handleSubmit((values) => {
    router.post(route('password.send'), values, {
        onError: (err) => {
            setErrors(err)
        },
        onSuccess: () => {
            toast({
                title: 'Link has been successfuly delivered',
                description: 'Please check your email to reset your account password'
            })
        },
        onBefore: () => {
            useGlobalLoaderStrore().isLoading = true
            useGlobalLoaderStrore().darkenBg = true
        },
        onFinish: () => {
            useGlobalLoaderStrore().isLoading = false
            useGlobalLoaderStrore().darkenBg = false
        }
    })
})
</script>

<template>
    <Head>Forgot password</Head>
    <Toaster/>
    <CustomLoader/>
    <div class="min-w-full min-h-screen  content-center bg-black container p-0 lg:px-8">
        <div
            class="md:w-3/4 w-full p-8 py-24 lg:p-24 mx-auto h-fit md:min-h-screen content-center bg-primary space-y-12"
            v-auto-animate>
            <div @click="navigateLink(route('home'))" class="flex flex-col items-center w-full cursor-pointer">
                <UsersRound :stroke-width="2" class="size-16 text-white"/>
                <h1 class="text-white text-pretty tracking-tight">Teamway®</h1>
            </div>
            <Card>
                <CardHeader class="flex space-y-4">
                    <ChevronLeft @click="navigateLink(route('auth.sign-in'))"
                                 class="mr-4 hover:bg-secondary-foreground hover:text-white rounded border cursor-pointer"/>
                    <CardTitle class="border-l-4 ps-1 border-black">Forgot Password</CardTitle>
                </CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit="onSubmit">
                        <FormField name="email" v-slot="{componentField}" :validate-on-blur="!isFieldDirty">
                            <FormItem class="flex flex-col" v-auto-animate>
                                <FormLabel>Enter your email to reset password</FormLabel>
                                <div class="lg:inline-flex space-y-2 lg:space-y-0 lg:gap-2">
                                    <FormControl>
                                        <Input v-bind="componentField" placeholder="email" autocomplete="email"
                                               type="email"/>
                                    </FormControl>
                                    <Button class="font-semibold bg-black text-white">Send Reset Link</Button>
                                </div>
                                <FormMessage/>
                            </FormItem>
                        </FormField>
                    </form>
                    <CardDescription>We will send verification link to your email account to reset your password
                    </CardDescription>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
<style scoped>
.container {
    width: 100%;
    height: 100%;
    --s: 200px; /* control the size */
    --c1: #1d1d1d;
    --c2: #4e4f51;
    --c3: #3c3c3c;

    background: repeating-conic-gradient(
        from 30deg,
        #0000 0 120deg,
        var(--c3) 0 180deg
    ) calc(0.5 * var(--s)) calc(0.5 * var(--s) * 0.577),
    repeating-conic-gradient(
        from 30deg,
        var(--c1) 0 60deg,
        var(--c2) 0 120deg,
        var(--c3) 0 180deg
    );
    background-size: var(--s) calc(var(--s) * 0.577);
}

.anim-border-typewriter {
    animation: typewriter 800ms infinite alternate;
}

@keyframes typewriter {
    0% {
        border-color: transparent;
    }
    100% {
        border-color: black;
    }
}

</style>
