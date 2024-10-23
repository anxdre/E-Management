<script setup lang="ts">

import { defaultToast, navigateLink } from "@/lib/utils";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/shadcn/ui/card";
import { Button } from "@/shadcn/ui/button";
import { ChevronLeft, PencilIcon } from "lucide-vue-next";
import TomTomMap from "@/Components/TomTomMap.vue";
import { onMounted, reactive, ref, watch } from "vue";
import { LngLat } from "@tomtom-international/web-sdk-maps";
import { useGeolocation, watchPausable } from "@vueuse/core";
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";

defineOptions({
    layout: LayoutWrapper
})

const { coords } = useGeolocation();
const markerPosition = reactive(
    new LngLat(112.7166368, -7.272563) // Default position
);
const radius = ref([100])

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
        if (position) {
            markerPosition.lng = position.lng;
            markerPosition.lat = position.lat;
        }
    } catch (error) {
        console.error("Error mendapatkan lokasi:", error);
    }
}

onMounted(() => {
    getCurrentLocation(); // Ambil lokasi saat komponen di-mount
});


watchPausable(markerPosition,()=>{
    defaultToast('Info',`Lat : ${markerPosition.lat}, Long: ${markerPosition.lng}`);
})

</script>

<template>
    <main class="grid flex-1 items-start gap-4 p-4 sm:px-6 md:gap-4">
        <Card class="overflow-scroll">
            <CardHeader>
                <CardTitle @click="navigateLink(route('employee-account.index'))"
                           class="inline-flex items-center gap-2">
                    <Button variant="ghost" class="w-fit">
                        <ChevronLeft/>
                    </Button>
                    {{ isCreate ? 'Create Employee Account' : 'Account Detail' }}
                </CardTitle>
                <CardDescription class="inline-flex justify-between items-center">
                    {{
                        isCreate ? 'Create your employee account and view their related information.' : ' Manage your employee account and view their related information.'
                    }}
                    <div v-if="!isCreate && !isEditing" class="ml-auto flex items-center gap-2">
                        <Button @click="isEditing = true" size="sm" class="h-7 gap-1 bg-black">
                            <PencilIcon class="h-3.5 w-3.5"/>
                            <span class="sr-only sm:not-sr-only sm:whitespace-nowrap">
                  Edit Employee Account
                </span>
                        </Button>
                    </div>
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col justify-center space-y-4 md:grid md:grid-cols-3 md:space-x-4">
                    <div class="flex flex-col items-center py-4 relative space-y-8">
                        <TomTomMap :position="markerPosition" v-model:radius="radius"/>
                    </div>
                    <div class="outline outline-secondary rounded-md col-span-2 flex flex-col p-4">
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
