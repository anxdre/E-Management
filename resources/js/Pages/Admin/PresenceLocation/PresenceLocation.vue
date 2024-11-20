<script setup lang="ts">
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import { ArrowLeftCircle, ArrowRightCircle, MapPinHouse, PlusCircle, Search, TriangleAlert } from 'lucide-vue-next'
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
    DialogTitle,
    DialogTrigger
} from "@/shadcn/ui/dialog";
import CustomLink from "@/Components/CustomLink.vue";

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

function generateCode() {
    axios.post(route('presence-verification.json.new'), { id: selectedData.value.id })
        .then(({ data: { data: dataFromServer, message, status_code } }) => {
            successToast('Success', message)
            selectedData.value.single_verification = dataFromServer
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
    <main class="flex-1 items-start gap-4 p-4 sm:px-6 md:gap-4">
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
            <CardContent class="h-full max-h-screen overflow-y-scroll">
                <Dialog v-if="dataset.length > 0">
                    <DialogTrigger class="gap-4 grid grid-flow-row md:grid-cols-3 p-4" as="div">
                        <Card v-for="data in dataset" :key="data.id" @click="selectedData = data"
                              class="p-4 cursor-pointer hover:bg-black/10 hover:outline hover:outline-black/20 w-full">
                            <div class="inline-flex gap-2 items-center text-lg w-full justify-between">
                                <div class="gap-4 flex flex-col items-center text-center text-lg w-full font-semibold">
                                    <MapPinHouse class="w-full max-h-20 h-full text-black stroke-1 stroke-primary"/>
                                    {{ data.name }}
                                </div>
                            </div>
                        </Card>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>
                                Action
                            </DialogTitle>
                            <DialogDescription class="space-y-4 flex flex-col">
                                <div class="mb-4">
                                    <h4>Verification Code</h4>
                                    <h1 class="text-black"
                                        v-if="selectedData.single_verification.length > 0">{{
                                            selectedData.single_verification[0].verification_hash
                                        }}</h1>
                                    <span v-else>No Verification code</span>
                                </div>
                                <Button @click="generateCode()">Generate New Code</Button>
                                <CustomLink :href="route('presence-location.detail',{id:selectedData.id})">
                                    <Button class="w-full" variant="outline">Edit Position</Button>
                                </CustomLink>
                                <Dialog>
                                    <DialogTrigger>
                                        <Button class="bg-black w-full">Delete Location</Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            Are you sure to delete this location ?
                                        </DialogHeader>
                                        <DialogDescription>
                                            This action can't be undone
                                        </DialogDescription>
                                        <DialogFooter>
                                            <div class="space-x-2">
                                                <Button class="bg-black">Yes</Button>
                                                <Button variant="outline">Cancel</Button>
                                            </div>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <DialogClose as-child>
                                <Button type="button" variant="secondary">
                                    Close
                                </Button>
                            </DialogClose>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
                <Card v-else class="p-4 m-2">
                    <div class="inline-flex gap-2 items-center text-lg w-full justify-center">
                        <TriangleAlert class="text-destructive animate-pulse"/>
                        Tidak ada data
                    </div>
                </Card>
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
