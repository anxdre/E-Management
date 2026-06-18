<script setup lang="ts">
import { Badge } from '@/shadcn/ui/badge'
import { Card, CardContent, CardHeader } from '@/shadcn/ui/card'
import { computed, onMounted, reactive, ref, useAttrs } from 'vue'
import dayjs from 'dayjs'
import axios from "axios";
import { cn, errorToast, hideGlobalLoader, showGlobalLoader, successToast } from "@/lib/utils";
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import { Separator } from "@/shadcn/ui/separator";
import { Check, ChevronsUpDown, DownloadIcon, LoaderIcon, TrashIcon, TriangleAlert, CalendarIcon } from "lucide-vue-next";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/shadcn/ui/table";
import { Button } from "@/shadcn/ui/button";

import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/shadcn/ui/dialog";
import { Label } from "@/shadcn/ui/label";
import { Popover, PopoverContent, PopoverTrigger } from "@/shadcn/ui/popover";
import { CrudDialogAdapter } from "@/lib/DialogState/CrudDialogAdapter";
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from "@/shadcn/ui/command";
import { watchPausable } from "@vueuse/core";
import { debounceFilter } from "@vueuse/core/index";
import { Input } from "@/shadcn/ui/input";
import { Calendar } from "@/shadcn/ui/calendar"

const downloadPDF = () => {
    const url = route('company-receipt.export.detail.pdf', { payroll: data.value.id })
    window.open(url, '_blank')
}

const dialogState = reactive(new CrudDialogAdapter())
const isEdit = ref(false)
const attrs = useAttrs()
const data = ref({ status: 'pending', work_hour: 0, total_salary: 0, date: ''});
defineOptions({
    layout: LayoutWrapper
})

const statusVariant = computed(() => {
    switch (data.value.status) {
        case 'approved':
            return 'success'
        case 'denied':
            return 'destructive'
        default:
            return 'warning'
    }
})

const listOfPayroll = ref([])
const selectedPayroll = ref({})
const searchPayrollItem = ref('')
const searchPayrollWatcher = watchPausable(searchPayrollItem, (val) => {
    getPayrollDataset(val)
}, { eventFilter: debounceFilter(800) })

function getPayrollDataset(search?: string) {
    dialogState.add.progress = true
    searchPayrollWatcher.pause()
    axios.get(route('company-payroll.json.all', {
        search: search,
        user: attrs.auth.user?.id
    }))
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            listOfPayroll.value = dataFromServer.data
        })
        .catch((err) => {
            errorToast('Error !', err.response.data.message)
        })
        .finally(() => {
            dialogState.add.progress = false
            searchPayrollWatcher.resume()
        })
}


function getData() {
    showGlobalLoader();
    axios.get(route('employee-payroll.json.detail', { user: attrs.auth.user?.id, payroll: attrs.id }))
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            data.value = dataFromServer
        })
        .catch((err) => {
            errorToast('Error !', err.response.data.message)
        })
        .finally(() => {
            hideGlobalLoader();
        })
}

function addPayrollItem() {
   data.value.company_salary_item.push({
       id : undefined,
       name : undefined,
       is_tax : undefined,
       type :undefined,
       calculation_type : undefined,
       quantity: undefined,
       salary : undefined,
       detail_item: {
           quantity:  0,
           total_value:  0
       },
   })
}

function deleteItem(index:number){
    data.value.company_salary_item.splice(index,1)
}

function updatePayroll() {
    if (!isEdit.value) {
        isEdit.value = !isEdit.value
        return
    }

    showGlobalLoader();
    axios.put(route('company-receipt.json.update', { user: attrs.auth.user?.id, payroll: attrs.id }), data.value)
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            if (status_code !== 200){
                errorToast('Error !', message)
                return
            }
            successToast('Success !', message)
            isEdit.value = false
            getData()
        })
        .catch((err) => {
            errorToast('Error !', err.response.data.message)
        })
        .finally(() => {
            hideGlobalLoader()
        })
}

function exportDetailExcel() {
    const url = route('company-receipt.export.detail.excel', { user: attrs.auth.user?.id, payroll: data.value.id })
    window.open(url, '_blank')
}

function confirmPayroll(status:boolean) {
    if (isEdit.value) {
        errorToast('Error !', 'Please save your changes before confirming the receipt')
        return
    }

    showGlobalLoader();
    axios.put(route('company-receipt.json.confirm', { user: attrs.auth.user?.id, }), {id: data.value.id, status:status})
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            if (status_code !== 200){
                errorToast('Error !', message)
                return
            }
            data.value = dataFromServer
            successToast('Success !', message)
            getData();
        })
        .catch((err) => {
            errorToast('Error !', err.response.data.message)
        })
        .finally(() => {
            hideGlobalLoader()
        })
}


onMounted(() => {
    getData()
    getPayrollDataset()
})
</script>

<template>
    <div class="container max-w-3xl mx-auto p-4 my-8">
        <Dialog v-model:open="dialogState.add.state">
            <DialogContent :disable-dismiss="true"
                           class="sm:max-w-fit grid-rows-[auto_minmax(0,1fr)_auto] p-0 max-h-[40dvh]">
                <DialogHeader class="p-6 pb-0">
                    <DialogTitle>Add Payroll Item To {{ data.user?.user_detail?.fullname }}</DialogTitle>
                    <DialogDescription>
                        Add payroll item to your employee for this receipt. Click save when you're done.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4 overflow-y-auto px-6">
                    <div class="flex flex-col justify-between min-h-[100dvh] px-2">
                        <div class="flex flex-col gap-4">
                            <div class="w-full h-full flex flex-col gap-2">
                                <Label class="ps-2">Payroll Item</Label>
                                <div class="inline-flex items-center gap-2">
                                    <Popover>
                                        <PopoverTrigger as-child>
                                            <Button
                                                variant="outline"
                                                role="combobox"
                                                class="w-[280px] justify-between"
                                            >
                                                {{ selectedPayroll.name ? selectedPayroll.name : 'Select Item...' }}
                                                <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50"/>
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent class="w-[280px] p-0">
                                            <Command v-model="selectedPayroll" :shouldFilter="false"
                                                     :filter-function="()=>{}">
                                                <CommandInput @input="(val)=>searchPayrollItem = val.target.value"
                                                              placeholder="Search Item"/>
                                                <CommandEmpty>No Item Found</CommandEmpty>
                                                <CommandList>
                                                    <CommandGroup>
                                                        <CommandItem
                                                            v-for="item in listOfPayroll"
                                                            :value="item.name"
                                                            @select.prevent="selectedPayroll = item">
                                                            <Check
                                                                :class="cn('mr-2 h-4 w-4',selectedPayroll.id === item.id ? 'opacity-100' : 'opacity-0')"
                                                            />
                                                            {{ item.name }}
                                                        </CommandItem>
                                                    </CommandGroup>
                                                </CommandList>
                                            </Command>
                                        </PopoverContent>
                                    </Popover>
                                    <LoaderIcon v-if="dialogState.add.progress" class="animate-spin"/>
                                </div>
                            </div>
                            <div v-if="selectedPayroll.type == 'hourly'" class="w-full h-full flex flex-col gap-2">
                                <Label class="ps-2">Quantity</Label>
                                <Input type="number" class="max-w-[280px]" placeholder="Quantity"/>
                            </div>
                        </div>
                    </div>
                </div>
                <DialogFooter class="p-6 pt-0 items-center !justify-between inline-flex">
                    <Button type="submit">
                        Add Payroll Item
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <div class="p-4">
            <Card class="w-full  mx-auto">
                <CardHeader class="space-y-2">
                    <h1 class="text-xl font-bold">{{ data.company_profile?.company_name ?? '' }}</h1>
                    <Separator/>
                    <h4 class="text-muted-foreground">Payroll Receipt Detail</h4>
                    <h4 v-if="isEdit" class="bg-destructive text-center animate-pulse">Edit Mode Active</h4>
                </CardHeader>
                <CardContent>
                    <div class="w-full flex flex-col gap-5">
                        <div class="space-y-4 w-full flex flex-col">
                            <div class="md:flex grid justify-between items-center">
                                <span class="text-sm font-medium">Status</span>
                                <Badge :variant="statusVariant">{{ data.status }}</Badge>
                            </div>
                            <div class="md:flex grid justify-between items-center">
                                <span class="text-sm font-medium">Start Date</span>
                                <span v-if="!isEdit" class="font-bold">{{ dayjs(data.start_date).format('DD/MM/YYYY') }}</span>
                                <Popover v-else>
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="outline"
                                            :class="cn(
                                          'w-fit justify-end text-left font-normal',
                                          !data.start_date && 'text-muted-foreground',
                                        )"
                                        >
                                            <CalendarIcon class="mr-2 h-4 w-4" />
                                            {{ data.start_date ?  dayjs(data.start_date).format('DD/MM/YYYY') : "Pick a date" }}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent class="w-auto p-0">
                                        <Calendar @update:model-value="date => data.start_date = date" initial-focus />
                                    </PopoverContent>
                                </Popover>
                            </div>

                            <div class="md:flex grid justify-between items-center">
                                <span class="text-sm font-medium">End Date</span>
                                <span v-if="!isEdit" class="font-bold">{{ dayjs(data.end_date).format('DD/MM/YYYY') }}</span>
                                <Popover v-else>
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="outline"
                                            :class="cn(
                                          'w-fit justify-end text-left font-normal',
                                          !data.end_date && 'text-muted-foreground',
                                        )"
                                        >
                                            <CalendarIcon class="mr-2 h-4 w-4" />
                                            {{ data.end_date ?  dayjs(data.end_date).format('DD/MM/YYYY') : "Pick a date" }}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent class="w-auto p-0">
                                        <Calendar @update:model-value="date => data.end_date = date" initial-focus />
                                    </PopoverContent>
                                </Popover>
                            </div>

                            <div class="md:flex grid justify-between items-center">
                                <span class="text-sm font-medium">Employee Name</span>
                                <span class="font-bold" :class="{'text-red-500': data.user?.deleted_at}">{{ data.user?.user_detail?.fullname || 'deleted account' }}</span>
                            </div>
                            <div class="md:flex grid justify-between items-center">
                                <span class="text-sm font-medium">Employee Email</span>
                                <span class="font-bold">{{ data.user?.email }}</span>
                            </div>
                            <div class="md:flex grid justify-between items-center">
                                <span class="text-sm font-medium">Employee Phone Number</span>
                                <span class="font-bold">{{ data.user?.user_detail?.phone }}</span>
                            </div>
                        </div>

                        <div class="inline-grid justify-items-center items-center grid-cols-3 mt-2">
                            <Separator class="w-full"/>
                            <span class="text-center font-bold text-muted-foreground">Payroll Item Detail</span>
                            <Separator class="w-full"/>
                        </div>

                        <Table class="relative">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="hidden w-[100px] sm:table-cell text-center">
                                        No
                                    </TableHead>
                                    <TableHead>Name</TableHead>
                                    <TableHead class="text-center">Base Ammount</TableHead>
                                    <TableHead class="text-center">Category</TableHead>
                                    <TableHead class="text-center">Type</TableHead>
                                    <TableHead class="text-center">Quantity</TableHead>
                                    <TableHead v-if="!isEdit" class="text-center">Total Earning</TableHead>
                                    <TableHead v-if="isEdit" class="text-center">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(salItem,index) in data.company_salary_item">
                                    <TableCell class="hidden sm:table-cell text-center">
                                        {{ index + 1 }}
                                    </TableCell>
                                    <TableCell v-if="!isEdit" class="font-medium">
                                        <div class="flex flex-col">
                                            <div class="inline-flex items-center gap-1">
                                                {{ salItem.name }}
                                                <Badge v-if="salItem.is_requested" variant="outline" class="text-xs">Requested</Badge>
                                            </div>
                                            <span v-if="salItem.is_requested && salItem.request_info" class="text-xs text-muted-foreground mt-0.5 leading-tight">
                                                Requested {{ dayjs(salItem.request_info.created_at).format('DD/MM/YYYY') }}
                                                · Approved by {{ data.company_profile?.company_name ?? salItem.request_info.approved_by?.user_detail?.fullname ?? '-' }}
                                                {{ salItem.request_info.approved_date ? dayjs(salItem.request_info.approved_date).format('DD/MM/YYYY') : '' }}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell v-else class="font-medium">
                                        <div class="inline-flex items-center gap-2">
                                            <Popover>
                                                <PopoverTrigger as-child>
                                                    <Button
                                                        variant="outline"
                                                        role="combobox"
                                                        class="w-[280px] justify-between">
                                                        {{ salItem.name ? salItem.name : 'Select Payroll Item...' }}
                                                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50"/>
                                                    </Button>
                                                </PopoverTrigger>
                                                <PopoverContent class="w-[280px] p-0">
                                                    <Command :model-value="salItem.name"
                                                              @update:model-value="val => salItem.name = val"
                                                              :shouldFilter="false">
                                                        <CommandInput
                                                            @input="(val)=>searchPayrollItem = val.target.value"
                                                            placeholder="Search Item"/>
                                                        <CommandEmpty>No Item Found</CommandEmpty>
                                                        <CommandList>
                                                            <CommandGroup>
                                                                <CommandItem
                                                                    v-for="item in listOfPayroll"
                                                                    :value="item.name"
                                                                    @select.prevent="() => {
                                                                      salItem.id = item.id
                                                                      salItem.name = item.name
                                                                      salItem.is_tax = item.is_tax
                                                                      salItem.type = item.type
                                                                      salItem.calculation_type = item.calculation_type
                                                                      salItem.salary = item.salary
                                                                    }">
                                                                    <Check
                                                                        :class="cn('mr-2 h-4 w-4',salItem.id === item.id ? 'opacity-100' : 'opacity-0')"
                                                                    />
                                                                    {{ item.name }}
                                                                </CommandItem>
                                                            </CommandGroup>
                                                        </CommandList>
                                                    </Command>
                                                </PopoverContent>
                                            </Popover>
                                            <LoaderIcon v-if="dialogState.add.progress" class="animate-spin"/>
                                        </div>
                                    </TableCell>

                                    <TableCell v-if="!salItem.is_tax" class="font-medium text-center">
                                        {{
                                            salItem.salary ? new Intl.NumberFormat('id-ID', {
                                                style: 'currency',
                                                currency: 'IDR'
                                            }).format(salItem.salary) : '-'
                                        }}
                                    </TableCell>
                                    <TableCell v-else class="font-medium text-center">
                                        {{ salItem.salary }}%
                                    </TableCell>
                                    <TableCell v-if="!salItem.is_tax" class="text-center">
                                        <Badge
                                            :variant="salItem.calculation_type == 'add' ? 'success' : 'destructive'">
                                            {{ salItem.calculation_type }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell v-else class="text-center">
                                        <Badge
                                            :variant="'warning'">
                                            Tax
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        {{ salItem.type }}
                                    </TableCell>
                                    <TableCell v-if="!isEdit" class="text-center">
                                        {{ salItem.detail_item.quantity }}
                                    </TableCell>
                                     <TableCell v-else class="text-center">
                                        <Input type="number" min="0" v-if="salItem.type == 'fixed' || salItem.is_requested" :default-value="salItem.detail_item.quantity"  v-model="salItem.quantity"/>
                                        <Label v-else>-</Label>
                                    </TableCell>
                                    <TableCell v-if="!isEdit && !salItem.is_tax && salItem.calculation_type == 'add' " class="text-right">
                                        {{
                                            salItem.detail_item.total_value ? new Intl.NumberFormat('id-ID', {
                                                style: 'currency',
                                                currency: 'IDR'
                                            }).format(salItem.detail_item.total_value) : '-'
                                        }}
                                    </TableCell>
                                    <TableCell v-if="!isEdit && (salItem.is_tax || salItem.calculation_type == 'subtract')" class="text-right text-red-500">
                                        - {{
                                            salItem.detail_item.total_value ? new Intl.NumberFormat('id-ID', {
                                                style: 'currency',
                                                currency: 'IDR'
                                            }).format(salItem.detail_item.total_value) : '-'
                                        }}
                                    </TableCell>
                                    <TableCell v-if="isEdit" class="text-right text-red-500">
                                        <Button @click="deleteItem(index)">
                                            <TrashIcon class="size-5"/>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="isEdit">
                                    <TableCell colspan="8" class="text-end">
                                        <Button @click="addPayrollItem()" class="bg-black">Add New Data
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="!data.company_salary_item || data.company_salary_item.length == 0">
                                    <TableCell colspan="6" class="text-center">
                                        <div class="inline-flex gap-2 items-center text-lg">
                                            <TriangleAlert class="text-destructive animate-pulse"/>
                                            Tidak ada data
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <div class="w-full flex flex-col gap-4 md:gap-0">
                            <div class="md:flex grid justify-between items-center">
                                <span class="text-sm font-medium">Work Hours</span>
                                <span v-if="!isEdit" class="font-bold">{{ data.work_hour }} hours</span>
                            </div>
                            <div class="md:flex grid justify-between items-center">
                                <span class="text-sm font-medium">Total Presence</span>
                                <span v-if="!isEdit" class="font-bold">{{ data.total_presence_record || 0 }} presence</span>
                            </div>
                            <div class="md:flex grid justify-between items-center">
                                <span class="text-sm font-medium">Total Salary</span>
                                <span v-if="!isEdit" class=""> {{
                                        data.total_salary ? new Intl.NumberFormat('id-ID', {
                                            style: 'currency',
                                            currency: 'IDR'
                                        }).format(data.total_salary) : '-'
                                    }}</span>
                            </div>
                            <div class="md:flex grid justify-between items-center">
                                <span class="text-sm font-medium">Total Subtraction</span>
                                <span v-if="!isEdit" class="text-red-500"> - {{
                                        data.total_subtract ? new Intl.NumberFormat('id-ID', {
                                            style: 'currency',
                                            currency: 'IDR'
                                        }).format(data.total_subtract) : '-'
                                    }}</span>
                            </div>
                            <div class="md:flex grid justify-between items-center">
                                <span class="text-sm font-medium">Total Tax</span>
                                <span v-if="!isEdit" class="text-red-500"> - {{
                                        data.total_tax ? new Intl.NumberFormat('id-ID', {
                                            style: 'currency',
                                            currency: 'IDR'
                                        }).format(data.total_tax) : '-'
                                    }}</span>
                            </div>
                            <div class="md:flex grid justify-between items-center">
                                <span class="text-sm font-medium">Total Salary After Tax</span>
                                <span v-if="!isEdit" class="font-bold"> {{
                                        data.salary_after_tax ? new Intl.NumberFormat('id-ID', {
                                            style: 'currency',
                                            currency: 'IDR'
                                        }).format(data.salary_after_tax) : '-'
                                    }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col mt-2">
                        <span class="text-sm text-muted-foreground">
                            *This receipt is automatically generated
                        </span>
                            <span class="text-sm text-muted-foreground">
                            *If there any mistake, try contact your administrator
                        </span>
                        </div>

                        <div class="inline-grid justify-items-center items-center grid-cols-3 mt-2">
                            <Separator class="w-full"/>
                            <span class="text-center text-sm text-muted-foreground">End of Receipt</span>
                            <Separator class="w-full"/>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
        <div class="container mx-auto w-full max-w-3xl p-4">
            <div class="inline-flex w-full items-center justify-between gap-2">
                <div class="space-x-2">
                    <Button :disabled="isEdit" @click="downloadPDF()" variant="outline">Export PDF</Button>
                    <Button :disabled="isEdit" @click="exportDetailExcel()" variant="outline">Export Excel</Button>
                    <Button  v-if="data.status == 'pending' && !data.user?.deleted_at" @click="updatePayroll()" class="bg-amber-500">{{
                            isEdit ? 'Save Receipt' : 'Edit Receipt'
                        }}
                    </Button>
                </div>
                <div class="space-x-2" v-if="data.status == 'pending' && !data.user?.deleted_at">
                    <Button @click="confirmPayroll(false)" :disabled="isEdit" class="bg-black">Reject</Button>
                    <Button @click="confirmPayroll(true)" :disabled="isEdit">Approve</Button>
                </div>
            </div>

            <div class="flex flex-col mt-4">
                 <span v-if="isEdit" class="text-sm text-muted-foreground">
                            *Exit edit mode first to accept or reject receipt
                        </span>
                <span class="text-sm text-muted-foreground">
                            *Approve button will send the receipt to employee email & status will be change to completed,
                            make sure the receipt was correct.
                        </span>
                <span class="text-sm text-muted-foreground">
                            *After receipt was approved you can't make edit or delete
                        </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
