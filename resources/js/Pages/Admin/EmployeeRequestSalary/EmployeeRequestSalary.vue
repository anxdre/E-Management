<script setup lang="ts">
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import {
    ArrowLeftCircle,
    ArrowRightCircle,
    CalendarIcon,
    Check,
    LoaderCircleIcon,
    Search,
    TriangleAlert,
    X,
} from 'lucide-vue-next'

import { Badge } from '@/shadcn/ui/badge'
import { Button } from '@/shadcn/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/shadcn/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow, } from '@/shadcn/ui/table'
import type { DateRange } from 'reka-ui'
import { onMounted, reactive, ref } from "vue";
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/shadcn/ui/select'
import { Popover, PopoverContent, PopoverTrigger } from '@/shadcn/ui/popover'
import { RangeCalendar } from '@/shadcn/ui/range-calendar'
import dayjs from 'dayjs'

defineOptions({
    layout: LayoutWrapper
})

const dataset = ref()
const isLoading = ref(false)
const paginateControl = reactive(new PaginationOption())
const filterStatus = ref('all')
const dateFilter = ref<DateRange>()

const paginationWatcher = watchPausable(
    paginateControl,
    () => { getDataset(paginateControl.currentPage) },
    { eventFilter: debounceFilter(800) }
)

const dialogState = reactive({
    approve: { show: false, data: null as any },
    reject: { show: false, data: null as any },
})

function getDataset(page: number) {
    paginationWatcher.pause()
    isLoading.value = true
    const params: Record<string, any> = { page, search: paginateControl.searchQuery }
    if (filterStatus.value && filterStatus.value !== 'all') params.status = filterStatus.value
    if (dateFilter.value?.start) params.date_start = dateFilter.value.start.toDate('Asia/Jakarta').toISOString().split('T')[0]
    if (dateFilter.value?.end) params.date_end = dateFilter.value.end.toDate('Asia/Jakarta').toISOString().split('T')[0]

    axios.get(route('employee-request-salary.json.all', params))
        .then(({ data: { data: dataFromServer } }) => {
            dataset.value = dataFromServer.data
            paginateControl.currentPage = dataFromServer.current_page
            paginateControl.nextPageUrl = dataFromServer.next_page_url
            paginateControl.prevPageUrl = dataFromServer.prev_page_url
            paginateControl.from = dataFromServer.from
            paginateControl.to = dataFromServer.to
            paginateControl.totalData = dataFromServer.total
            paginateControl.perPageData = dataFromServer.per_page
        })
        .catch((err) => { errorToast('Error !', err.response.data.message) })
        .finally(() => { paginationWatcher.resume(); isLoading.value = false })
}

function approveReject(id: number, status: 'approved' | 'rejected') {
    axios.put(route('employee-request-salary.json.approve-reject'), { id, status })
        .then(({ data: { message } }) => {
            successToast('Success', message)
            dialogState.approve.show = false
            dialogState.reject.show = false
            getDataset(paginateControl.currentPage)
        })
        .catch((err) => { errorToast('Error !', err.response.data.message) })
}

const statusBadgeVariant = (status: string) => {
    switch (status) {
        case 'approved': return 'success'
        case 'rejected': return 'destructive'
        default: return 'warning'
    }
}

onMounted(() => { getDataset(1) })
</script>

<template>
    <main class="flex-1 items-start gap-4 p-4 sm:px-6 md:gap-4">
        <Card>
            <CardHeader>
                <div class="flex justify-between items-center">
                    <div>
                        <CardTitle>Employee Request Salary</CardTitle>
                        <CardDescription>Manage employee requests for additional salary components</CardDescription>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="relative w-64">
                        <Search class="absolute left-2 top-2.5 size-4 text-muted-foreground"/>
                        <Input placeholder="Search employee..." class="pl-8" v-model="paginateControl.searchQuery"/>
                    </div>
                    <div class="w-36">
                        <Select v-model="filterStatus" @update:modelValue="getDataset(1)">
                            <SelectTrigger>
                                <SelectValue placeholder="All Status"/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Status</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="approved">Approved</SelectItem>
                                <SelectItem value="rejected">Rejected</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button variant="outline" class="w-fit justify-start text-left font-normal gap-2">
                                    <CalendarIcon class="size-4"/>
                                    {{ dateFilter?.start ? dayjs(dateFilter.start.toDate('Asia/Jakarta')).format('DD/MM/YYYY') : 'Start' }}
                                    -
                                    {{ dateFilter?.end ? dayjs(dateFilter.end.toDate('Asia/Jakarta')).format('DD/MM/YYYY') : 'End' }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0">
                                <RangeCalendar v-model="dateFilter" class="rounded-md border"/>
                                <div class="flex justify-end p-2 border-t">
                                    <Button size="sm" @click="getDataset(1)">Apply</Button>
                                </div>
                            </PopoverContent>
                        </Popover>
                    </div>
                    <Button v-if="dateFilter?.start || dateFilter?.end" variant="ghost" size="sm" @click="dateFilter = undefined; getDataset(1)">
                        <X class="size-3"/> Clear
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>No</TableHead>
                            <TableHead>Employee</TableHead>
                            <TableHead>Component</TableHead>
                            <TableHead class="text-center">Quantity</TableHead>
                            <TableHead class="text-center">Status</TableHead>
                            <TableHead class="text-center">Request Date</TableHead>
                            <TableHead class="text-center">Approved Date</TableHead>
                            <TableHead class="text-center">Realized</TableHead>
                            <TableHead class="text-center">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="isLoading">
                            <TableCell colspan="9" class="text-center">
                                <LoaderCircleIcon class="animate-spin mx-auto"/>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="!isLoading && (!dataset || dataset.length == 0)">
                            <TableCell colspan="9" class="text-center">
                                <div class="inline-flex gap-2 items-center text-lg">
                                    <TriangleAlert class="text-destructive animate-pulse"/>
                                    Tidak ada data
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="(item, index) in dataset" :key="item.id">
                            <TableCell>{{ index + 1 + (paginateControl.perPageData * (paginateControl.currentPage - 1)) }}</TableCell>
                            <TableCell class="font-medium">{{ item.user?.user_detail?.fullname || 'Deleted User' }}</TableCell>
                            <TableCell>{{ item.component?.name || '-' }}</TableCell>
                            <TableCell class="text-center">{{ item.quantity }}</TableCell>
                            <TableCell class="text-center">
                                <Badge :variant="statusBadgeVariant(item.status)">{{ item.status }}</Badge>
                            </TableCell>
                            <TableCell class="text-center">
                                {{ item.created_at ? dayjs(item.created_at).format('DD/MM/YYYY') : '-' }}
                            </TableCell>
                            <TableCell class="text-center">
                                {{ item.approved_date ? dayjs(item.approved_date).format('DD/MM/YYYY') : '-' }}
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge :variant="item.is_realized ? 'success' : 'secondary'">
                                    {{ item.is_realized ? 'Yes' : 'No' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-center">
                                <div v-if="item.status === 'pending'" class="inline-flex gap-1">
                                    <Button size="sm" variant="success" class="size-7 p-0"
                                            @click="dialogState.approve = { show: true, data: item }">
                                        <Check class="size-4"/>
                                    </Button>
                                    <Button size="sm" variant="destructive" class="size-7 p-0"
                                            @click="dialogState.reject = { show: true, data: item }">
                                        <X class="size-4"/>
                                    </Button>
                                </div>
                                <span v-else class="text-xs text-muted-foreground">
                                    {{ item.status === 'approved' ? 'Approved' : 'Rejected' }}
                                </span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
            <CardFooter v-if="dataset && dataset.length > 0" class="flex justify-between items-center">
                <span class="text-xs text-muted-foreground">
                    Showing {{ paginateControl.from }} to {{ paginateControl.to }} of {{ paginateControl.totalData }}
                </span>
                <div class="inline-flex gap-2">
                    <Button variant="outline" size="sm" :disabled="!paginateControl.prevPageUrl"
                            @click="getDataset(paginateControl.currentPage - 1)">
                        <ArrowLeftCircle class="size-4"/> Prev
                    </Button>
                    <Button variant="outline" size="sm" :disabled="!paginateControl.nextPageUrl"
                            @click="getDataset(paginateControl.currentPage + 1)">
                        Next <ArrowRightCircle class="size-4"/>
                    </Button>
                </div>
            </CardFooter>
        </Card>

        <!-- Approve Confirmation Dialog -->
        <Dialog v-model:open="dialogState.approve.show">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Approve Request</DialogTitle>
                    <DialogDescription>
                        Approve request from <strong>{{ dialogState.approve.data?.user?.user_detail?.fullname }}</strong>
                        for component <strong>{{ dialogState.approve.data?.component?.name }}</strong>
                        (qty: {{ dialogState.approve.data?.quantity }}).
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button @click="approveReject(dialogState.approve.data.id, 'approved')">Approve</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Reject Confirmation Dialog -->
        <Dialog v-model:open="dialogState.reject.show">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reject Request</DialogTitle>
                    <DialogDescription>
                        Reject request from <strong>{{ dialogState.reject.data?.user?.user_detail?.fullname }}</strong>
                        for component <strong>{{ dialogState.reject.data?.component?.name }}</strong>.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="approveReject(dialogState.reject.data.id, 'rejected')">Reject</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </main>
</template>
