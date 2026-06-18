<script setup lang="ts">
import { toTypedSchema } from "@vee-validate/zod";
import { z } from "zod";
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/shadcn/ui/form";
import { vAutoAnimate } from "@formkit/auto-animate";
import { reactive } from "vue";
import { useForm } from "vee-validate";
import { onMounted, ref } from "vue";
import { Input } from "@/shadcn/ui/input";
import { Button } from "@/shadcn/ui/button";
import { Checkbox } from "@/shadcn/ui/checkbox";
import { ChevronLeft, LoaderCircleIcon, PencilIcon, SaveIcon } from "lucide-vue-next";
import axios from "axios";
import { cn, errorToast, navigateLink, successToast } from "@/lib/utils";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/shadcn/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/shadcn/ui/select";
import { Avatar, AvatarFallback, AvatarImage } from "@/shadcn/ui/avatar";
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import { useFileDialog } from "@vueuse/core";
import { usePage } from "@inertiajs/vue3";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/shadcn/ui/tooltip'

defineOptions({
    layout: LayoutWrapper
})

const props = defineProps<{
    profile?: any
}>()

const isLoading = ref(false)
const isEditing = ref(false)

const formSchema = toTypedSchema(z.object({
    company_name: z.string({ required_error: "must be filled" }),
    company_email: z.string().email().optional().or(z.literal('')),
    company_address: z.string().optional().or(z.literal('')),
    company_phone: z.string().optional().or(z.literal('')),
    npwp: z.string().optional().or(z.literal('')),
    company_logo: z.any()
        .refine((file: File) => file?.length !== 0, "File is required")
        .refine((file) => file?.size < 15000000, "Max size is 15MB.")
        .nullish(),
    auto_approve: z.boolean().optional(),
    auto_approve_mode: z.enum(['realtime', 'cron']).optional(),
    auto_approve_batch_hour: z.string().optional(),
    auto_approve_min_duration: z.number({coerce: true}).int().min(0).optional(),
    auto_approve_duplicate_coords: z.boolean().optional(),
}))

const { handleSubmit, setFieldValue, setErrors } = useForm({
    validationSchema: formSchema,
    initialValues: {
        company_name: props.profile?.company_name ?? '',
        company_email: props.profile?.company_email ?? '',
        company_address: props.profile?.company_address ?? '',
        company_phone: props.profile?.company_phone ?? '',
        npwp: props.profile?.npwp ?? '',
        auto_approve: props.profile?.auto_approve ?? false,
        auto_approve_mode: props.profile?.auto_approve_mode ?? 'realtime',
        auto_approve_batch_hour: props.profile?.auto_approve_batch_hour
            ? String(props.profile.auto_approve_batch_hour).substring(0, 5)
            : '17:00',
        auto_approve_min_duration: props.profile?.auto_approve_min_duration ?? 60,
        auto_approve_duplicate_coords: props.profile?.auto_approve_duplicate_coords ?? false,
    }
})

const onSubmit = handleSubmit((values, ctx) => {
    isLoading.value = true
    axios.postForm(route('company-settings.json.update'), values, {})
        .then(({ data: { data: responseData, message, status_code } }) => {
            successToast('Success', message)
            isEditing.value = false
        })
        .catch((error) => {
            setErrors(error.response.data.errors)
        })
        .finally(() => {
            isLoading.value = false
        })
})

// Email change form (reactive, avoid vee-validate multi-form conflict)
const currentEmail = ref(usePage().props.auth.user.email)
const emailLoading = ref(false)
const isEmailEditing = ref(false)
const emailForm = reactive({ email: currentEmail.value, current_password: '' })
const emailErrors = reactive<Record<string, string>>({})

function submitEmail() {
    emailErrors.email = ''
    emailErrors.current_password = ''
    let valid = true
    if (!emailForm.email) { emailErrors.email = 'must be filled'; valid = false }
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailForm.email)) { emailErrors.email = 'invalid email'; valid = false }
    if (!emailForm.current_password) { emailErrors.current_password = 'must be filled'; valid = false }
    if (!valid) return

    emailLoading.value = true
    axios.post(route('company-settings.json.change-email'), {
        email: emailForm.email,
        current_password: emailForm.current_password,
    })
        .then(({ data: { message } }) => {
            successToast('Success', message)
            isEmailEditing.value = false
            currentEmail.value = emailForm.email
        })
        .catch(err => {
            if (err.response?.data?.errors) Object.assign(emailErrors, err.response.data.errors)
        })
        .finally(() => { emailLoading.value = false })
}

function cancelEmailEdit() {
    isEmailEditing.value = false
    emailForm.email = currentEmail.value
    emailForm.current_password = ''
    emailErrors.email = ''
    emailErrors.current_password = ''
}

// Password change form (reactive, avoid vee-validate multi-form conflict)
const passwordLoading = ref(false)
const isPasswordEditing = ref(false)
const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' })
const passwordErrors = reactive<Record<string, string>>({})

function submitPassword() {
    passwordErrors.current_password = ''
    passwordErrors.password = ''
    passwordErrors.password_confirmation = ''
    let valid = true
    if (!passwordForm.current_password) { passwordErrors.current_password = 'must be filled'; valid = false }
    if (!passwordForm.password) { passwordErrors.password = 'must be filled'; valid = false }
    else if (passwordForm.password.length < 8) { passwordErrors.password = 'min 8 characters'; valid = false }
    if (!passwordForm.password_confirmation) { passwordErrors.password_confirmation = 'must be filled'; valid = false }
    else if (passwordForm.password !== passwordForm.password_confirmation) { passwordErrors.password_confirmation = 'passwords do not match'; valid = false }
    if (!valid) return

    passwordLoading.value = true
    axios.post(route('company-settings.json.change-password'), {
        current_password: passwordForm.current_password,
        password: passwordForm.password,
        password_confirmation: passwordForm.password_confirmation,
    })
        .then(({ data: { message } }) => {
            successToast('Success', message)
            isPasswordEditing.value = false
            passwordForm.current_password = ''
            passwordForm.password = ''
            passwordForm.password_confirmation = ''
        })
        .catch(err => {
            if (err.response?.data?.errors) Object.assign(passwordErrors, err.response.data.errors)
        })
        .finally(() => { passwordLoading.value = false })
}

function cancelPasswordEdit() {
    isPasswordEditing.value = false
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    passwordErrors.current_password = ''
    passwordErrors.password = ''
    passwordErrors.password_confirmation = ''
}

const { files, open, reset, onCancel, onChange } = useFileDialog({
    accept: 'images/*',
    directory: false,
    multiple: false
})

const logoImage = ref()

onChange((files) => {
    if (!files || !files[0]) return

    if (!files[0].type.includes("image/")) {
        errorToast('Oops', 'invalid image file type')
        return
    }
    logoImage.value = URL.createObjectURL(files[0])
    setFieldValue('company_logo', files[0])
})

onMounted(() => {
    if (props.profile?.company_logo) {
        logoImage.value = `${import.meta.env.VITE_APP_URL}/storage/${props.profile.company_logo}`;
    }
})
</script>

<template>
    <main class="grid flex-1 items-start gap-4 p-4 sm:px-6 md:gap-4">
        <Card class="overflow-scroll">
            <CardHeader>
                <CardTitle @click="navigateLink(route('company-settings.index'))"
                           class="inline-flex items-center gap-2">
                    <Button variant="ghost" class="w-fit">
                        <ChevronLeft/>
                    </Button>
                    Company Settings
                </CardTitle>
                <CardDescription class="inline-flex justify-between items-center">
                    Manage your company profile and information.
                    <div v-if="!isEditing" class="ml-auto flex items-center gap-2">
                        <Button @click="isEditing = true" size="sm" class="h-7 gap-1 bg-black">
                            <PencilIcon class="h-3.5 w-3.5"/>
                            <span class="sr-only sm:not-sr-only sm:whitespace-nowrap">Edit Company Profile</span>
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
                                    <Avatar @click="isEditing && open()" class="size-64 md:mt-4"
                                            :class="isEditing ? cn('cursor-pointer') : ''">
                                        <AvatarImage :src="logoImage ?? ''"></AvatarImage>
                                        <AvatarFallback>Company Logo</AvatarFallback>
                                    </Avatar>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <span>Change Company Logo</span>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                        <span v-if="!isEditing" class="text-xs text-muted-foreground">*enter edit mode to change logo</span>
                        <span v-else class="text-xs text-muted-foreground">*don't forget to save before leaving</span>
                    </div>
                    <div class="outline outline-secondary rounded-md col-span-2 flex flex-col p-4">
                        <form class="space-y-4" @submit="onSubmit">
                            <FormField name="company_name" v-slot="{ componentField }">
                                <FormItem v-auto-animate>
                                    <FormLabel>Company Name</FormLabel>
                                    <FormControl>
                                        <Input :disabled="!isEditing"
                                               v-bind="componentField"
                                               type="text"
                                               placeholder="enter company name"
                                        />
                                    </FormControl>
                                    <FormMessage/>
                                </FormItem>
                            </FormField>

                            <FormField name="company_address" v-slot="{ componentField }">
                                <FormItem v-auto-animate>
                                    <FormLabel>Address</FormLabel>
                                    <FormControl>
                                        <Input :disabled="!isEditing"
                                               v-bind="componentField"
                                               type="text"
                                               placeholder="enter company address"
                                        />
                                    </FormControl>
                                    <FormMessage/>
                                </FormItem>
                            </FormField>

                            <FormField name="company_email" v-slot="{ componentField }">
                                <FormItem v-auto-animate>
                                    <FormLabel>Email Address</FormLabel>
                                    <FormControl>
                                        <Input :disabled="!isEditing"
                                               v-bind="componentField"
                                               type="email"
                                               placeholder="enter company email"
                                        />
                                    </FormControl>
                                    <FormMessage/>
                                </FormItem>
                            </FormField>

                            <FormField name="company_phone" v-slot="{ componentField }">
                                <FormItem v-auto-animate>
                                    <FormLabel>Phone Number</FormLabel>
                                    <FormControl>
                                        <Input :disabled="!isEditing"
                                               v-bind="componentField"
                                               type="tel"
                                               placeholder="enter company phone number"
                                        />
                                    </FormControl>
                                    <FormMessage/>
                                </FormItem>
                            </FormField>

                            <FormField name="npwp" v-slot="{ componentField }">
                                <FormItem v-auto-animate>
                                    <FormLabel>NPWP</FormLabel>
                                    <FormControl>
                                        <Input :disabled="!isEditing"
                                               v-bind="componentField"
                                               type="text"
                                               placeholder="enter NPWP"
                                        />
                                    </FormControl>
                                    <FormMessage/>
                                </FormItem>
                            </FormField>

                            <!-- Auto Approval Settings -->
                            <div class="border-t pt-4 mt-6">
                                <h3 class="font-semibold mb-1">Auto Approval</h3>
                                <p class="text-sm text-muted-foreground mb-4">
                                    Automatically approve presence records that pass all configured checks.
                                    Employees with clean attendance get approved instantly; anomalous records stay pending for manual review.
                                </p>

                                <FormField name="auto_approve" v-slot="{ value, handleChange }">
                                    <FormItem class="flex flex-row items-center gap-2 space-y-0">
                                        <FormControl>
                                            <Checkbox :disabled="!isEditing" :checked="value" @update:checked="handleChange" id="auto_approve"/>
                                        </FormControl>
                                        <FormLabel for="auto_approve" class="cursor-pointer">Enable Auto Approval</FormLabel>
                                    </FormItem>
                                    <p class="text-xs text-muted-foreground mt-1 ml-6">When enabled, presences matching all criteria below will be auto-approved.</p>
                                </FormField>

                                <FormField name="auto_approve_mode" v-slot="{ value, handleChange }">
                                    <FormItem class="mt-3">
                                        <FormLabel>Mode</FormLabel>
                                        <FormControl>
                                            <Select :disabled="!isEditing" :model-value="value" @update:model-value="handleChange">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select mode"/>
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="realtime">Realtime (approve at checkout)</SelectItem>
                                                    <SelectItem value="cron">Cron (batch at set hour)</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </FormControl>
                                        <p class="text-xs text-muted-foreground mt-1">Realtime: approves at the moment of checkout. Cron: batch-approves all eligible records at a set hour daily.</p>
                                        <FormMessage/>
                                    </FormItem>
                                </FormField>

                                <FormField name="auto_approve_batch_hour" v-slot="{ componentField }">
                                    <FormItem class="mt-3">
                                        <FormLabel>Batch Hour (cron mode)</FormLabel>
                                        <FormControl>
                                            <Input :disabled="!isEditing" v-bind="componentField" type="time" placeholder="17:00"/>
                                        </FormControl>
                                        <p class="text-xs text-muted-foreground mt-1">Only used in Cron mode. Recommended: set to end of work hours.</p>
                                        <FormMessage/>
                                    </FormItem>
                                </FormField>

                                <FormField name="auto_approve_min_duration" v-slot="{ componentField }">
                                    <FormItem class="mt-3">
                                        <FormLabel>Min Work Duration (minutes)</FormLabel>
                                        <FormControl>
                                            <Input :disabled="!isEditing" v-bind="componentField" type="number" min="0" placeholder="60"/>
                                        </FormControl>
                                        <p class="text-xs text-muted-foreground mt-1">Minimum minutes between time-in and time-out to qualify for auto-approval. Set 0 to disable this check. This is independent from each location's working hours.</p>
                                        <FormMessage/>
                                    </FormItem>
                                </FormField>

                                <FormField name="auto_approve_duplicate_coords" v-slot="{ value, handleChange }">
                                    <FormItem class="flex flex-row items-center gap-2 space-y-0 mt-3">
                                        <FormControl>
                                            <Checkbox :disabled="!isEditing" :checked="value" @update:checked="handleChange" id="auto_approve_duplicate_coords"/>
                                        </FormControl>
                                        <FormLabel for="auto_approve_duplicate_coords" class="cursor-pointer">Check duplicate GPS coordinates (last 7 days)</FormLabel>
                                    </FormItem>
                                    <p class="text-xs text-muted-foreground mt-1 ml-6">Prevents auto-approval if the exact same coordinates appear in the last 7 days (indicates GPS spoofing).</p>
                                </FormField>
                            </div>

                            <Button v-if="isEditing" :disabled="isLoading" type="submit"
                                    class="w-full gap-2 mt-4">
                                <SaveIcon class="h-4 w-4"/>
                                Save
                                <LoaderCircleIcon v-if="isLoading" class="animate-spin"/>
                            </Button>
                        </form>
                    </div>
                </div>
            </CardContent>
            <CardFooter/>
        </Card>

        <!-- Security: Email + Password -->
        <Card>
            <CardHeader>
                <CardTitle class="inline-flex items-center gap-2">
                    Security
                </CardTitle>
                <CardDescription>
                    Manage your login email and password.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col lg:flex-row w-full gap-6">
                    <!-- Left: Login Email -->
                    <div class="flex-1 min-w-0 space-y-4">
                        <h3 class="font-semibold">Login Email</h3>
                        <p class="text-sm text-muted-foreground">Current: {{ currentEmail }}</p>
                        <form @submit.prevent="submitEmail" class="space-y-4">
                            <div v-auto-animate>
                                <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">New Email</label>
                                <Input :disabled="!isEmailEditing"
                                       v-model="emailForm.email"
                                       type="email"
                                       placeholder="enter new email"
                                />
                                <p v-if="emailErrors.email" class="text-sm font-medium text-destructive mt-1">{{ emailErrors.email }}</p>
                            </div>

                            <div v-auto-animate>
                                <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Current Password</label>
                                <Input :disabled="!isEmailEditing"
                                       v-model="emailForm.current_password"
                                       type="password"
                                       placeholder="confirm password"
                                />
                                <p v-if="emailErrors.current_password" class="text-sm font-medium text-destructive mt-1">{{ emailErrors.current_password }}</p>
                            </div>

                            <div v-if="!isEmailEditing" class="flex items-center gap-2">
                                <Button type="button" @click="isEmailEditing = true" size="sm" class="h-7 gap-1 bg-black">
                                    <PencilIcon class="h-3.5 w-3.5"/>
                                    <span class="sr-only sm:not-sr-only sm:whitespace-nowrap">Edit Email</span>
                                </Button>
                            </div>
                            <div v-else class="flex gap-2">
                                <Button :disabled="emailLoading" type="submit" class="gap-2">
                                    <SaveIcon class="h-4 w-4"/>
                                    Save
                                    <LoaderCircleIcon v-if="emailLoading" class="animate-spin"/>
                                </Button>
                                <Button variant="outline" type="button" @click="cancelEmailEdit">
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    </div>

                    <!-- Vertical divider -->
                    <div class="hidden lg:block border-l" />

                    <!-- Right: Change Password -->
                    <div class="flex-1 min-w-0 space-y-4">
                        <h3 class="font-semibold">Change Password</h3>
                        <form @submit.prevent="submitPassword" class="space-y-4">
                            <div v-auto-animate>
                                <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Current Password</label>
                                <Input :disabled="!isPasswordEditing"
                                       v-model="passwordForm.current_password"
                                       type="password"
                                       placeholder="enter current password"
                                />
                                <p v-if="passwordErrors.current_password" class="text-sm font-medium text-destructive mt-1">{{ passwordErrors.current_password }}</p>
                            </div>

                            <div v-auto-animate>
                                <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">New Password</label>
                                <Input :disabled="!isPasswordEditing"
                                       v-model="passwordForm.password"
                                       type="password"
                                       placeholder="enter new password"
                                />
                                <p v-if="passwordErrors.password" class="text-sm font-medium text-destructive mt-1">{{ passwordErrors.password }}</p>
                            </div>

                            <div v-auto-animate>
                                <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Confirm New Password</label>
                                <Input :disabled="!isPasswordEditing"
                                       v-model="passwordForm.password_confirmation"
                                       type="password"
                                       placeholder="confirm new password"
                                />
                                <p v-if="passwordErrors.password_confirmation" class="text-sm font-medium text-destructive mt-1">{{ passwordErrors.password_confirmation }}</p>
                            </div>

                            <div v-if="!isPasswordEditing" class="flex items-center gap-2">
                                <Button type="button" @click="isPasswordEditing = true" size="sm" class="h-7 gap-1 bg-black">
                                    <PencilIcon class="h-3.5 w-3.5"/>
                                    <span class="sr-only sm:not-sr-only sm:whitespace-nowrap">Edit Password</span>
                                </Button>
                            </div>
                            <div v-else class="flex gap-2">
                                <Button :disabled="passwordLoading" type="submit" class="gap-2">
                                    <SaveIcon class="h-4 w-4"/>
                                    Save
                                    <LoaderCircleIcon v-if="passwordLoading" class="animate-spin"/>
                                </Button>
                                <Button variant="outline" type="button" @click="cancelPasswordEdit">
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </CardContent>
            <CardFooter/>
        </Card>
    </main>
</template>
