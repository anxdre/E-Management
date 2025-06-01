<script setup lang="ts">
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'
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
    RotateCw,
    Search,
    TriangleAlert
} from 'lucide-vue-next'
import { Button } from '@/shadcn/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/shadcn/ui/card'
import { CrudDialogAdapter } from "@/lib/DialogState/CrudDialogAdapter";
import { computed, onMounted, reactive, ref } from "vue";
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger
} from "@/shadcn/ui/dialog";
import { Label } from "@/shadcn/ui/label";
import { usePage } from "@inertiajs/vue3";
import MapView from "@/Components/MapView.vue";
import { LngLat } from "@tomtom-international/web-sdk-maps";
import DateTimePicker from "@/Components/DateTimePicker.vue";

defineOptions({
    layout: LayoutWrapper
})
const dialogState = reactive(new CrudDialogAdapter())
const dataset = ref([])
const isLoading = ref(false)
const paginateControl = reactive(new PaginationOption())
const paginationWatcher = watchPausable(
    paginateControl,
    (value) => {
        getDataset()
    }, { eventFilter: debounceFilter(800) }
)
const filterControl = ref({
    filterBy: '',
    sortBy: '',
    orderBy: '',
    location_id: '',
})

const form = ref({
    user_id: '',
    location_id: '',
    latitude: '',
    longitude: '',
    code: '',
    note: '',
    time:'',
    time_in:undefined,
    time_out:undefined,
    status:'in',
    attachment: null as File | null
})
const locationDataSet = ref([])
const profile = ref({})

function getDataset() {
    paginationWatcher.pause()
    isLoading.value = true
    axios.get(route('employee-presence.json.all',{user:profile.value.id}))
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

    // function deleteItem(dataId: number) {
    //     axios.delete(route('presence-location.json.delete'), { data: { data_id: dataId } })
    //         .then(({ data: { data: dataFromServer, message, status_code } }) => {
    //             successToast('Success', message)
    //             getDataset()
    //         })
    //         .catch((err) => {
    //             errorToast('Error !', err.response.data.message)
    //         })
    //         .finally(() => {
    //         })
    // }
const handleSubmit = async () => {
    isLoading.value = true
    axios.post(route('employee-presence.json.create', { user: profile.value.id }),form.value)
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            if(statusCode != 200){
                errorToast('Error',message)
            }
            locationDataSet.value = dataFromServer.data
            successToast('Success','Presence Saved')
        })
        .catch((err) => {
            errorToast('Error !', err.response.data.message)
        })
        .finally(() => {
            paginationWatcher.resume()
            isLoading.value = false
        })
}

const formatDate = (date) => {
    const day = date.getDate();
    const month = date.getMonth() + 1;
    const year = date.getFullYear();

    return `${day}/${month}/${year}`;
}

function getAllLocation() {
    isLoading.value = true
    axios.get(route('presence-location.json.all',))
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            locationDataSet.value = dataFromServer.data
        })
        .catch((err) => {
            errorToast('Error !', err.response.data.message)
        })
        .finally(() => {
            isLoading.value = false
        })
}

async function getProfile() {
    let page = usePage()
    let path = page.url
    let match = path.match(/\/Employee\/(\d+)\/Presence-History/)
    let userId = match?.[1] ?? null
    if (!userId) {
        return
    }
    isLoading.value = true
    try {
        const response = await axios.get(route('employee-account.json.detail', { user: userId }))
        const { data: dataFromServer, message, status_code } = response.data
        profile.value = dataFromServer
        form.value.user_id = dataFromServer.id
    } catch (err) {
        errorToast('Error', 'Profile not loaded yet, please reload the page')
    } finally {
        isLoading.value = false
    }
}

const markerPosition = computed({
    get(): LngLat {
        return new LngLat(form.value.longitude ?? 112.7166368, form.value.latitude ?? -7.272563) // Default position
    },

    set(newValue: LngLat) {
        form.value.longitude = newValue.lng
        form.value.latitude = newValue.lat
    }
})

async function getCurrentLocation() {
    try {
        const position = await new Promise<{ lat: number; lng: number }>((resolve, reject) => {
            if (!navigator.geolocation) {
                alert("Geolocation tidak didukung di browser ini.");
                return resolve(null);
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;
                    resolve({ lat: latitude, lng: longitude });
                },
                (error) => {
                    alert(`Gagal mendapatkan lokasi: ${error.message}`);
                    resolve(null);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });

        // Kalau dapat lokasi, update reactive state
        if (position.lng) {
            markerPosition.value = new LngLat(position.lng, position.lat)
        }
    } catch (error) {
        console.error("Error mendapatkan lokasi:", error);
    }
}

onMounted(async () => {
    await getProfile()
    getDataset()
    getAllLocation()
    await getCurrentLocation()
})
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
                    <Dialog>
                        <DialogTrigger>
                            <Button size="sm"
                                    class="h-7 gap-1 bg-black w-full md:w-fit">
                                <PlusCircle class="h-3.5 w-3.5"/>
                                <span class=" sm:not-sr-only sm:whitespace-nowrap">Add new presence</span>
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Add New Presence</DialogTitle>
                                <DialogDescription>Fill the form below to add employee presence</DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-4 py-4">
                                <div class="grid grid-cols-4 items-center gap-4">
                                    <Label for="user_id" class="text-right">User</Label>
                                    <Input disabled id="user_id" :model-value="profile.user_detail.fullname"
                                           class="col-span-3" type="text"/>
                                </div>

                                <div class="grid grid-cols-4 items-center gap-4">
                                    <Label for="location_id" class="text-right">Location</Label>
                                    <Select v-model="form.location_id" id="location_id">
                                        <SelectTrigger class="col-span-3">
                                            <ul>{{
                                                    locationDataSet.find((item) => item.id == form.location_id)?.name || 'Select Location'
                                                }}
                                            </ul>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="location in locationDataSet" :value="location.id">
                                                {{ location.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div v-if="$attrs.auth.user.type == 'employee'"
                                     class="grid grid-cols-4 items-center gap-4">
                                    <Label for="code" class="text-right">Verification Code</Label>
                                    <Input id="code" v-model="form.code" class="col-span-3"/>
                                </div>

                                <div v-if="$attrs.auth.user.type == 'company'"
                                     class="grid grid-cols-4 items-center gap-4">
                                    <Label for="code" class="text-right">Time In</Label>
                                    <VueDatePicker :preview-format="formatDate" class="min-w-max" v-model:modelValue="form.time_in" />
                                </div>

                                <div v-if="$attrs.auth.user.type == 'company'"
                                     class="grid grid-cols-4 items-center gap-4">
                                    <Label for="code" class="text-right">Time Out</Label>
                                    <VueDatePicker :preview-format="formatDate" class="min-w-max" v-model:modelValue="form.time_out" />
                                </div>

                                <div class="grid grid-cols-4 items-center gap-4">
                                    <Label for="map" class="text-right">Location</Label>
                                    <MapView id="map" class="h-[100px] md:h-[200px] col-span-3"
                                             v-model:marker-position="markerPosition" marker-radius="0"/>
                                </div>

                                <div class="grid grid-cols-4 items-center gap-4">
                                    <Label for="note" class="text-right">Note</Label>
                                    <Input id="note" v-model="form.note" class="col-span-3"/>
                                </div>

                                <div class="grid grid-cols-4 items-center gap-4">
                                    <Label for="attachment" class="text-right">Attachment</Label>
                                    <Input id="attachment" class="col-span-3" type="file"
                                           @change="e => form.attachment = e.target.files?.[0] ?? null"/>
                                </div>
                            </div>

                            <DialogFooter>
                                <Button @click="handleSubmit">
                                    Create Presence
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
                <div class="flex flex-col gap-2 p-4">
                    <div class="flex flex-col items-center md:flex-row justify-between gap-2">
                        <Select v-model="filterControl.filterBy">
                            <SelectTrigger class="w-full md:w-1/6">
                                <ul>{{ filterControl.filterBy.title || 'Filter By' }}</ul>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="{id:'location',title:'location'}">location</SelectItem>
                                <SelectItem :value="{id:'presence_status',title:'presence status'}">presence status
                                </SelectItem>
                                <SelectItem :value="{id:'admin_status',title:'status by admin'}">status by admin
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div class="w-full" v-if="filterControl.filterBy.id == 'location'">
                            <Select v-model="filterControl.location_id" id="location_id">
                                <SelectTrigger>
                                    <ul>{{
                                            locationDataSet.find((item) => item.id == filterControl.location_id)?.name || 'Select Location'
                                        }}
                                    </ul>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="location in locationDataSet" :value="location.id">
                                        {{ location.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="w-full inline-flex gap-2" v-if="filterControl.filterBy">
                            <Select v-model="filterControl.sortBy">
                                <SelectTrigger>
                                    <ul>{{ filterControl.sortBy.title || 'Value' }}</ul>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="{id:'in',title:'In'}">In</SelectItem>
                                    <SelectItem :value="{id:'out',title:'Out'}">Out</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="filterControl.orderBy">
                                <SelectTrigger>
                                    <ul>{{ filterControl.orderBy.title || 'Order From' }}</ul>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="{id:'asc',title:'Newest'}">Newest</SelectItem>
                                    <SelectItem :value="{id:'desc',title:'Oldest'}">Oldest</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Button
                            @click="()=>{filterControl.filterBy = '';filterControl.orderBy = '';filterControl.sortBy = ''}"
                            size="sm" class="h-7 gap-1 bg-black w-full md:w-fit group">
                            <RotateCw class="h-3.5 w-3.5"/>
                            <span class="block md:hidden group-hover:inline sm:not-sr-only sm:whitespace-nowrap">Reset Filter</span>
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
