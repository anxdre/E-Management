<script setup lang="ts">
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'
import {
    ArrowLeftCircle,
    ArrowRightCircle,
    Calendar,
    DollarSign,
    DownloadIcon,
    FileSpreadsheet,
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
import { computed, onMounted, reactive, ref, watch } from "vue";
import axios from "axios";
import { appUrl, errorToast, formatDate, formatWorkingHour, PaginationOption, successToast } from "@/lib/utils";
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
    DialogClose,
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
import dayjs from "dayjs";
import { Textarea } from "@/shadcn/ui/textarea";
import CustomLink from "@/Components/CustomLink.vue";

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
const filterWatcher = watchPausable(filterControl.value, () => {
    if (filterControl.value.filterBy && (filterControl.value.sortBy || filterControl.value.location_id)) {
        getDataset()
    }
}, { eventFilter: debounceFilter(800) })

const form = ref({
    mst_user_id: '',
    location_id: '',
    latitude: '',
    longitude: '',
    code: '',
    note: '',
    time: '',
    time_in: undefined,
    time_out: undefined,
    status: 'in',
    attachment: null as File | null
})
const locationDataSet = ref([])
const profile = ref({})
const stats = ref({
    total_day_work: 0,
    total_on_time: 0,
    total_late: 0,
    total_earnings: 0,
})

function getStats() {
    axios.get(route('employee-presence.json.stats', { user: profile.value.id }))
        .then(({ data: { data } }) => {
            stats.value = data
        })
}

function getDataset() {
    paginationWatcher.pause()
    filterWatcher.pause()
    isLoading.value = true
    axios.get(route('employee-presence.json.all', {
        user: profile.value.id,
        location_id: filterControl.value.location_id,
        status: filterControl.value.sortBy.toLowerCase(),
        orderBy: filterControl.value.orderBy.id,
        ...paginateControl
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
            filterWatcher.resume()
            isLoading.value = false
        })
}

function deleteItem(dataId: number) {
    dialogState.delete.progress = true
    axios.delete(route('employee-presence.json.delete', { user: profile.value.id }), { data: { id: dataId } })
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            successToast('Success', message)
            getDataset()
            dialogState.delete.state = false
            dialogState.delete.data = undefined
        })
        .catch((err) => {
            errorToast('Error !', err.response.data.message)
        })
        .finally(() => {
            dialogState.delete.progress = false
        })
}

const isEditing = ref(false)
const editForm = ref({
    time_in: null as string | null,
    time_out: null as string | null,
})
const confirmDialog = ref({
    show: false,
    status: '' as 'approved' | 'rejected' | '',
    progress: false,
})

watch(() => dialogState.show.state, (val) => {
    if (val && dialogState.show.data) {
        editForm.value = {
            time_in: dialogState.show.data.time_in,
            time_out: dialogState.show.data.time_out,
        }
        isEditing.value = false
    }
})

function enterEditMode() {
    editForm.value = {
        time_in: dialogState.show.data.time_in,
        time_out: dialogState.show.data.time_out,
    }
    isEditing.value = true
}

function cancelEdit() {
    isEditing.value = false
    editForm.value = { time_in: null, time_out: null }
}

function updateStatus(status: 'approved' | 'rejected') {
    confirmDialog.value = { show: true, status, progress: false }
}

function confirmAction() {
    const status = confirmDialog.value.status as 'approved' | 'rejected'
    confirmDialog.value.progress = true
    isEditing.value = false
    dialogState.show.state = false
    axios.put(route('employee-presence.json.status', { user: profile.value.id }), {
        id: dialogState.show.data.id,
        status,
        time_in: editForm.value.time_in,
        time_out: editForm.value.time_out,
    })
        .then(({ data: { message } }) => {
            successToast('Success', message)
            getDataset()
            confirmDialog.value = { show: false, status: '', progress: false }
        })
        .catch((err) => {
            errorToast('Error !', err.response.data.message)
            confirmDialog.value = { show: false, status: '', progress: false }
        })
}

function exportExcel() {
    const params = {
        status: filterControl.value.sortBy,
        location_id: filterControl.value.location_id,
        date_start: '',
        date_end: '',
        orderBy: filterControl.value.orderBy?.id || 'desc',
    }
    const url = route('employee-presence.export.excel', { user: profile.value.id, ...params })
    window.open(url, '_blank')
}

const handleSubmit = async () => {
    isLoading.value = true
    dialogState.add.progress = true
    axios.postForm(route('employee-presence.json.create', { user: profile.value.id }), form.value)
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            if (status_code != 200) {
                errorToast('Error', message)
            }
            successToast('Success', 'Presence Saved')
            dialogState.add.state = false
            getDataset()
        })
        .catch((err) => {
            console.log(err)
            errorToast('Error !', err.response.data.message)
        })
        .finally(() => {
            dialogState.add.progress = false
            isLoading.value = false
        })
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
        form.value.mst_user_id = dataFromServer.id
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
    await getAllLocation()
    getDataset()
    getStats()
    await getCurrentLocation()
})


</script>

<template>
    <Head>
        <title>Presence History</title>
    </Head>
    <main class="flex-1 items-start gap-4 p-4 sm:px-6 md:gap-4">
        <Dialog v-model:open="dialogState.delete.state">
            <DialogContent>
                <DialogHeader>
                    Are you sure to delete this presence data ?
                </DialogHeader>
                <DialogDescription class="inline-flex items-center gap-2">
                    This action can't be undone
                    <loader-circle-icon v-if="dialogState.delete.progress" class="animate-spin"/>
                </DialogDescription>
                <DialogFooter>
                    <div class="space-x-2">
                        <Button @click="
                        deleteItem(dialogState.delete.data.id)"
                                v-bind:disabled="dialogState.delete.progress" class="bg-black">Yes
                        </Button>
                        <Button variant="outline">Cancel</Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <Dialog v-model:open="dialogState.show.state">
            <DialogContent class="lg:max-w-screen-md grid-rows-[auto_minmax(0,1fr)_auto] p-0 max-h-[90dvh]">
                <DialogHeader class="p-6 pb-0">
                    <DialogTitle>Detail Presence</DialogTitle>
                    <DialogDescription>
                        Detailed information about your presence
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4 overflow-y-auto px-6">
                    <div class="flex flex-col space-y-4">
                        <div class="border p-8 rounded-md relative">
                            <Badge variant="secondary" class="absolute -top-3 left-8">Presence Position</Badge>
                            <MapView id="map" class="h-[200px] md:h-[400px] col-span-3"
                                     view-only="true"
                                     :marker-position="new LngLat(dialogState.show.data.longitude ?? 112.7166368, dialogState.show.data.latitude ?? -7.272563)"
                                     marker-radius="0"/>
                        </div>
                        <div class="border p-8 space-y-4 rounded-md relative">
                            <Badge variant="secondary" class="absolute -top-3 left-8">Working Hour</Badge>
                            <div class="grid md:grid-cols-6 items-center gap-4 !mt-0">
                                <Label for="code" class="col-span-1 md:text-right">Time In</Label>
                                <VueDatePicker :disabled="!isEditing" :format="formatDate(editForm.time_in)"
                                               :preview-format="formatDate" class=" col-span-2"
                                               v-model="editForm.time_in"/>

                                <Label for="code" class="col-span-1 md:text-right">Working Hour</Label>
                                <Input disabled class=" col-span-2"
                                       :model-value="formatWorkingHour(editForm.time_in, editForm.time_out) || '-'"/>

                            </div>


                            <div class="grid md:grid-cols-6 items-center gap-4">
                                <Label for="code" class="col-span-1 md:text-right">Time Out</Label>
                                <VueDatePicker :disabled="!isEditing" :format="formatDate(editForm.time_out)"
                                               :preview-format="formatDate" class="col-span-2 "
                                               v-model="editForm.time_out"/>

                                <Label for="code" class="col-span-1 md:text-right">Extended Hour</Label>
                                <Input disabled class=" col-span-2"
                                       :model-value="dialogState.show.data.exteded_time || '0'"/>
                            </div>
                        </div>

                        <div class="border p-8 space-y-4 rounded-md relative">
                            <Badge variant="secondary" class="absolute -top-3 left-8">Additional Information</Badge>

                            <div class="grid md:grid-cols-4 items-center gap-4 !mt-0">
                                <Label for="note" class="md:text-right">Note</Label>
                                <Input disabled class="col-span-3" :model-value="dialogState.show.data.note || '-'"/>
                            </div>

                            <div class="grid md:grid-cols-4 items-center gap-4">
                                <Label for="note" class="md:text-right">Status By Admin</Label>
                                <Badge class="place-content-center"
                                       v-if="dialogState.show.data.status_by_admin == 'pending'"
                                       variant="warning">Pending
                                </Badge>
                                <Badge class="place-content-center"
                                       v-if="dialogState.show.data.status_by_admin == 'rejected'"
                                       variant="destructive">Rejected
                                </Badge>
                                <Badge class="place-content-center"
                                       v-if="dialogState.show.data.status_by_admin == 'approved'"
                                       variant="success">Approved
                                </Badge>
                            </div>

                            <div class="grid md:grid-cols-4 items-center gap-4">
                                <Label for="attachment" class="md:text-right">Attachment</Label>
                                <a :href="`${appUrl}/storage/${dialogState.show.data.attachment}`" class="inline-flex bg-primary text-white items-center justify-center
                                 text-sm p-1 rounded-md font-medium gap-2">
                                    <DownloadIcon size="18"/>
                                    Download
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <DialogFooter class="p-6 pt-0">
                    <div class="flex w-full justify-between items-center">
                        <div v-if="isEditing" class="space-x-2">
                            <Button class="bg-green-600 text-white hover:bg-green-700" @click="updateStatus('approved')">Approve</Button>
                            <Button variant="destructive" @click="updateStatus('rejected')">Reject</Button>
                            <Button variant="outline" @click="cancelEdit">Cancel</Button>
                        </div>
                        <div v-else-if="dialogState.show.data?.status_by_admin === 'pending'" class="space-x-2">
                            <Button @click="enterEditMode">Approval & Edit</Button>
                        </div>
                        <DialogClose class="ml-auto">
                            <Button variant="outline">Close</Button>
                        </DialogClose>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <Dialog v-model:open="confirmDialog.show">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ confirmDialog.status === 'approved' ? 'Approve' : 'Reject' }} Presence</DialogTitle>
                    <DialogDescription>
                        Are you sure to {{ confirmDialog.status }} this presence record?
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-2 text-sm" v-if="confirmDialog.show">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Time In:</span>
                        <span>{{ formatDate(editForm.time_in) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Time Out:</span>
                        <span>{{ formatDate(editForm.time_out) || '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Note:</span>
                        <span>{{ dialogState.show.data?.note || '-' }}</span>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="confirmDialog.show = false; confirmDialog.status = ''; confirmDialog.progress = false">Cancel</Button>
                    <Button v-if="confirmDialog.status === 'approved'" class="bg-green-600 text-white hover:bg-green-700"
                            :disabled="confirmDialog.progress" @click="confirmAction">
                        <LoaderCircleIcon v-if="confirmDialog.progress" class="animate-spin mr-1 size-4"/>
                        Approve
                    </Button>
                    <Button v-else variant="destructive"
                            :disabled="confirmDialog.progress" @click="confirmAction">
                        <LoaderCircleIcon v-if="confirmDialog.progress" class="animate-spin mr-1 size-4"/>
                        Reject
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
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
                                {{ stats.total_day_work }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                This month
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
                                {{ stats.total_on_time }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                This month
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
                                {{ stats.total_late }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                This month
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
                                {{ stats.total_earnings ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(stats.total_earnings) : '-' }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Approved salary total
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </CardHeader>
            <CardContent class="h-full max-h-screen overflow-y-scroll">
                <div class="ml-auto justify-end flex items-center gap-2 px-4">
                    <Button @click="exportExcel()" size="sm" class="h-7 gap-1" variant="outline">
                        <FileSpreadsheet class="h-3.5 w-3.5"/>
                        <span class="sm:not-sr-only sm:whitespace-nowrap">Export Excel</span>
                    </Button>
                    <Dialog v-model:open="dialogState.add.state">
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

                                <div v-else
                                     class="grid grid-cols-4 items-center gap-4">
                                    <Label for="code" class="text-right">Time In</Label>
                                    <VueDatePicker :format="formatDate(form.time_in)" :preview-format="formatDate"
                                                   class="min-w-max"
                                                   v-model:modelValue="form.time_in"/>
                                </div>

                                <div v-if="$attrs.auth.user.type != 'employee'"
                                     class="grid grid-cols-4 items-center gap-4">
                                    <Label for="code" class="text-right">Time Out</Label>
                                    <VueDatePicker :format="formatDate(form.time_out)" :preview-format="formatDate"
                                                   class="min-w-max"
                                                   v-model:modelValue="form.time_out"/>
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
                                <Button v-bind:disabled="dialogState.add.progress" @click="handleSubmit()">
                                    <loader-circle-icon v-if="dialogState.add.progress" class="animate-spin"/>
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
                        <div class="w-full" v-if="filterControl.filterBy.id == 'presence_status'">
                            <Select v-model="filterControl.sortBy">
                                <SelectTrigger>
                                    <ul>{{
                                            filterControl.sortBy || 'Select Status'
                                        }}
                                    </ul>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Pending">Pending</SelectItem>
                                    <SelectItem value="Rejected">Rejected</SelectItem>
                                    <SelectItem value="Approved">Approve</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="w-full inline-flex gap-2" v-if="filterControl.filterBy">
                            <Select v-model="filterControl.orderBy">
                                <SelectTrigger>
                                    <ul>{{ filterControl.orderBy.title || 'Order From' }}</ul>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="{id:'desc',title:'Newest'}">Newest</SelectItem>
                                    <SelectItem :value="{id:'asc',title:'Oldest'}">Oldest</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Button
                            @click="()=>{filterControl.filterBy = '';filterControl.orderBy = '';filterControl.sortBy = '';filterControl.location_id=undefined}"
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
                            <TableHead>Time In</TableHead>
                            <TableHead>Time Out</TableHead>
                            <TableHead>Working Hour</TableHead>
                            <TableHead>Location</TableHead>
                            <TableHead class="text-center">Admin Status</TableHead>
                            <TableHead class="text-center">
                                Action
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(data,index) in dataset" :key="data.id">
                            <TableCell class="hidden sm:table-cell text-center">
                                {{ index + 1 }}
                            </TableCell>
                            <TableCell class="font-medium">
                                {{ dayjs(data.time_in).format('DD-MM-YYYY') || '-' }}
                            </TableCell>
                            <TableCell class="font-medium text-ellipsis overflow-ellipsis">
                                {{ dayjs(data.time_in).format('HH:mm:ss') || '-' }}
                            </TableCell>
                            <TableCell class="font-medium">
                                {{ dayjs(data.time_out).format('HH:mm:ss') || '-' }}
                            </TableCell>
                            <TableCell class="font-medium">
                                {{ formatWorkingHour(data.time_in, data.time_out) || '-' }}
                            </TableCell>
                            <TableCell class="font-medium">
                                {{ locationDataSet.find((val) => val.id === data.mst_presence_location_id)?.name || '-' }}
                            </TableCell>
                            <TableCell class="hidden sm:table-cell text-center">
                                <Badge v-if="data.status_by_admin == 'pending'" variant="warning">
                                    {{ data.status_by_admin }}
                                </Badge>
                                <Badge v-else-if="data.status_by_admin == 'rejected'" variant="destructive">
                                    {{ data.status_by_admin }}
                                </Badge>
                                <Badge v-else variant="success">
                                    {{ data.status_by_admin }}
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
                                            @click="dialogState.show.state = true;dialogState.show.data = data"
                                            class="cursor-pointer">View Detail
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="dialogState.delete.data = data;dialogState.delete.state = true"
                                            class="cursor-pointer">Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="!dataset || dataset.length == 0">
                            <TableCell colspan="7" class="text-center">
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
                        data
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
