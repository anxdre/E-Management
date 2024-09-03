<script setup lang="ts">

import {toast, Toaster} from "@/shadcn/ui/toast";
import {vAutoAnimate} from "@formkit/auto-animate";
import {Head, router} from "@inertiajs/vue3";
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from "@/shadcn/ui/card";
import {UsersRound} from "lucide-vue-next";
import {FormControl, FormField, FormItem, FormLabel, FormMessage} from "@/shadcn/ui/form";
import {Input} from "@/shadcn/ui/input";
import {useForm} from "vee-validate";
import {toTypedSchema} from "@vee-validate/zod";
import {z} from "zod";
import {Button} from "@/shadcn/ui/button";
import {navigateLink} from "@/lib/utils";
import {useGlobalLoaderStrore} from "@/lib/GlobalLoaderStore";
import {ref, useAttrs} from "vue";
import CustomLoader from "@/Components/CustomLoader.vue";

const parentAttrs = useAttrs()
const initialValues = {
    email: parentAttrs.email?.toString(),
    token: parentAttrs.token?.toString(),
    password: undefined,
    password_confirmation: undefined
}
const isValid = ref(true)

const formSchema = toTypedSchema(z.object({
    email: z.string({required_error: "must be filled"}).nullish(),
    token: z.string({required_error: "must be filled"}).nullish(),
    password: z.string({required_error: "must be filled"}).min(8),
    password_confirmation: z.string({required_error: "must be filled"}).min(8)
}).refine((values) => values.password === values.password_confirmation, {
    message: "Password didn't match",
    path: ['password_confirmation', 'password']
}))
//vee-validate init
const {handleSubmit, isFieldDirty, setErrors, setFieldValue, values} = useForm({
    validationSchema: formSchema, initialValues
})

const onSubmit = handleSubmit((values) => {
    router.patch(route('password.update'), values, {
        onError: (err) => {
            setErrors(err)
            if (err.email) {
                toast({
                    title: err.email,
                    description: 'you can try to get new token from forgot password page',
                    variant: 'destructive'
                })
                isValid.value = false
                return
            }
            toast({
                title: 'Oops, Something Wrong',
                description: 'Please try to reload the page or try again later',
                variant: 'destructive'
            })
        },
        onSuccess: () => {
            toast({
                title: 'Password successfully changed',
                description: 'Now you can login with your new password'
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
    <Head>New password</Head>
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
                    <CardTitle class="border-l-4 ps-1 border-black">Create New Password</CardTitle>
                    <CardDescription>Create new password for <b>{{ $attrs.email }}</b> account</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit="onSubmit">
                        <FormField name="password" v-slot="{componentField}" :validate-on-blur="!isFieldDirty">
                            <FormItem class="flex flex-col" v-auto-animate>
                                <FormLabel>Enter your new password</FormLabel>
                                <div class="lg:inline-flex space-y-2 lg:space-y-0 lg:gap-2">
                                    <FormControl>
                                        <Input v-bind="componentField" placeholder="new password"
                                               autocomplete="password"
                                               type="password"/>
                                    </FormControl>
                                </div>
                                <FormMessage/>
                            </FormItem>
                        </FormField>
                        <FormField name="password_confirmation" v-slot="{componentField}"
                                   :validate-on-blur="!isFieldDirty">
                            <FormItem class="flex flex-col" v-auto-animate>
                                <FormLabel>Confirm your new password</FormLabel>
                                <div class="lg:inline-flex space-y-2 lg:space-y-0 lg:gap-2">
                                    <FormControl>
                                        <Input v-bind="componentField" placeholder="confirm new password"
                                               autocomplete="password"
                                               type="password"/>
                                    </FormControl>
                                </div>
                                <FormMessage/>
                            </FormItem>
                        </FormField>
                        <Button class="font-semibold bg-black text-white">Create New Password</Button>
                    </form>
                    <CardDescription v-if="isValid">🔒 Don't forget to make strong password with combination special
                        character or
                        number
                    </CardDescription>
                    <CardDescription class="text-red-500" v-else>❌ Your token not longer valid, please to create another
                        request <span
                            @click.prevent="navigateLink(route('password.forgot'))"
                            class="underline italic font-semibold cursor-pointer">right here</span></CardDescription>
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
