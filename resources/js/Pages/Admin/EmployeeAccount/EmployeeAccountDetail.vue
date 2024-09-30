<script setup lang="ts">
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from "@/shadcn/ui/dialog";
import { toTypedSchema } from "@vee-validate/zod";
import { z } from "zod";
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/shadcn/ui/form";
import { vAutoAnimate } from "@formkit/auto-animate";
import { useForm } from "vee-validate";
import { ref } from "vue";
import { Input } from "@/shadcn/ui/input";
import { Button } from "@/shadcn/ui/button";
import {
    ArrowLeftCircle,
    ArrowRightCircle,
    LoaderCircleIcon,
    MoreHorizontal, PlusCircle,
    Search,
    TriangleAlert,
    PencilIcon
} from "lucide-vue-next";
import axios from "axios";
import { PhoneRegex } from "@/lib/utils";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/shadcn/ui/table";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger
} from "@/shadcn/ui/dropdown-menu";
import CreateAccount from "@/Pages/Admin/EmployeeAccount/CreateAccount.vue";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/shadcn/ui/card";
import { Badge } from "@/shadcn/ui/badge";
import EditAccount from "@/Pages/Admin/EmployeeAccount/EditAccount.vue";
import { Avatar, AvatarFallback, AvatarImage } from "@/shadcn/ui/avatar";
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";

defineOptions({
    layout:LayoutWrapper
})
const props = defineProps<{
    account?: any
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
    validationSchema: formSchema,initialValues:{
        name:props.account.user_detail.fullname,
        email:props.account.email,
        address:props.account.user_detail.address,
        phone:props.account.user_detail.phone
    }
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
    <main class="grid flex-1 items-start gap-4 p-4 sm:px-6 md:gap-4">
<!--        <Dialog>-->
<!--            <DialogContent>-->
<!--                <DialogHeader>-->
<!--                    <DialogTitle>Delete {{dialogState.delete.data.name}} Group</DialogTitle>-->
<!--                    <DialogDescription>-->
<!--                        After deletion, all of employee in this group will be still remaining,-->
<!--                        <br> Are you sure to perform this action ? deleted data cannot be undone.-->
<!--                    </DialogDescription>-->
<!--                </DialogHeader>-->
<!--                <DialogFooter>-->
<!--                    <DialogClose as-child>-->
<!--                        <Button type="button" variant="secondary">-->
<!--                            Close-->
<!--                        </Button>-->
<!--                    </DialogClose>-->
<!--                    <Button @click="deleteItem(dialogState.delete.data.id)">Delete</Button>-->
<!--                </DialogFooter>-->
<!--            </DialogContent>-->
<!--        </Dialog>-->
        <Card class="overflow-scroll">
            <CardHeader>
                <CardTitle>Account Detail</CardTitle>
                <CardDescription class="inline-flex justify-between items-center">
                    Manage your employee account and view their related information.
                    <div class="ml-auto flex items-center gap-2">
                        <Button @click="dialogState.show.state = true" size="sm" class="h-7 gap-1 bg-black">
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
                    <div class="flex items-center justify-center">
                        <Avatar class="size-64">
                            <AvatarImage src=""></AvatarImage>
                            <AvatarFallback>TS</AvatarFallback>
                        </Avatar>
                    </div>
                    <div class="outline outline-secondary rounded-md col-span-2 flex flex-col p-4">
                        <form class="space-y-4" @submit="onSubmit">
                            <FormField name="name" :validate-on-blur="!isFieldDirty" v-slot="{ componentField }">
                                <FormItem v-auto-animate>
                                    <FormLabel>Full Name</FormLabel>
                                    <FormControl>
                                        <Input disabled
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
                                        />
                                    </FormControl>
                                    <FormMessage/>
                                </FormItem>
                            </FormField>

                            <Button type="submit" class="w-full">
                                Save
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
