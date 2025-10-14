<script setup lang="ts">
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import {
    ArrowLeftCircle,
    ArrowRightCircle,
    LoaderCircleIcon,
    MoreHorizontal,
    PlusCircle,
    Search,
    TriangleAlert
} from 'lucide-vue-next'

import { Badge } from '@/shadcn/ui/badge'
import { Button } from '@/shadcn/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/shadcn/ui/card'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger
} from '@/shadcn/ui/dropdown-menu'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow, } from '@/shadcn/ui/table'
import { CrudDialogAdapter } from "@/lib/DialogState/CrudDialogAdapter";
import { computed, onMounted, reactive, ref, useAttrs } from "vue";
import axios from "axios";
import { errorToast, PaginationOption, successToast } from "@/lib/utils";
import { debounceFilter, watchPausable } from "@vueuse/core";
import { Input } from "@/shadcn/ui/input";
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from "@/shadcn/ui/dialog";
import { Label } from "@/shadcn/ui/label";
import { Checkbox } from '@/shadcn/ui/checkbox'
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger } from "@/shadcn/ui/select";
import { Separator } from "@/shadcn/ui/separator";

defineOptions({
    layout: LayoutWrapper
})

const attrs = useAttrs()
const dataset = ref()
const isLoading = ref(false)
const paginateControl = reactive(new PaginationOption())
const paginationWatcher = watchPausable(
    paginateControl,
    (value) => {
        getDataset(paginateControl.currentPage)
    }, { eventFilter: debounceFilter(800) }
)

const companyGroupFilter = ref()
const companyGroupFilterWatcher = watchPausable(
    companyGroupFilter,
    (value) => {
        getAllGroupCompany(companyGroupFilter.value)
    }, { eventFilter: debounceFilter(800) }
)

const companySalaryForm = ref({
    id: undefined,
    user_id: attrs.auth.user?.id,
    name: undefined,
    salary: 0,
    is_tax: false,
    type: undefined,
    calculation_type: undefined,
    included_at_default: false,
    companyGroupId: undefined,
    assigned_to: [],
})

const companyGroup = ref([]);

function getDataset(page?: number) {
    paginationWatcher.pause()
    isLoading.value = true
    axios.get(route('company-payroll.json.all', {
        page: page,
        search: paginateControl.searchQuery,
        user: companySalaryForm.value.user_id
    }))
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            dataset.value = dataFromServer.data
            paginateControl.currentPage = dataFromServer.current_page
            paginateControl.nextPageUrl = dataFromServer.next_page_url
            paginateControl.prevPageUrl = dataFromServer.prev_page_url
            paginateControl.from = dataFromServer.from
            paginateControl.to = dataFromServer.to
            paginateControl.totalData = dataFromServer.total
            paginateControl.perPageData = dataFromServer.per_page
        })
        .catch((err) => {
            errorToast('Error !', err.response.data.message)
        })
        .finally(() => {
            paginationWatcher.resume()
            isLoading.value = false
        })
}

function getEmployeeByGroup() {
    isLoading.value = true
    axios.get(route('employee-account.json.group', { group_id: companySalaryForm.value.companyGroupId }))
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            console.log(dataFromServer)
            companySalaryForm.value.assigned_to = dataFromServer
        })
        .catch((err) => {
            errorToast('Error !', err.response.data.message)
        })
        .finally(() => {
            isLoading.value = false
            companySalaryForm.value.companyGroupId = undefined
        })
}

function getAllGroupCompany(searchFilter: string) {
    isLoading.value = true
    companyGroupFilterWatcher.pause()
    axios.get(route('employee-group.json.all', { search: searchFilter }))
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            companyGroup.value = dataFromServer.data
        })
        .catch((err) => {
            errorToast('Error !', err.response.data.message)
        })
        .finally(() => {
            companyGroupFilterWatcher.resume()
            isLoading.value = false
        })
}

function deleteItem(dataId: number) {
    dialogState.delete.progress = true
    axios.delete(route('company-payroll.json.delete',{user:companySalaryForm.value.user_id}), { data: {id: dataId }})
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            successToast('Success', message)
            getDataset(paginateControl.currentPage)
            dialogState.delete.state = false
        })
        .catch((err) => {
            errorToast('Error !', err.response.data.message)
        })
        .finally(() => {
            dialogState.delete.progress = false
        })
}

function submitItem() {
    dialogState.add.progress = true
    axios.post(route('company-payroll.json.add', { user: companySalaryForm.value.user_id }), companySalaryForm.value)
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            successToast('Success', message)
            dialogState.add.state = false
            getDataset()
        })
        .catch((err) => {
            errorToast('Error !', err.response.data.message)
        })
        .finally(() => {
            dialogState.add.progress = false
        })
}

function submitEditItem() {
    dialogState.update.progress = true
    axios.put(route('company-payroll.json.update', { user: companySalaryForm.value.user_id }), companySalaryForm.value)
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            successToast('Success', message)
            dialogState.update.state = false
            getDataset()
        })
        .catch((err) => {
            errorToast('Error !', err.response.data.message)
        })
        .finally(() => {
            dialogState.update.progress = false
        })
}

onMounted(async () => {
    getDataset()
    getAllGroupCompany('')
})

const dialogState = reactive(new CrudDialogAdapter())
const formattedSalary = computed({
    get() {
        if (companySalaryForm.value.is_tax) {
            return `${companySalaryForm.value.salary} %`
        }
        return companySalaryForm.value.salary
            ? new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(companySalaryForm.value.salary)
            : '';
    },
    set(value: string) {
        // Hapus semua karakter non-digit
        const numericValue = value.replace(/[^0-9]/g, '');
        companySalaryForm.value.salary = numericValue ? parseInt(numericValue) : 0;
    }
});

function editData(data: any) {
    companySalaryForm.value.id = data.id
    companySalaryForm.value.name = data.name
    companySalaryForm.value.salary = data.salary
    companySalaryForm.value.is_tax = data.is_tax
    companySalaryForm.value.type = data.type
    companySalaryForm.value.calculation_type = data.calculation_type
    companySalaryForm.value.assigned_to = data.assigned_to
    companySalaryForm.value.included_at_default = data.assigned_to.length > 0 ? true : false
    dialogState.update.state = true
}

function addData() {
    companySalaryForm.value = {
        id: undefined,
        user_id: attrs.auth.user?.id,
        name: undefined,
        salary: 0,
        is_tax: false,
        type: undefined,
        calculation_type: undefined,
        included_at_default: false,
        companyGroupId: undefined,
        assigned_to: [],
    }
    dialogState.add.state = true
}
</script>

<template>
    <main class="grid flex-1 items-start gap-4 p-4 sm:px-6 md:gap-4">
        <Dialog v-model:open="dialogState.add.state">
            <DialogContent :disable-dismiss="true"
                           class="sm:max-w-fit grid-rows-[auto_minmax(0,1fr)_auto] p-0 max-h-[90dvh]">
                <DialogHeader class="p-6 pb-0">
                    <DialogTitle>Create New Company Payroll</DialogTitle>
                    <DialogDescription>
                        Create your company payroll category here. Click save when you're done.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4 overflow-y-auto px-6">
                    <div class="flex flex-col justify-between min-h-[100dvh] px-2">
                        <div class="flex flex-col gap-4">
                            <div class="w-full h-full flex flex-col gap-2">
                                <Label class="ps-2">Name</Label>
                                <Input v-model="companySalaryForm.name" placeholder="Salary name"></Input>
                            </div>
                            <div class="w-full h-full inline-flex items-center gap-4">
                                <Checkbox v-model="companySalaryForm.is_tax"/>
                                <div class="grid">
                                    <label
                                        class="text-sm font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                        Tax Category
                                    </label>
                                    <span class="text-sm text-muted-foreground">
                                       Check this to subtract % of total ammount employee salary
                                    </span>
                                </div>
                            </div>
                            <div class="w-full h-full flex flex-col gap-2">
                                <Label class="ps-2">{{ companySalaryForm.is_tax ? 'Tax value' : 'Salary' }}</Label>
                                <div class="inline-flex gap-2 items-center">
                                    <Input
                                        v-model="formattedSalary"
                                        type="text"
                                        placeholder="Masukkan gaji"
                                    />
                                </div>
                            </div>
                            <div class="w-full h-full flex flex-col gap-2">
                                <Label class="ps-2">Salary payment type</Label>
                                <Select v-model="companySalaryForm.type">
                                    <SelectTrigger class="capitalize">{{ companySalaryForm.type || 'Select type' }}
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value="fixed">
                                                Fixed
                                            </SelectItem>
                                            <SelectItem value="hourly">
                                                Hourly
                                            </SelectItem>
                                            <SelectItem value="presence">
                                                Presence
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div v-if="!companySalaryForm.is_tax" class="w-full h-full flex flex-col gap-2">
                                <Label class="ps-2">Calculation method</Label>
                                <Select v-model="companySalaryForm.calculation_type">
                                    <SelectTrigger class="capitalize">
                                        {{ companySalaryForm.calculation_type || 'Select type' }}
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value="add">
                                                add
                                            </SelectItem>
                                            <SelectItem value="subtract">
                                                Subtract
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="w-full h-full inline-flex items-center gap-4">
                                <Checkbox v-model="companySalaryForm.included_at_default"
                                          v-on:update:modelValue="value => value == false ? companySalaryForm.assigned_to = [] : true"/>
                                <div class="grid">
                                    <label
                                        class="text-sm font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                        Included as default
                                    </label>
                                    <span class="text-sm text-muted-foreground">Check this to make salary default included in employee payroll calculation</span>
                                </div>
                            </div>
                            <div v-if="companySalaryForm.included_at_default"
                                 class="inline-grid items-center grid-cols-3 mt-4">
                                <Separator/>
                                <Label class="justify-self-center text-muted-foreground">Assigned Employee</Label>
                                <Separator/>
                            </div>
                            <div v-if="companySalaryForm.included_at_default" class="w-full h-full flex flex-col gap-2">
                                <Label class="ps-2">Assign employee by group</Label>
                                <Label class="ps-2 text-xs text-muted-foreground">Tips : You can edit each employee
                                    configuration later at employee account management</Label>
                                <Select v-model="companySalaryForm.companyGroupId"
                                        @update:modelValue="getEmployeeByGroup()">
                                    <SelectTrigger class="capitalize">Select Company Group</SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem v-for="group in companyGroup" :value="group.id">
                                                {{ group.name }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <ul>
                                    <li v-for="user in companySalaryForm.assigned_to">
                                        <Card class="w-full p-4">
                                            <div class="flex flex-col justify-between gap-4">
                                                <Label class="text-lg font-bold">
                                                    {{ user.user_detail?.fullname }}
                                                </Label>
                                                <div class="w-full h-full inline-flex items-center gap-4">
                                                    <Checkbox v-model="user.pivot.available_to_request"/>
                                                    <div class="grid">
                                                        <label
                                                            class="text-sm font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                                            Available to request
                                                        </label>
                                                        <span class="text-sm text-muted-foreground">Check this to make salary can be requested by employee</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </Card>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <p>This content should appear at the bottom after you scroll.</p>
                    </div>
                </div>
                <DialogFooter class="p-6 pt-0">
                    <Button @click="submitItem" type="submit">
                        Save changes
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <Dialog v-model:open="dialogState.update.state">
            <DialogContent :disable-dismiss="true"
                           class="sm:max-w-fit grid-rows-[auto_minmax(0,1fr)_auto] p-0 max-h-[90dvh]">
                <DialogHeader class="p-6 pb-0">
                    <DialogTitle>Create New Company Payroll</DialogTitle>
                    <DialogDescription>
                        Create your company payroll category here. Click save when you're done.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4 overflow-y-auto px-6">
                    <div class="flex flex-col justify-between min-h-[100dvh] px-2">
                        <div class="flex flex-col gap-4">
                            <div class="w-full h-full flex flex-col gap-2">
                                <Label class="ps-2">Name</Label>
                                <Input v-model="companySalaryForm.name" placeholder="Salary name"></Input>
                            </div>
                            <div class="w-full h-full inline-flex items-center gap-4">
                                <Checkbox v-model="companySalaryForm.is_tax"/>
                                <div class="grid">
                                    <label
                                        class="text-sm font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                        Tax Category
                                    </label>
                                    <span class="text-sm text-muted-foreground">
                                       Check this to subtract % of total ammount employee salary
                                    </span>
                                </div>
                            </div>
                            <div class="w-full h-full flex flex-col gap-2">
                                <Label class="ps-2">{{ companySalaryForm.is_tax ? 'Tax value' : 'Salary' }}</Label>
                                <div class="inline-flex gap-2 items-center">
                                    <Input
                                        v-model="formattedSalary"
                                        type="text"
                                        placeholder="Masukkan gaji"
                                    />
                                </div>
                            </div>
                            <div class="w-full h-full flex flex-col gap-2">
                                <Label class="ps-2">Salary payment type</Label>
                                <Select v-model="companySalaryForm.type">
                                    <SelectTrigger class="capitalize">{{ companySalaryForm.type || 'Select type' }}
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value="fixed">
                                                Fixed
                                            </SelectItem>
                                            <SelectItem value="hourly">
                                                Hourly
                                            </SelectItem>
                                            <SelectItem value="presence">
                                                Presence
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div v-if="!companySalaryForm.is_tax" class="w-full h-full flex flex-col gap-2">
                                <Label class="ps-2">Calculation method</Label>
                                <Select v-model="companySalaryForm.calculation_type">
                                    <SelectTrigger class="capitalize">
                                        {{ companySalaryForm.calculation_type || 'Select type' }}
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value="add">
                                                add
                                            </SelectItem>
                                            <SelectItem value="subtract">
                                                Subtract
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="w-full h-full inline-flex items-center gap-4">
                                <Checkbox v-model="companySalaryForm.included_at_default"
                                          v-on:update:modelValue="value => value == false ? companySalaryForm.assigned_to = [] : true"/>
                                <div class="grid">
                                    <label
                                        class="text-sm font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                        Included as default
                                    </label>
                                    <span class="text-sm text-muted-foreground">Check this to make salary default included in employee payroll calculation</span>
                                </div>
                            </div>
                            <div v-if="companySalaryForm.included_at_default"
                                 class="inline-grid items-center grid-cols-3 mt-4">
                                <Separator/>
                                <Label class="justify-self-center text-muted-foreground">Assigned Employee</Label>
                                <Separator/>
                            </div>
                            <div v-if="companySalaryForm.included_at_default" class="w-full h-full flex flex-col gap-2">
                                <Label class="ps-2">Assign employee by group</Label>
                                <Label class="ps-2 text-xs text-muted-foreground">Tips : You can edit each employee
                                    configuration later at employee account management</Label>
                                <Select v-model="companySalaryForm.companyGroupId"
                                        @update:modelValue="getEmployeeByGroup()">
                                    <SelectTrigger class="capitalize">Select Company Group</SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem v-for="group in companyGroup" :value="group.id">
                                                {{ group.name }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <ul>
                                    <li v-for="user in companySalaryForm.assigned_to">
                                        <Card class="w-full p-4">
                                            <div class="flex flex-col justify-between gap-4">
                                                <Label class="text-lg font-bold">
                                                    {{ user.user_detail?.fullname }}
                                                </Label>
                                                <div class="w-full h-full inline-flex items-center gap-4">
                                                    <Checkbox v-model="user.pivot.available_to_request"/>
                                                    <div class="grid">
                                                        <label
                                                            class="text-sm font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                                            Available to request
                                                        </label>
                                                        <span class="text-sm text-muted-foreground">Check this to make salary can be requested by employee</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </Card>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <p>This content should appear at the bottom after you scroll.</p>
                    </div>
                </div>
                <DialogFooter class="p-6 pt-0">
                    <Button @click="submitEditItem()" type="submit">
                        Save changes
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <Dialog v-model:open="dialogState.delete.state">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{ dialogState.delete.data.name }} item</DialogTitle>
                    <DialogDescription>
                        After deletion, all of payroll calculation in this attached employee will be deleted,
                        <br><br> Are you sure to perform this action ? deleted data cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">
                            Close
                        </Button>
                    </DialogClose>
                    <Button @click="deleteItem(dialogState.delete.data.id)">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <Card>
            <CardHeader>
                <CardTitle>Company Payroll Management</CardTitle>
                <CardDescription class="inline-flex justify-between items-center">
                    Manage your company payroll and manage their related settings.
                    <div class="ml-auto flex items-center gap-2">
                        <Button @click="addData()" size="sm" class="h-7 gap-1 bg-black">
                            <PlusCircle class="h-3.5 w-3.5"/>
                            <span class="sr-only sm:not-sr-only sm:whitespace-nowrap">
                  Add Company Payroll
                </span>
                        </Button>
                    </div>
                </CardDescription>
                <div class="relative md:ml-auto flex-1 md:grow-0">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground"/>
                    <Input
                        v-model:model-value="paginateControl.searchQuery"
                        type="search"
                        placeholder="Search..."
                        class="w-full rounded-lg bg-background pl-8 md:w-[200px] lg:w-[320px]"
                    />
                </div>
            </CardHeader>
            <CardContent>
                <Table class="relative">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="hidden w-[100px] sm:table-cell text-center">
                                No
                            </TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead class="text-center">Value</TableHead>
                            <TableHead class="text-center">Assigned Employee</TableHead>
                            <TableHead class="text-center">Calculation Type</TableHead>
                            <TableHead class="text-center">Type</TableHead>
                            <TableHead>
                                Action
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(group,index) in dataset" :key="group.id">
                            <TableCell class="hidden sm:table-cell text-center">
                                {{ index + 1 }}
                            </TableCell>
                            <TableCell class="font-medium">
                                {{ group.name }}
                            </TableCell>
                            <TableCell v-if="!group.is_tax" class="font-medium text-center">
                                {{
                                    group.salary ? new Intl.NumberFormat('id-ID', {
                                        style: 'currency',
                                        currency: 'IDR'
                                    }).format(group.salary) : '-'
                                }}
                            </TableCell>
                            <TableCell v-else class="font-medium text-center">
                                {{ group.salary }}%
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge variant="outline">
                                    {{ group.assigned_to.length }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge v-if="!group.is_tax"
                                       :variant="group.calculation_type == 'add' ? 'success':'destructive'">
                                    {{ group.calculation_type }}
                                </Badge>
                                <Badge v-else class="bg-amber-500">
                                    Tax
                                </Badge>
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge v-if="!group.is_tax" variant="outline">
                                    {{ group.type }}
                                </Badge>
                                <Badge v-else variant="outline">
                                    tax
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            aria-haspopup="true"
                                            size="icon"
                                            variant="ghost"
                                        >
                                            <MoreHorizontal class="h-4 w-4"/>
                                            <span class="sr-only">Toggle menu</span>
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                        <DropdownMenuItem
                                            @click="()=>{editData(group)}"
                                            class="cursor-pointer">Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="dialogState.delete.data = group;dialogState.delete.state = true"
                                            class="cursor-pointer">Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="!dataset || dataset.length == 0">
                            <TableCell colspan="4" class="text-center">
                                <div class="inline-flex gap-2 items-center text-lg">
                                    <TriangleAlert class="text-destructive animate-pulse"/>
                                    Tidak ada data
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell v-if="isLoading" colspan="4">
                                <div class="w-full inline-flex justify-center animate-pulse">
                                    <LoaderCircleIcon class="size-10 animate-spin"/>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
            <CardFooter>
                <div class="justify-between md:inline-flex md:space-y-0 space-y-2 w-full">
                    <div class="text-xs text-muted-foreground">
                        Showing <strong>{{ paginateControl.from }}-{{ paginateControl.to }}</strong> of
                        <strong>{{ paginateControl.totalData }}</strong>
                        group
                    </div>
                    <div class="text-xs text-muted-foreground grid grid-flow-col space-x-4">
                        <Button @click="()=>paginateControl.currentPage--" v-if="paginateControl.prevPageUrl"
                                variant="outline" class="gap-2">
                            <ArrowLeftCircle size="18"/>
                            Prev
                        </Button>
                        <span class="hidden md:block px-4 border rounded-md content-center text-center">
                            {{ paginateControl.currentPage }}
                        </span>
                        <Button @click="paginateControl.currentPage++" v-if="paginateControl.nextPageUrl"
                                class="bg-black gap-2">
                            Next
                            <ArrowRightCircle size="18"/>
                        </Button>
                    </div>
                </div>
            </CardFooter>
        </Card>
    </main>
</template>

<style scoped>

</style>
