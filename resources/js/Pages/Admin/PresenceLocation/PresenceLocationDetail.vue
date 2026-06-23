<script setup lang="ts">

import {defaultToast, errorToast, navigateLink, successToast} from "@/lib/utils";
import {Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle} from "@/shadcn/ui/card";
import {Button} from "@/shadcn/ui/button";
import {ChevronLeft} from "lucide-vue-next";

import {computed, onMounted, ref, useAttrs} from "vue";
import {tryOnBeforeMount, watchPausable} from "@vueuse/core";
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import {Slider} from "@/shadcn/ui/slider";
import {Input} from "@/shadcn/ui/input";
import {Label} from "@/shadcn/ui/label";
import {
    NumberField,
    NumberFieldContent,
    NumberFieldDecrement,
    NumberFieldIncrement,
    NumberFieldInput,
} from '@/shadcn/ui/number-field'
import axios from "axios";
import {useGlobalLoaderStrore} from "@/lib/GlobalLoaderStore";
import {LngLat} from "maplibre-gl";
import MapView from "@/Components/MapView.vue";
import TimePicker from "@/Components/TimePicker.vue";

defineOptions({
    layout: LayoutWrapper
})

const {dataFromServer} = defineProps<{ dataFromServer: any }>()

const data = ref({
    mst_user_id: useAttrs().auth?.user?.id,
    radius: 10,
    name:null,
    latitude:null,
    longitude:null,
    min_hour:null,
    max_hour:null,
    start_hour:null,
    end_hour:null
})
const markerPosition = computed({
    get(): LngLat {
        return new LngLat(data.value.longitude ?? 112.7166368, data.value.latitude ?? -7.272563) // Default position
    },

    set(newValue: LngLat) {
        data.value.longitude = newValue.lng
        data.value.latitude = newValue.lat
    }
})
const radius = computed({
    get(): number[] {
        return [data.value.radius]
    },

    set(newValue: number[]) {
        data.value.radius = newValue[0]
    }
})

async function getCurrentLocation() {
    if(dataFromServer) return
    try {
        const position = await new Promise<{ lat: number; lng: number }>((resolve, reject) => {
            if (!navigator.geolocation) {
                alert("Geolocation tidak didukung di browser ini.");
                return resolve(null);
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const {latitude, longitude} = position.coords;
                    resolve({lat: latitude, lng: longitude});
                },
                (error) => {
                    alert(`Gagal mendapatkan lokasi: ${error.message}`);
                    resolve(null);
                },
                {enableHighAccuracy: true, timeout: 10000, maximumAge: 0}
            );
        });

        // Kalau dapat lokasi, update reactive state
        if (position.lng) {
            markerPosition.value = new LngLat(position.lng,position.lat)
        }
    } catch (error) {
        console.error("Error mendapatkan lokasi:", error);
    }
}

tryOnBeforeMount(() => {
    if (dataFromServer) {
        data.value = {
            id: dataFromServer.id,
            mst_user_id: useAttrs().auth?.user?.id,
            max_hour: dataFromServer.max_hour,
            min_hour: dataFromServer.min_hour,
            start_hour: dataFromServer.start_hour,
            name: dataFromServer.name,
        }
        markerPosition.value = new LngLat(dataFromServer.longitude,dataFromServer.latitude)
        radius.value = [dataFromServer.tolerance]
        return
    }
     getCurrentLocation(); // Ambil lokasi saat komponen di-mount
});

watchPausable(markerPosition, () => {
    defaultToast('Info', `Lat : ${markerPosition.value.lat}, Long: ${markerPosition.value.lng}`);
})

function _saveLocation() {
    useGlobalLoaderStrore().isLoading = true
    useGlobalLoaderStrore().darkenBg = true
    axios.post(route('presence-location.create'), data.value)
        .then(({data: {data: dataFromServer, message, statusCode}}) => {
            data.id = dataFromServer.id
            successToast('Success', message)
            navigateLink(route('presence-location.index'))
        })
        .catch(({response}) => {
            errorToast('error', response.data.message)
        })
        .finally(() => {
            useGlobalLoaderStrore().isLoading = false
            useGlobalLoaderStrore().darkenBg = false
        })
}

</script>

<template>
    <main class="grid flex-1 items-start gap-4 p-4 sm:px-6 md:gap-4">
        <Card class="overflow-y-scroll">
            <CardHeader>
                <CardTitle @click="navigateLink(route('presence-location.index'))"
                           class="inline-flex items-center gap-2">
                    <Button variant="ghost" class="w-fit">
                        <ChevronLeft/>
                    </Button>
                    {{ isCreate ? 'Create new presence location' : 'Presence location detail' }}
                </CardTitle>
                <CardDescription class="inline-flex justify-between items-center">
                    {{
                        isCreate ? 'Create your company presence location' : 'Manage your company presence location'
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="relative">
                    <div class="flex flex-col items-center">
                        <MapView v-model:marker-radius="radius[0]" v-model:marker-position="markerPosition"/>
                        <span class="text-xs text-muted-foreground">*click on map to add presence location</span>

                        <Card
                            class="md:w-1/4 w-full h-3/4 overflow-scroll right-4 top-4 bg-white/30 md:absolute p-4 flex-col flex m-4 backdrop-blur-sm hover:backdrop-blur-0 hover:bg-white">
                            <div class="space-y-2">
                                <Slider class="w-full md:w-3/5"
                                        :default-value="[10]" :max="100" :min="1" :step="1"
                                        v-model:model-value="radius"/>
                                <Label>Radius : {{ radius[0] }} / 100 M</Label>
                            </div>

                            <section class="space-y-4 mt-4">
                                <div class="space-y-1">
                                    <Label required>Location presence name</Label>
                                    <Input class="bg-white " type="text" v-model="data.name"/>
                                </div>
                                <div class="space-y-1">
                                    <Label required>Longitude</Label>
                                    <Input disabled v-model:model-value="data.longitude"/>
                                    <Label required>Latitude</Label>
                                    <Input disabled v-model:model-value="data.latitude"/>
                                </div>
                                <div>
                                    <Label>Presence Start</Label>
                                    <TimePicker v-model="data.start_hour" />
                                </div>
                                <div>
                                    <Label>Presence End</Label>
                                    <TimePicker v-model="data.end_hour" />
                                </div>
                                <div>
                                    <Label>Min Working Hour</Label>
                                    <TimePicker v-model="data.min_hour" />
                                </div>
                                <div>
                                    <Label>Max Working Hour</Label>
                                    <TimePicker v-model="data.max_hour" />
                                </div>
                                <Button class="w-full" @click="_saveLocation">Save</Button>
                            </section>
                        </Card>
                    </div>
                </div>
            </CardContent>
            <CardFooter>
            </CardFooter>
        </Card>
    </main>
</template>

<style scoped>

</style>
