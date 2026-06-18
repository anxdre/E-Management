<script setup lang="ts">
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import {
    ArrowLeftCircle,
    ArrowRightCircle,
    CalendarIcon,
    CircleX,
    DownloadIcon,
    LoaderCircleIcon,
    MoreHorizontal,
    PlusCircle,
    Search,
    TriangleAlert,
    X,
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
import { computed, onMounted, reactive, Ref, ref, useAttrs } from "vue";
import axios from "axios";
import { errorToast, navigateLink, PaginationOption, successToast } from "@/lib/utils";
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
import type { DateRange } from 'reka-ui'
import { getLocalTimeZone, startOfMonth, today } from "@internationalized/date";
import { RangeCalendar } from "@/shadcn/ui/range-calendar";
import { Popover, PopoverContent, PopoverTrigger } from "@/shadcn/ui/popover";
import dayjs from "dayjs";

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

const start = startOfMonth(today(getLocalTimeZone()))

const end = start.add({ months: 1 })

const dateFilter = ref({
    start,
    end,
}) as Ref<DateRange>

const generateForm = ref({
    calculateDate: { start, end },
    assigned_to: [],
})

function getDataset(page?: number) {
    paginationWatcher.pause()
    isLoading.value = true
    axios.get(route('company-receipt.json.all', {
        page: page,
        search: paginateControl.searchQuery,
        date_filter: {
            start: dateFilter.value.start?.toDate('Asia/Jakarta'),
            end: dateFilter.value.end?.toDate('Asia/Jakarta'),
        },
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
            const target = dialogState.add.state ? generateForm.value.assigned_to : companySalaryForm.value.assigned_to
            const existingIds = new Set(target.map((u: any) => u.id))
            const newUsers = dataFromServer.filter((u: any) => !existingIds.has(u.id))
            if (dialogState.add.state) {
                generateForm.value.assigned_to = [...target, ...newUsers]
            } else {
                companySalaryForm.value.assigned_to = [...target, ...newUsers]
            }
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
    axios.delete(route('company-receipt.json.delete', { user: companySalaryForm.value.user_id }), { data: { id: dataId } })
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

function submitBulkItem() {
    dialogState.add.progress = true
    const params = {
        user_id: generateForm.value.assigned_to.map((user) => user.id),
        date_start: generateForm.value.calculateDate.start.toDate('Asia/Jakarta'),
        date_end: generateForm.value.calculateDate.end.toDate('Asia/Jakarta')
    }
    axios.post(route('company-receipt.json.add.bulk', { user: companySalaryForm.value.user_id }), params)
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            if (status_code !== 200) {
                errorToast('Error !', message)
                return;
            }
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

function editData(data: any) {
   navigateLink(route('employee-payroll.detail', { user: companySalaryForm.value.user_id, payroll: data.id }))
}

function deleteAssignedEmployee(index: number) {
    if (dialogState.add.state) {
        generateForm.value.assigned_to.splice(index, 1)
    } else {
        companySalaryForm.value.assigned_to.splice(index, 1)
    }
}

function exportExcel() {
    const params = {
        search: paginateControl.searchQuery,
        date_start: dateFilter.value.start?.toDate('Asia/Jakarta'),
        date_end: dateFilter.value.end?.toDate('Asia/Jakarta'),
    }
    const url = route('company-receipt.export.excel', { ...params, user: companySalaryForm.value.user_id })
    window.open(url, '_blank')
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
    generateForm.value.assigned_to = []
    generateForm.value.calculateDate = { start, end }
    dialogState.add.state = true
}

onMounted(async () => {
    getDataset()
    getAllGroupCompany('')
})
</script>

<template>
    <main class="grid flex-1 items-start gap-4 p-4 sm:px-6 md:gap-4">
        <Dialog v-model:open="dialogState.add.state">
            <DialogContent :disable-dismiss="true"
                           class="sm:max-w-fit grid-rows-[auto_minmax(0,1fr)_auto] p-0 max-h-[90dvh]">
                <DialogHeader class="p-6 pb-0">
                    <DialogTitle>Generate New Employee Payroll</DialogTitle>
                    <DialogDescription>
                        Create bulk employee payroll here. Click save when you're done.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4 overflow-y-auto px-6">
                    <div class="flex flex-col justify-between min-h-[100dvh] px-2">
                        <div class="flex flex-col gap-4">
                            <div class="w-full h-full flex flex-col gap-2">
                                <Label class="ps-2">Calculated Payroll</Label>
                                <Popover>
                                    <PopoverTrigger class="inline-flex">
                                        <Button variant="outline" class="gap-2 w-full justify-start">
                                            <CalendarIcon class="size-4"/>
                                            {{
                                                `${dayjs(generateForm.calculateDate.start).format('DD/MM/YYYY')} - ${dayjs(generateForm.calculateDate.end).format('DD/MM/YYYY')}` || 'Select Date'
                                            }}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent class="p-0 w-fit" trap-focus>
                                        <RangeCalendar v-model="generateForm.calculateDate"
                                                       class="rounded-md border items-center"/>
                                    </PopoverContent>
                                </Popover>
                            </div>
                            <div
                                class="inline-grid items-center grid-cols-3 mt-4">
                                <Separator/>
                                <Label class="justify-self-center text-muted-foreground">Assigned Employee</Label>
                                <Separator/>
                            </div>
                            <div class="w-full h-full flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <Label class="ps-2">Assign employee by group</Label>
                                    <Button v-if="generateForm.assigned_to.length > 0" variant="outline" size="sm"
                                            class="text-destructive"
                                            @click="generateForm.assigned_to = []">
                                        <X class="h-3 w-3 me-1"/> Clear All ({{ generateForm.assigned_to.length }})
                                    </Button>
                                </div>
                                <Label class="ps-2 text-xs text-muted-foreground">Tips : You can generate customized
                                    salary each employee
                                    at <strong>Employee Management</strong></Label>
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
                                <ul class="flex flex-col gap-2">
                                    <li v-for="(user, index) in generateForm.assigned_to" :key="user.id">
                                        <Card class="w-full p-4">
                                            <div class="flex items-start justify-between">
                                                <div class="flex flex-col justify-between">
                                                    <Label class="text-lg font-bold">
                                                        {{ user.user_detail?.fullname }}
                                                    </Label>
                                                    <label
                                                        class="text-sm text-muted-foreground font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                                        {{ user.email }}
                                                    </label>
                                                    <label
                                                        class="text-sm text-muted-foreground font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                                        {{ user.user_detail?.phone }}
                                                    </label>
                                                    <Badge
                                                        :variant="user.is_suspended ? 'destructive':'success'"
                                                        class="text-sm font-medium w-fit mt-2">
                                                        {{ user.is_suspended ? 'Suspended' : 'Active' }} Employee
                                                    </Badge>
                                                </div>
                                                <Button variant="ghost" size="icon"
                                                        class="h-6 w-6 text-destructive shrink-0"
                                                        @click="deleteAssignedEmployee(index)">
                                                    <X class="h-4 w-4"/>
                                                </Button>
                                            </div>
                                        </Card>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <DialogFooter class="p-6 pt-0 items-center !justify-between inline-flex">
                    <Label>Total salary receipt will be generated : {{ generateForm.assigned_to.length }}</Label>
                    <Button @click="submitBulkItem" type="submit">
                        Generate Salary Receipt
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
                                <div class="flex justify-between items-center">
                                    <Label class="ps-2">Assign employee by group</Label>
                                    <Button v-if="companySalaryForm.assigned_to.length > 0" variant="outline" size="sm"
                                            class="text-destructive"
                                            @click="companySalaryForm.assigned_to = []">
                                        <X class="h-3 w-3 me-1"/> Clear All ({{ companySalaryForm.assigned_to.length }})
                                    </Button>
                                </div>
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
                                <ul class="flex flex-col gap-2">
                                    <li v-for="(user, userIndex) in companySalaryForm.assigned_to" :key="user.id">
                                        <Card class="w-full p-4">
                                            <div class="flex flex-col justify-between gap-4">
                                                <div class="flex justify-between items-start">
                                                    <Label class="text-lg font-bold">
                                                        {{ user.user_detail?.fullname }}
                                                    </Label>
                                                    <Button variant="ghost" size="icon"
                                                            class="h-6 w-6 text-destructive shrink-0"
                                                            @click="deleteAssignedEmployee(userIndex)">
                                                        <X class="h-4 w-4"/>
                                                    </Button>
                                                </div>
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
                <CardTitle>Payroll Receipt Management</CardTitle>
                <CardDescription class="inline-flex justify-between items-center">
                    Manage your company payroll receipt and manage employee payroll related settings.
                    <div class="ml-auto flex items-center gap-2">
                        <Button @click="exportExcel()" size="sm" class="h-7 gap-1" variant="outline">
                            <DownloadIcon class="h-3.5 w-3.5"/>
                            <span class="sr-only sm:not-sr-only sm:whitespace-nowrap">Export Excel</span>
                        </Button>
                        <Button @click="addData()" size="sm" class="h-7 gap-1 bg-black">
                            <PlusCircle class="h-3.5 w-3.5"/>
                            <span class="sr-only sm:not-sr-only sm:whitespace-nowrap">Generate Payroll</span>
                        </Button>
                    </div>
                </CardDescription>
                <div class="relative md:ml-auto flex-1 md:grow-0">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground"/>
                    <Input
                        v-model:model-value="paginateControl.searchQuery"
                        type="search"
                        placeholder="Search employee email..."
                        class="w-full rounded-lg bg-background pl-8 md:w-[200px] lg:w-[320px]"
                    />
                </div>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col gap-2 p-4 w-fit">
                    <Popover>
                        <PopoverTrigger class="inline-flex">
                            <Button class="gap-2">
                                <CalendarIcon class="size-4"/>
                                Date Filter
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="space-y-2 justify-items-end">
                            <RangeCalendar v-model="dateFilter" class="rounded-md border"/>
                            <Button @click="getDataset()" class="bg-black">Get Data</Button>
                        </PopoverContent>
                    </Popover>
                </div>
                <Table class="relative">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="hidden w-[100px] sm:table-cell text-center">
                                No
                            </TableHead>
                            <TableHead>Employee Name</TableHead>
                            <TableHead class="text-center">Base Salary</TableHead>
                            <TableHead class="text-center">Total Salary After Tax</TableHead>
                            <TableHead class="text-center">Status</TableHead>
                            <TableHead class="text-center">Transaction Date</TableHead>
                            <TableHead class="text-center">Requested At</TableHead>
                            <TableHead>
                                Action
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(data,index) in dataset" :key="data.id">
                            <TableCell class="hidden sm:table-cell text-center">
                                {{ index + 1 }}
                            </TableCell>
                            <TableCell :class="`font-medium ${data.user?.deleted_at ? 'text-red-500' : ''}`">
                                {{ data.user?.user_detail?.fullname || 'deleted account'}}
                            </TableCell>
                            <TableCell class="font-medium text-center">
                                {{
                                    data.total_salary ? new Intl.NumberFormat('id-ID', {
                                        style: 'currency',
                                        currency: 'IDR'
                                    }).format(data.total_salary) : '-'
                                }}
                            </TableCell>
                            <TableCell class="font-medium text-center">
                                {{
                                    data.salary_after_tax ? new Intl.NumberFormat('id-ID', {
                                        style: 'currency',
                                        currency: 'IDR'
                                    }).format(data.salary_after_tax) : '-'
                                }}
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge
                                    :variant="data.status == 'approved' ? 'success' : data.status == 'denied' ? 'destructive' : 'warning'">
                                    {{ data.status }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-center">
                                {{ dayjs(data.start_date).format('DD/MM/YYYY') }}
                            </TableCell>
                            <TableCell class="text-center">
                                {{ dayjs(data.created_at).format('DD/MM/YYYY HH:mm') }}
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
                                            @click="()=>{editData(data)}"
                                            class="cursor-pointer">{{ data.user?.deleted_at ? 'Detail' : 'Detail / Edit' }}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem v-if="!data.user?.deleted_at && (data.status == 'pending' || !data.status)"
                                            @click="dialogState.delete.data = data;dialogState.delete.state = true"
                                            class="cursor-pointer">Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="!dataset || dataset.length == 0">
                            <TableCell colspan="8" class="text-center">
                                <div class="inline-flex gap-2 items-center text-lg">
                                    <TriangleAlert class="text-destructive animate-pulse"/>
                                    Tidak ada data
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell v-if="isLoading" colspan="8">
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
