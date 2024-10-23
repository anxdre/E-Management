<script setup lang="ts">
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import { ArrowLeftCircle, ArrowRightCircle, MoreHorizontal, PlusCircle, Search, TriangleAlert,MapPinHouse } from 'lucide-vue-next'
import { Button } from '@/shadcn/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/shadcn/ui/card'
import { CrudDialogAdapter } from "@/lib/DialogState/CrudDialogAdapter";
import { onMounted, reactive, ref } from "vue";
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
import CreateAccount from "@/Pages/Admin/EmployeeAccount/CreateAccount.vue";
import EditAccount from "@/Pages/Admin/EmployeeAccount/EditAccount.vue";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger
} from "@/shadcn/ui/dropdown-menu";
import { ScrollArea } from "@/shadcn/ui/scroll-area";

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
                    <DialogTitle>Delete {{ dialogState.delete.data.name }} Location</DialogTitle>
                    <DialogDescription>
                        After deletion, all data related (employee presence) of this location will be deleted,
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
                <CardTitle>Presence Location</CardTitle>
                <CardDescription class="inline-flex justify-between items-center">
                    Manage your presence location and view their related information.
                    <div class="ml-auto flex items-center gap-2">
                        <Button @click="navigateLink(route('presence-location.create'))" size="sm"
                                class="h-7 gap-1 bg-black">
                            <PlusCircle class="h-3.5 w-3.5"/>
                            <span class="sr-only sm:not-sr-only sm:whitespace-nowrap">
                  Add Presence Location
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
            <CardContent class="space-y-4">
                <ScrollArea class="h-[800px] w-full rounded-md border p-4">
                <Card class="p-4 m-2">
                    <div class="inline-flex gap-2 items-center text-lg w-full justify-center">
                        <TriangleAlert class="text-destructive animate-pulse"/>
                        Tidak ada data
                    </div>
                </Card>
                <Card class="p-4 m-2 cursor-pointer hover:bg-black/10 hover:outline hover:outline-black/20">
                    <div class="inline-flex gap-2 items-center text-lg w-full justify-between">
                        <div class="inline-flex gap-4 items-center text-lg w-full font-semibold">
                            <MapPinHouse class="text-black"/>
                            Next Avenue
                        </div>
                    </div>
                </Card>
                </ScrollArea>
            </CardContent>
            <CardFooter>
                <div class="justify-between md:inline-flex md:space-y-0 space-y-2 w-full">
                    <div class="text-xs text-muted-foreground">
                        Showing <strong>{{ paginateControl.from }}-{{ paginateControl.to }}</strong> of
                        <strong>{{ paginateControl.totalData }}</strong>
                        account
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
