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
import CreateEmployee from "@/Pages/Admin/EmployeeGroup/CreateEmployee.vue";
import { CrudDialogAdapter } from "@/lib/DialogState/CrudDialogAdapter";
import { onMounted, reactive, ref } from "vue";
import axios from "axios";
import { errorToast, navigateLink, PaginationOption, successToast } from "@/lib/utils";
import { debounceFilter, watchPausable } from "@vueuse/core";
import { Input } from "@/shadcn/ui/input";
import EditEmployee from "@/Pages/Admin/EmployeeGroup/EditEmployee.vue";
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from "@/shadcn/ui/dialog";
import CreateAccount from "@/Pages/Admin/EmployeeAccount/CreateAccount.vue";
import EditAccount from "@/Pages/Admin/EmployeeAccount/EditAccount.vue";

defineOptions({
    layout: LayoutWrapper
})

const dataset = ref()
const isLoading = ref(false)
const paginateControl = reactive(new PaginationOption())
const paginationWatcher = watchPausable(
    paginateControl,
    (value) => {
        getDataset(paginateControl.currentPage)
    }, { eventFilter: debounceFilter(800) }
)

function getDataset(page: number) {
    paginationWatcher.pause()
    isLoading.value = true
    axios.get(route('employee-account.json.all', { page: page, search: paginateControl.searchQuery }))
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
    axios.delete(route('employee-group.json.delete'), { data: { data_id: dataId } })
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            successToast('Success', message)
            getDataset(paginateControl.currentPage)
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
    <main class="flex-1 items-start gap-4 p-4 sm:px-6 md:gap-4">
        <CreateAccount @successCreated="getDataset()" :errors="$attrs.errors"
                        v-model:is-showing="dialogState.show.state"/>
        <EditAccount @successUpdated="getDataset()" :dataset="dialogState.update.data"
                      v-model:is-showing="dialogState.update.state"
                      :errors="$attrs.errors"/>
        <Dialog v-model:open="dialogState.delete.state">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{dialogState.delete.data.name}} Employee</DialogTitle>
                    <DialogDescription>
                        After deletion, all data of this employee  will be still deleted,
                        <br> Are you sure to perform this action ? deleted data cannot be undone.
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
                <CardTitle>Company Account</CardTitle>
                <CardDescription class="inline-flex justify-between items-center">
                    Manage your employee account and view their related information.
                    <div class="ml-auto flex items-center gap-2">
                        <Button @click="navigateLink(route('employee-account.create'))" size="sm" class="h-7 gap-1 bg-black">
                            <PlusCircle class="h-3.5 w-3.5"/>
                            <span class="sr-only sm:not-sr-only sm:whitespace-nowrap">
                  Add Employee Account
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
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="hidden w-[100px] sm:table-cell text-center">
                                No
                            </TableHead>
                            <TableHead>Full Name</TableHead>
                            <TableHead>Phone Number</TableHead>
                            <TableHead>Address</TableHead>
                            <TableHead class="hidden w-[100px] sm:table-cell text-center">Account Status</TableHead>
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
                            <TableCell  class="text-center">
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
