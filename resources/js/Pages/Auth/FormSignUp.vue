<script setup lang="ts">
import {Input} from "@/shadcn/ui/input";
import {Button} from "@/shadcn/ui/button";
import {z} from "zod";
import {FormControl, FormField, FormItem, FormLabel, FormMessage} from '@/shadcn/ui/form'
import {useForm} from "vee-validate";
import {vAutoAnimate} from "@formkit/auto-animate";
import {toTypedSchema} from "@vee-validate/zod";
import {toRaw} from "vue";


const phoneRegex = new RegExp(
    /^([+]?[\s0-9]+)?(\d{3}|[(]?[0-9]+[)])?([-]?[\s]?[0-9])+$/
);

const formSchema = toTypedSchema(z.object({
    name: z.string({required_error: "must be filled"}),
    email: z.string({required_error: "must be filled"}),
    address: z.string({required_error: "must be filled"}),
    phone: z.string({required_error: "must be filled"}).min(8, "8 digit required")
        .regex(phoneRegex, 'wrong phone number format')
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
    re_password: z.string({required_error: "must be filled"}).min(8)
}).refine((values) => values.password === values.re_password, {
    message: "Password didn't match",
    path: ['password', 're_password']
}))


//vee-validate init
const {handleSubmit, isFieldDirty, setErrors, setFieldValue, values} = useForm({
    validationSchema: formSchema
})

const onSubmit = handleSubmit((values) => {
    console.log('Form submitted!', values)
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

        <FormField name="re_password" :validate-on-blur="!isFieldDirty" v-slot="{ componentField }">
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

        <Button @click="console.log(toRaw(values))" type="submit" class="w-full">
            Sign Up
        </Button>
    </form>
</template>

<style scoped>

</style>
