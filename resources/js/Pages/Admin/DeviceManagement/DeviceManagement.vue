<script setup lang="ts">
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import { CrudDialogAdapter } from "@/lib/DialogState/CrudDialogAdapter";
import { errorToast, formatDate, PaginationOption, successToast } from "@/lib/utils";
import { Button } from '@/shadcn/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/shadcn/ui/card';
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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger
} from '@/shadcn/ui/dropdown-menu';
import { Input } from "@/shadcn/ui/input";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow, } from '@/shadcn/ui/table';
import { debounceFilter, watchPausable } from "@vueuse/core";
import axios from "axios";
import {
    ArrowLeftCircle,
    ArrowRightCircle,
    LoaderCircleIcon,
    MoreHorizontal, Search,
    TriangleAlert
} from 'lucide-vue-next';
import { onMounted, reactive, ref, useAttrs } from "vue";

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
const dialogState = reactive(new CrudDialogAdapter())


function getDataset(page = 1) {
    isLoading.value = true
    paginationWatcher.pause()
    axios.get(route('device-management.json.all', {
        user: attrs.auth.user.id
    }), {
        params: {
            page: page,
            search: paginateControl.searchQuery,
        }
    }).then(({ data: { data: dataFromServer, message, status_code } }) => {
        dataset.value = dataFromServer.data
        paginateControl.currentPage = dataFromServer.current_page
        paginateControl.nextPageUrl = dataFromServer.next_page_url
        paginateControl.prevPageUrl = dataFromServer.prev_page_url
        paginateControl.from = dataFromServer.from
        paginateControl.to = dataFromServer.to
        paginateControl.totalData = dataFromServer.total
        paginateControl.perPageData = dataFromServer.per_page
    }).catch((err) => {
        errorToast('Failed to fetch data')
    }).finally(() => {
        isLoading.value = false
        paginationWatcher.resume()
    })
}

function deleteItem(id: number) {
    isLoading.value = true
    axios.delete(route('device-management.json.delete-device', { id: id, user: attrs.auth.user.id }))
        .then((res) => {
            successToast('Data deleted successfully')
            getDataset(paginateControl.currentPage)
            dialogState.delete.state = false
        })
        .catch((err) => {
            errorToast('Error', err.response.data.message || 'Failed to delete data')
        }).finally(() => {
            isLoading.value = false
        })
}

onMounted(async () => {
    getDataset()
})
</script>

<template>
    <main class="grid flex-1 items-start gap-4 p-4 sm:px-6 md:gap-4">
        <Dialog v-model:open="dialogState.delete.state">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Device</DialogTitle>
                    <DialogDescription>
                        <strong>{{ dialogState.delete.data.email }}'s</strong> device will be deleted permanently.
                        After deletion, the employee will be logged out from <strong>{{
                            dialogState.delete.data.tokens[0].device_name }}</strong> and
                        they will need to log in again to access their account.
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
                <CardTitle>Company Device Management</CardTitle>
                <CardDescription class="inline-flex justify-between items-center">
                    Manage your employee logged device & manage their related settings.
                </CardDescription>
                <div class="relative md:ml-auto flex-1 md:grow-0">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input v-model:model-value="paginateControl.searchQuery" type="search" placeholder="Search..."
                        class="w-full rounded-lg bg-background pl-8 md:w-[200px] lg:w-[320px]" />
                </div>
            </CardHeader>
            <CardContent>
                <Table class="relative">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="hidden w-[100px] sm:table-cell text-center">
                                No
                            </TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead class="text-center">Device Name</TableHead>
                            <TableHead class="text-center">Device Type</TableHead>
                            <TableHead class="text-center">Created At</TableHead>
                            <TableHead>
                                Action
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(group, index) in dataset" :key="group.id">
                            <TableCell class="hidden sm:table-cell text-center">
                                {{ index + 1 }}
                            </TableCell>
                            <TableCell class="font-medium">
                                {{ group.email }}
                            </TableCell>
                            <TableCell class="text-center">
                                {{ group.tokens[0].device_name }}
                            </TableCell>
                            <TableCell class="text-center">
                                {{ group.tokens[0].device_type }}
                            </TableCell>
                            <TableCell class="text-center">
                                {{ formatDate(group.tokens[0].created_at) }}
                            </TableCell>
                            <TableCell>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button aria-haspopup="true" size="icon" variant="ghost">
                                            <MoreHorizontal class="h-4 w-4" />
                                            <span class="sr-only">Toggle menu</span>
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                        <DropdownMenuItem
                                            @click="dialogState.delete.data = group; dialogState.delete.state = true"
                                            class="cursor-pointer">Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="!dataset || dataset.length == 0">
                            <TableCell colspan="5" class="text-center">
                                <div class="inline-flex gap-2 items-center text-lg">
                                    <TriangleAlert class="text-destructive animate-pulse" />
                                    Tidak ada data
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell v-if="isLoading" colspan="5">
                                <div class="w-full inline-flex justify-center animate-pulse">
                                    <LoaderCircleIcon class="size-10 animate-spin" />
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
                        <Button @click="() => paginateControl.currentPage--" v-if="paginateControl.prevPageUrl"
                            variant="outline" class="gap-2">
                            <ArrowLeftCircle :size="18" />
                            Prev
                        </Button>
                        <span class="hidden md:block px-4 border rounded-md content-center text-center">
                            {{ paginateControl.currentPage }}
                        </span>
                        <Button @click="paginateControl.currentPage++" v-if="paginateControl.nextPageUrl"
                            class="bg-black gap-2">
                            Next
                            <ArrowRightCircle :size="18" />
                        </Button>
                    </div>
                </div>
            </CardFooter>
        </Card>
    </main>
</template>

<style scoped></style>
