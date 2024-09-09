<script setup lang="ts">

import {Input} from "@/shadcn/ui/input";
import {Button} from "@/shadcn/ui/button";
import {toTypedSchema} from "@vee-validate/zod";
import {z} from "zod";
import {FormControl, FormField, FormItem, FormLabel, FormMessage} from "@/shadcn/ui/form";
import {useForm} from "vee-validate";
import {vAutoAnimate} from "@formkit/auto-animate";
import {router} from "@inertiajs/vue3";
import {useGlobalLoaderStrore} from "@/lib/GlobalLoaderStore";
import {navigateLink} from "@/lib/utils";


const formSchema = toTypedSchema(z.object({
    email: z.string({required_error: "must be filled"}),
    password: z.string({required_error: "must be filled"}).min(8),
}))

//vee-validate init
const {handleSubmit, isFieldDirty, setErrors, setFieldValue, values} = useForm({
    validationSchema: formSchema
})

const onSubmit = handleSubmit((values) => {
    router.post(route('auth.sign-in'), values, {
        onError: (err) => {
            setErrors(err)
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
    <form class="space-y-4" @submit.prevent="onSubmit">
        <FormField name="email" :validate-on-blur="!isFieldDirty" v-slot="{ componentField }">
            <FormItem v-auto-animate>
                <FormLabel>Email Address</FormLabel>
                <FormControl>
                    <Input
                        v-bind="componentField"
                        type="email"
                        autocomplete="email"
                        placeholder="enter your email company"
                    />
                </FormControl>
                <FormMessage/>
            </FormItem>
        </FormField>

        <FormField name="password" :validate-on-blur="!isFieldDirty" v-slot="{ componentField }">
            <FormItem v-auto-animate>
                <FormLabel class="inline-flex justify-between w-full items-center">Password
                    <span @click="navigateLink(route('password.forgot'))"
                          class="ml-auto inline-block text-sm underline cursor-pointer">
                        Forgot your password?
                    </span>
                </FormLabel>
                <FormControl>
                    <Input
                        v-bind="componentField"
                        type="password"
                        placeholder="enter password min 8 character"
                        autocomplete="current-password"
                    />
                </FormControl>
                <FormMessage/>
            </FormItem>
        </FormField>

        <Button type="submit" class="w-full">
            Sign In
        </Button>
    </form>
</template>

<style scoped>

</style>
