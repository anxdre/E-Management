<script setup lang="ts">
import {Input} from "@/shadcn/ui/input";
import {Button} from "@/shadcn/ui/button";
import {z} from "zod";
import {FormControl, FormField, FormItem, FormLabel, FormMessage} from '@/shadcn/ui/form'
import {useForm} from "vee-validate";
import {vAutoAnimate} from "@formkit/auto-animate";
import {toTypedSchema} from "@vee-validate/zod";
import {router} from "@inertiajs/vue3";
import {toast} from "@/shadcn/ui/toast";
import {useGlobalLoaderStrore} from "@/lib/GlobalLoaderStore";
import { PhoneRegex } from "@/lib/utils";

const emit = defineEmits<{
    registered: [value: boolean]
}>()

const formSchema = toTypedSchema(z.object({
    name: z.string({required_error: "must be filled"}),
    email: z.string({required_error: "must be filled"}),
    address: z.string({required_error: "must be filled"}),
    phone: z.string({required_error: "must be filled"}).min(8, "8 digit required")
        .regex(PhoneRegex, 'wrong phone number format')
        .transform(value => {
            // Menghapus karakter +, -, dan spasi
            let transformedValue = value.replace(/[+\-\s]/g, '');
            // Mengganti awalan +62 menjadi 0
            if (transformedValue.startsWith('62')) {
                transformedValue = '0' + transformedValue.slice(2);
            }
            return transformedValue;
        }),
    password: z.string({required_error: "must be filled"}).min(8),
    password_confirmation: z.string({required_error: "must be filled"}).min(8)
}).refine((values) => values.password === values.password_confirmation, {
    message: "Password didn't match",
    path: ['password', 'password_confirmation']
}))


//vee-validate init
const {handleSubmit, isFieldDirty, setErrors, setFieldValue, values} = useForm({
    validationSchema: formSchema
})

const onSubmit = handleSubmit((values) => {
    router.post(route('auth.sign-up'), values, {
        onSuccess: () => {
            emit('registered', true)
            toast({
                title: 'Success sign up',
                description: 'please check your email to verify your account',
                duration: 3000
            })
        },
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
    <form class="space-y-4" @submit="onSubmit">
        <FormField name="name" :validate-on-blur="!isFieldDirty" v-slot="{ componentField }">
            <FormItem v-auto-animate>
                <FormLabel>Company Name</FormLabel>
                <FormControl>
                    <Input
                        v-bind="componentField"
                        type="text"
                        autocomplete="company"
                        placeholder="enter your company name"
                    />
                </FormControl>
                <FormMessage/>
            </FormItem>
        </FormField>

        <FormField name="address" :validate-on-blur="!isFieldDirty" v-slot="{ componentField }">
            <FormItem v-auto-animate>
                <FormLabel>Company Address</FormLabel>
                <FormControl>
                    <Input
                        v-bind="componentField"
                        type="text"
                        autocomplete="address"
                        placeholder="enter your company address"
                    />
                </FormControl>
                <FormMessage/>
            </FormItem>
        </FormField>

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

        <FormField name="phone" :validate-on-blur="!isFieldDirty" v-slot="{ componentField }">
            <FormItem v-auto-animate>
                <FormLabel>Phone number</FormLabel>
                <FormControl>
                    <Input
                        v-bind="componentField"
                        type="tel"
                        autocomplete="phone"
                        placeholder="enter your phone number"
                    />
                </FormControl>
                <FormMessage/>
            </FormItem>
        </FormField>

        <FormField name="password" :validate-on-blur="!isFieldDirty" v-slot="{ componentField }">
            <FormItem v-auto-animate>
                <FormLabel>Password</FormLabel>
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

        <FormField name="password_confirmation" :validate-on-blur="!isFieldDirty" v-slot="{ componentField }">
            <FormItem v-auto-animate>
                <FormLabel>Re - type password</FormLabel>
                <FormControl>
                    <Input
                        v-bind="componentField"
                        type="password"
                        placeholder="re - type your password"
                        autocomplete="current-password"
                    />
                </FormControl>
                <FormMessage/>
            </FormItem>
        </FormField>

        <Button type="submit" class="w-full">
            Sign Up
        </Button>
    </form>
</template>

<style scoped>

</style>
