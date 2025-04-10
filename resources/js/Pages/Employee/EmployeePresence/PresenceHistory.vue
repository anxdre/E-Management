<script setup lang="ts">
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import {
    ArrowLeftCircle,
    ArrowRightCircle,
    Calendar,
    DollarSign,
    LoaderCircleIcon,
    LucideAlarmClockPlus,
    LucideClock,
    MoreHorizontal,
    PlusCircle,
    Search,
    TriangleAlert,
    RotateCw
} from 'lucide-vue-next'
import { Button } from '@/shadcn/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/shadcn/ui/card'
import { CrudDialogAdapter } from "@/lib/DialogState/CrudDialogAdapter";
import { onMounted, reactive, ref } from "vue";
import axios from "axios";
import { errorToast, navigateLink, PaginationOption, successToast } from "@/lib/utils";
import { debounceFilter, watchPausable } from "@vueuse/core";
import { Input } from "@/shadcn/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger } from "@/shadcn/ui/select";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger
} from "@/shadcn/ui/dropdown-menu";
import { Badge } from "@/shadcn/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/shadcn/ui/table";

defineOptions({
    layout: LayoutWrapper
})

const dataset = ref([])
const selectedData = ref()
const isLoading = ref(false)
const paginateControl = reactive(new PaginationOption())
const paginationWatcher = watchPausable(
    paginateControl,
    (value) => {
        getDataset()
    }, { eventFilter: debounceFilter(800) }
)
const filterControl = ref({
    filterBy:'',
    sortBy:'',
    orderBy:''
})

function getDataset() {
    paginationWatcher.pause()
    isLoading.value = true
    axios.get(route('presence-location.json.all', {
        page: paginateControl.currentPage,
        search: paginateControl.searchQuery
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

function deleteItem(dataId: number) {
    axios.delete(route('presence-location.json.delete'), { data: { data_id: dataId } })
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            successToast('Success', message)
            getDataset()
        })
        .catch((err) => {
            errorToast('Error !', err.response.data.message)
        })
        .finally(() => {
        })
}

onMounted(() => {
    getDataset()
})

const dialogState = reactive(new CrudDialogAdapter())
</script>

<template>
    <Head>
        <title>Presence History</title>
    </Head>
    <main class="flex-1 items-start gap-4 p-4 sm:px-6 md:gap-4">
        <Card>
            <CardHeader>
                <CardTitle>Employee Presence History</CardTitle>
                <CardDescription class="inline-flex justify-between items-center">
                    Manage your presence history and view their related information.
                </CardDescription>
                <div class="grid gap-4 md:grid-cols-2 md:gap-8 lg:grid-cols-4 p-4">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                Total Day Work
                            </CardTitle>
                            <Calendar class="h-4 w-4 text-muted-foreground"/>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                3
                            </div>
                            <p class="text-xs text-muted-foreground">
                                -2 since yesterday
                            </p>
                        </CardContent>
                    </Card>
                    <Card class="outline outline-green-500">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                Total On Time Presence
                            </CardTitle>
                            <LucideClock class="h-4 w-4 text-muted-foreground "/>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                12
                            </div>
                            <p class="text-xs text-muted-foreground">
                                +20.1% from last month
                            </p>
                        </CardContent>
                    </Card>
                    <Card class="outline outline-destructive">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                Total Late Presence
                            </CardTitle>
                            <LucideAlarmClockPlus class="h-4 w-4 text-muted-foreground"/>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                23
                            </div>
                            <p class="text-xs text-muted-foreground">
                                +180.1% from last month
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                Total Earnings
                            </CardTitle>
                            <DollarSign class="h-4 w-4 text-muted-foreground"/>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                35
                            </div>
                            <p class="text-xs text-muted-foreground">
                                +2 since yesterday
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </CardHeader>
            <CardContent class="h-full max-h-screen overflow-y-scroll">
                <div class="ml-auto justify-end flex items-center gap-2 px-4">
                        <Button @click="navigateLink(route('presence-location.create'))" size="sm"
                                class="h-7 gap-1 bg-black w-full md:w-fit">
                            <PlusCircle class="h-3.5 w-3.5"/>
                            <span class=" sm:not-sr-only sm:whitespace-nowrap">Add new presence</span>
                        </Button>
                    </div>
                <div class="flex flex-col gap-2 p-4">
                    <div class="flex flex-col items-center md:flex-row justify-between gap-2">
                        <Select v-model="filterControl.filterBy">
                            <SelectTrigger class="w-full md:w-1/6">
                                <ul>{{filterControl.filterBy.title || 'Filter By'}}</ul>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="{id:location,title:'location'}">location</SelectItem>
                                <SelectItem :value="{id:location,title:'presence status'}">presence status</SelectItem>
                                <SelectItem :value="{id:admin_status,title:'status by admin'}">status by admin</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select v-model="filterControl.sortBy">
                            <SelectTrigger class="w-full md:w-1/6">
                                <ul>{{filterControl.sortBy.title || 'Value'}}</ul>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="{id:'in',title:'In'}">In</SelectItem>
                                <SelectItem :value="{id:'out',title:'Out'}">Out</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select v-model="filterControl.orderBy">
                            <SelectTrigger class="w-full md:w-1/6">
                                <ul>{{filterControl.orderBy.title || 'Order From'}}</ul>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="{id:'asc',title:'Newest'}">Newest</SelectItem>
                                <SelectItem :value="{id:'desc',title:'Oldest'}">Oldest</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button @click="()=>{filterControl.filterBy = '';filterControl.orderBy = '';filterControl.sortBy = ''}" size="sm" class="h-7 gap-1 bg-black w-full md:w-fit group">
                            <RotateCw class="h-3.5 w-3.5"/> <span class="block md:hidden group-hover:inline sm:not-sr-only sm:whitespace-nowrap">Reset Filter</span>
                        </Button>

                        <div class="relative w-full md:ml-auto flex-1 md:grow-0">
                            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground"/>
                            <Input
                                v-model:model-value="paginateControl.searchQuery"
                                type="search"
                                placeholder="Search..."
                                class="w-full rounded-lg bg-background pl-8 md:w-[200px] lg:w-[320px]"
                            />
                        </div>
                    </div>
                </div>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="hidden w-[100px] sm:table-cell text-center">
                                No
                            </TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead>Location</TableHead>
                            <TableHead>Presence Status</TableHead>
                            <TableHead class="hidden w-[100px] sm:table-cell text-center text-nowrap">Status By Admin
                            </TableHead>
                            <TableHead class="text-center">
                                Action
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(account,index) in dataset" :key="account.id">
                            <TableCell class="hidden sm:table-cell text-center">
                                {{ index + 1 }}
                            </TableCell>
                            <TableCell class="font-medium">
                                {{ account.user_detail?.fullname ?? '-' }}
                            </TableCell>
                            <TableCell class="font-medium text-ellipsis overflow-ellipsis">
                                {{ account.user_detail?.phone ?? '-' }}
                            </TableCell>
                            <TableCell class="font-medium">
                                {{ account.user_detail?.address ?? '-' }}
                            </TableCell>
                            <TableCell class="hidden sm:table-cell text-center">
                                <Badge v-if="!account.is_suspended" variant="success">
                                    Active
                                </Badge>
                                <Badge v-else variant="destructive">
                                    Suspended
                                </Badge>
                            </TableCell>
                            <TableCell class="text-center">
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
                                            @click="()=>{navigateLink(route('employee-account.detail',{id:account.id}))}"
                                            class="cursor-pointer">View Account
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="dialogState.delete.data = account;dialogState.delete.state = true"
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
                        location
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
