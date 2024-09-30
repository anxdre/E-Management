<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/shadcn/ui/dialog";
import { toTypedSchema } from "@vee-validate/zod";
import { z } from "zod";
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/shadcn/ui/form";
import { vAutoAnimate } from "@formkit/auto-animate";
import { useForm } from "vee-validate";
import { ref } from "vue";
import { Input } from "@/shadcn/ui/input";
import { Button } from "@/shadcn/ui/button";
import { LoaderCircleIcon } from "lucide-vue-next";
import axios from "axios";
import { PhoneRegex } from "@/lib/utils";

const isShowing = defineModel<Boolean>('isShowing', { required: true })
const emit = defineEmits<{
    (e: 'successCreated', data: any): void
}>()
const props = defineProps<{
    errors?: any
}>()
const isLoading = ref(false)

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

const { handleSubmit, isFieldDirty, setErrors, setFieldValue, values } = useForm({
    validationSchema: formSchema,
})

const onSubmit = handleSubmit((values, ctx) => {
    isLoading.value = true
    axios.post(route('employee-account.json.add'), values, {}).then(() => {
        emit('successCreated', values)
        isShowing.value = false
    }).catch((error) => {
        setErrors(error.response.data.errors)
    }).finally(() => {
        isLoading.value = false
    })
})
</script>

<template>
    <Dialog v-model:open="isShowing">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Create Employee Account</DialogTitle>
                <DialogDescription>
                    Make new account of employee here. Click save when you're done.
                </DialogDescription>
            </DialogHeader>
            <form class="space-y-4" @submit.prevent="onSubmit">
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
        </DialogContent>
    </Dialog>
</template>

<style scoped>

</style>
