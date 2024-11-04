<script setup lang="ts">

import {defaultToast, errorToast, navigateLink, successToast} from "@/lib/utils";
import {Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle} from "@/shadcn/ui/card";
import {Button} from "@/shadcn/ui/button";
import {ChevronLeft} from "lucide-vue-next";
import TomTomMap from "@/Components/TomTomMap.vue";
import {computed, onMounted, ref, useAttrs} from "vue";
import {watchPausable} from "@vueuse/core";
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
import {LngLat} from "@tomtom-international/web-sdk-maps";

defineOptions({
    layout: LayoutWrapper
})

const {dataFromServer} = defineProps<{ dataFromServer: any }>()

const data = ref({user_id: useAttrs().auth?.user?.id, radius: 10, max_hour: 8})
const markerPosition = computed({
    get(): LngLat {
        return new LngLat(data.longitude ?? 112.7166368, data.latitude ?? -7.272563) // Default position
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
            markerPosition.lng = position.lng;
            markerPosition.lat = position.lat;
        }
    } catch (error) {
        console.error("Error mendapatkan lokasi:", error);
    }
}

onMounted(() => {
    getCurrentLocation(); // Ambil lokasi saat komponen di-mount

    if (dataFromServer) {
        data.value = {
            id: dataFromServer.id,
            user_id: useAttrs().auth?.user?.id,
            radius: dataFromServer.tolerance,
            max_hour: dataFromServer.max_hour,
            name: dataFromServer.name,
            latitude: dataFromServer.latitude,
            longitude: dataFromServer.longitude
        }
        markerPosition.value = new LngLat(data.value.longitude,data.value.latitude)
        radius.value = [data.value.radius]
    }
});

watchPausable(markerPosition, () => {
    defaultToast('Info', `Lat : ${markerPosition.lat}, Long: ${markerPosition.lng}`);
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
                        <TomTomMap @markerChange="value => markerPosition = value" :position="markerPosition"
                                   v-model:radius="radius"/>
                        <span class="text-xs text-muted-foreground">*click on map to add presence location</span>

                        <Card
                            class="md:w-1/4 w-full h-fit left-0 bg-white/30 md:absolute p-4 flex-col flex m-4 backdrop-blur-sm hover:backdrop-blur-0 hover:bg-white">
                            <div class="space-y-2">
                                <Slider class="w-full md:w-3/5"
                                        :default-value="[10]" :max="100" :min="0" :step="1"
                                        v-model:model-value="radius"/>
                                <Label>Radius : {{ radius[0] }} / 100 M</Label>
                            </div>

                            <section class="space-y-4 mt-4">
                                <div class="space-y-1">
                                    <Label>Location presence name</Label>
                                    <Input class="w-full" type="text" v-model="data.name"/>
                                </div>
                                <div>
                                    <NumberField id="max_hour" v-model="data.max_hour" :default-value="8" :max="24"
                                                 :min="1">
                                        <Label for="max_hour">Max hour</Label>
                                        <NumberFieldContent>
                                            <NumberFieldDecrement/>
                                            <NumberFieldInput/>
                                            <NumberFieldIncrement/>
                                        </NumberFieldContent>
                                    </NumberField>
                                </div>
                                <div class="space-y-1">
                                    <Label>Longitude</Label>
                                    <Input disabled v-model:model-value="data.longitude"/>
                                    <Label>Latitude</Label>
                                    <Input disabled v-model:model-value="data.latitude"/>
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
