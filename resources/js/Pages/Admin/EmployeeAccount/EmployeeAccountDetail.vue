<script setup lang="ts">
import { toTypedSchema } from "@vee-validate/zod";
import { z } from "zod";
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/shadcn/ui/form";
import { vAutoAnimate } from "@formkit/auto-animate";
import { useForm } from "vee-validate";
import { onMounted, ref, useAttrs } from "vue";
import { Input } from "@/shadcn/ui/input";
import { Button } from "@/shadcn/ui/button";
import { ChevronLeft, LoaderCircleIcon, MapPinCheck, NotepadText, PencilIcon } from "lucide-vue-next";
import axios from "axios";
import { cn, errorToast, navigateLink, PhoneRegex, successToast } from "@/lib/utils";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/shadcn/ui/card";
import { Avatar, AvatarFallback, AvatarImage } from "@/shadcn/ui/avatar";
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import { useFileDialog } from "@vueuse/core";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger, } from '@/shadcn/ui/tooltip'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/shadcn/ui/select";
import { ComboboxAnchor, ComboboxContent, ComboboxInput, ComboboxPortal, ComboboxRoot } from 'radix-vue'
import { CommandEmpty, CommandGroup, CommandItem, CommandList } from '@/shadcn/ui/command'
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete } from '@/shadcn/ui/tags-input'
import { Label } from "@/shadcn/ui/label";
import { router, usePage } from "@inertiajs/vue3";

defineOptions({
    layout: LayoutWrapper
})
const props = defineProps<{
    account?: any,
    companyGroup: any
}>()
const isLoading = ref(false)
const isEditing = ref(false)
const isCreate = ref(props.account == undefined)

const formSchema = toTypedSchema(z.object({
    id: z.number().nullish(),
    name: z.string({ required_error: "must be filled" }),
    email: z.string({ required_error: "must be filled" }),
    address: z.string({ required_error: "must be filled" }),
    phone: z.string({ required_error: "must be filled" }).min(8, "8 digit required")
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
    profile_picture: z.any()
        .refine((file: File) => file?.length !== 0, "File is required")
        .refine((file) => file?.size < 15000000, "Max size is 15MB.")
        .nullish(),
    is_suspended: z.string(),
    company_group: z.array(z.any()).nullish(),
    password: z.union([z.string().min(8), z.literal(''), z.null()]).optional(),
    password_confirmation: z.union([z.string().min(8), z.literal(''), z.null()]).optional()
}).refine((values) => {
    // Ensure passwords match if they are provided
    return !(values.password && values.password_confirmation && values.password !== values.password_confirmation);
}, {
    message: "Password didn't match",
    path: ['password_confirmation']
}))

const { handleSubmit, isFieldDirty, setErrors, setFieldValue, values } = useForm({
    validationSchema: formSchema, initialValues: {
        id: props.account?.id,
        name: props.account?.user_detail.fullname,
        email: props.account?.email,
        address: props.account?.user_detail.address,
        phone: props.account?.user_detail.phone,
        is_suspended: props.account?.is_suspended.toString(),
        profile_picture: props.account?.profile_picture,
        company_group: props.account?.groups,
    }
})

const onSubmit = handleSubmit((values, ctx) => {
    values.company_group = values.company_group?.map((item) => item.id)
    isLoading.value = true
    if (isCreate.value) {
        console.log('add')
        axios.postForm(route('employee-account.json.add'), values, {})
            .then(({ data: { data: responseData, message, status_code } }) => {
                successToast('Success', message)
                navigateLink(route('employee-account.detail', { id: responseData.id }))
            })
            .catch((error) => {
                setErrors(error.response.data.errors)
            })
            .finally(() => {
                isLoading.value = false
            })
        return
    }

    axios.postForm(route('employee-account.json.update'), values, {})
        .then(({ data: { data: responseData, message, status_code } }) => {
            successToast('Success', message)
            isCreate.value = false
            isEditing.value = false
        })
        .catch((error) => {
            setErrors(error.response.data.errors)
        })
        .finally(() => {
            isLoading.value = false
        })
})

const { files, open, reset, onCancel, onChange } = useFileDialog({
    accept: 'images/*',
    directory: false,
    multiple: false
})

const profileImage = ref()

onChange((files) => {
    if (!files || !files[0]) {
        return
    }

    if (!files[0].type.includes("image/")) {
        errorToast('Oops', 'sorry, invalid image file type')
        return
    }
    profileImage.value = URL.createObjectURL(files[0])
    setFieldValue('profile_picture', files[0])
})

onMounted(() => {
    console.log(props.account)
    if (props.account) {
        profileImage.value = `${import.meta.env.VITE_APP_URL}/storage/${props.account.user_detail.picture_profile}`;
    }
})

</script>

<template>
    <main class="grid flex-1 items-start gap-4 p-4 sm:px-6 md:gap-4">
        <Card class="overflow-scroll">
            <CardHeader>
                <CardTitle @click="navigateLink(route('employee-account.index'))"
                           class="inline-flex items-center gap-2">
                    <Button variant="ghost" class="w-fit">
                        <ChevronLeft/>
                    </Button>
                    {{ isCreate ? 'Create Employee Account' : 'Account Detail' }}
                </CardTitle>
                <CardDescription class="inline-flex justify-between items-center">
                    {{
                        isCreate ? 'Create your employee account and view their related information.' : ' Manage your employee account and view their related information.'
                    }}
                    <div v-if="!isCreate && !isEditing" class="ml-auto flex items-center gap-2">
                        <Button @click="isEditing = true" size="sm" class="h-7 gap-1 bg-black">
                            <PencilIcon class="h-3.5 w-3.5"/>
                            <span class="sr-only sm:not-sr-only sm:whitespace-nowrap">
                  Edit Employee Account
                </span>
                        </Button>
                    </div>
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col justify-center space-y-4 md:grid md:grid-cols-3 md:space-x-4">
                    <div class="flex flex-col items-center py-4 relative space-y-8">
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Avatar @click="()=>{(isEditing || isCreate) && open()}" class="size-64 md:mt-4"
                                            :class="isEditing || isCreate ? cn('cursor-pointer') : '' ">
                                        <AvatarImage :src="profileImage ?? ''"></AvatarImage>
                                        <AvatarImage :src="profileImage ?? ''"></AvatarImage>
                                        <AvatarFallback>Profile Picture</AvatarFallback>
                                    </Avatar>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <span>Change profile picture</span>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                        <span v-if="!isCreate && !isEditing" class="text-xs text-muted-foreground">*you must enter edit mode to change profile picture</span>
                        <span v-else
                              class="text-xs text-muted-foreground">*don't forget to save before leaving the page</span>
                        <div v-if="!isCreate && !isEditing " class="w-full flex flex-col items-center space-y-2">
                            <Button @click="navigateLink(route('employee-payroll.user.index',{user:$page.props.auth.user.id,employee:props.account.id}))" class="w-full gap-4">
                                <NotepadText/>
                                Manage Employee Salary
                            </Button>
                            <Button @click="router.get(route('employee-presence.index',{user:props.account.id}))"
                                    class="w-full gap-4">
                                <MapPinCheck/>
                                Manage Employee Presence
                            </Button>
                        </div>
                    </div>
                    <div class="outline outline-secondary rounded-md col-span-2 flex flex-col p-4">
                        <form class="space-y-4" @submit="onSubmit">
                            <FormField name="name" :validate-on-blur="!isFieldDirty" v-slot="{ componentField }">
                                <FormItem v-auto-animate>
                                    <FormLabel>Full Name</FormLabel>
                                    <FormControl>
                                        <Input :disabled="!isCreate && !isEditing"
                                               v-bind="componentField"
                                               type="text"
                                               autocomplete="name"
                                               placeholder="enter your employee name"
                                        />
                                    </FormControl>
                                    <FormMessage/>
                                </FormItem>
                            </FormField>

                            <FormField name="address" :validate-on-blur="!isFieldDirty" v-slot="{ componentField }">
                                <FormItem v-auto-animate>
                                    <FormLabel>Address</FormLabel>
                                    <FormControl>
                                        <Input
                                            :disabled="!isCreate && !isEditing"
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
                                            :disabled="!isCreate && !isEditing"
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
                                            :disabled="!isCreate && !isEditing"
                                            v-bind="componentField"
                                            type="tel"
                                            autocomplete="phone"
                                            placeholder="enter your phone number"
                                        />
                                    </FormControl>
                                    <FormMessage/>
                                </FormItem>
                            </FormField>

                            <FormField name="is_suspended" :validate-on-blur="!isFieldDirty"
                                       v-slot="{ componentField }">
                                <FormItem v-auto-animate>
                                    <FormLabel>Status</FormLabel>
                                    <FormControl>
                                        <Select v-bind="componentField">
                                            <SelectTrigger :disabled="!isCreate && !isEditing">
                                                <SelectValue placeholder="Select status"/>
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="0">Active</SelectItem>
                                                <SelectItem value="1">Suspended</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </FormControl>
                                    <FormMessage/>
                                </FormItem>
                            </FormField>

                            <FormField name="password" :validate-on-blur="!isFieldDirty" v-slot="{ componentField }">
                                <FormItem v-auto-animate>
                                    <FormLabel>Password</FormLabel>
                                    <FormControl>
                                        <Input
                                            :disabled="!isCreate && !isEditing"
                                            v-bind="componentField"
                                            type="password"
                                            placeholder="enter password min 8 character"
                                        />
                                    </FormControl>
                                    <FormMessage/>
                                </FormItem>
                            </FormField>

                            <FormField name="password_confirmation" :validate-on-blur="!isFieldDirty"
                                       v-slot="{ componentField }">
                                <FormItem v-auto-animate>
                                    <FormLabel>Re - type password</FormLabel>
                                    <FormControl>
                                        <Input
                                            :disabled="!isCreate && !isEditing"
                                            v-bind="componentField"
                                            type="password"
                                            placeholder="re - type your password"
                                        />
                                    </FormControl>
                                    <FormMessage/>
                                </FormItem>
                            </FormField>

                            <FormField name="company_group" :validate-on-blur="!isFieldDirty"
                                       v-slot="{ componentField }">
                                <FormItem v-auto-animate>
                                    <FormLabel>Company Group</FormLabel>
                                    <FormControl>
                                        <TagsInput class="px-0 gap-0" v-model="componentField.modelValue">
                                            <div class="flex gap-2 flex-wrap items-center px-3">
                                                <TagsInputItem
                                                    :class="!isCreate && !isEditing && cn('cursor-not-allowed')"
                                                    v-for="item in componentField.modelValue" :key="item"
                                                    :value="item">
                                                    <Label class="px-2">{{ item.name }}</Label>
                                                    <TagsInputItemDelete :disabled="!isCreate && !isEditing"
                                                                         :class="!isEditing && cn('cursor-not-allowed')"/>
                                                </TagsInputItem>
                                            </div>

                                            <ComboboxRoot class="w-full">
                                                <ComboboxAnchor as-child>
                                                    <ComboboxInput
                                                        :class="!isCreate && !isEditing && cn('cursor-not-allowed')"
                                                        :disabled="!isCreate && !isEditing"
                                                        placeholder="Select group..."
                                                        as-child>
                                                        <TagsInputInput
                                                            @keydown.enter.prevent
                                                            class="w-full px-3"
                                                            :class="componentField.modelValue?.length > 0 ? 'mt-2' : ''"/>
                                                    </ComboboxInput>
                                                </ComboboxAnchor>

                                                <ComboboxPortal>
                                                    <ComboboxContent>
                                                        <CommandList
                                                            position="popper"
                                                            class="w-[--radix-popper-anchor-width] rounded-md mt-2 border bg-popover text-popover-foreground shadow-md outline-none data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2"
                                                        >
                                                            <CommandEmpty/>
                                                            <CommandGroup>
                                                                <CommandItem
                                                                    v-for="companyGroup in props.companyGroup.filter(({id})=>{
                                                                        const modelValueSet = new Set(componentField.modelValue?.map(({ id }) => id));
                                                                        return !modelValueSet.has(id) || componentField.modelValue === null;
                                                                    })"
                                                                    :key="companyGroup.id" :value="companyGroup"
                                                                    @select="() => {
                                                                         if (!componentField.modelValue) {
                                                                            componentField.modelValue = [] }
                                                                        componentField.modelValue.push(companyGroup)
                                                                        setFieldValue('company_group', componentField.modelValue)
                                                                    }">
                                                                    {{ companyGroup.name }}
                                                                </CommandItem>
                                                            </CommandGroup>
                                                        </CommandList>
                                                    </ComboboxContent>
                                                </ComboboxPortal>
                                            </ComboboxRoot>
                                        </TagsInput>
                                    </FormControl>
                                    <FormMessage/>
                                </FormItem>
                            </FormField>

                            <Button v-if="isCreate || isEditing" :disabled="isLoading" type="submit"
                                    class="w-full gap-2">
                                Save
                                <LoaderCircleIcon v-if="isLoading" class="animate-spin"/>
                            </Button>
                        </form>
                    </div>
                </div>
            </CardContent>
            <CardFooter>
            </CardFooter>
        </Card>
    </main>
</template>

<style scoped>

</style>
