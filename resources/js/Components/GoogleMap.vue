<script setup lang="ts">
import { GoogleMap, AdvancedMarker,InfoWindow } from 'vue3-google-map'
import { useGeolocation } from "@vueuse/core";
import { ref, watch } from "vue";
import { Button } from "@/shadcn/ui/button";

const center = { lat: -7.2710563, lng:112.7269001 }

const mapKey = 'AIzaSyA4AkrhO-jUC-1PeOBHLcCUnI-gApWfVOc'
const { coords, locatedAt, error, resume, pause } = useGeolocation()
const mapRef = ref(null)

const placeMarker = { position: { lat: -7.2710563, lng:112.7269001 }, title: 'Posisi' }
const pinOptions = { background: '#FBBC04' }

function getCurentLocation() {
    console.log(coords.value?.latitude)
    if (coords && coords.value?.latitude != 'Infinity') {
        return { lat: coords.value.latitude, lng: coords.value.longitude }
    }
    return center
}

watch(() => mapRef.value?.ready, (ready) => {
    if (!ready) return

    mapRef.value.map.addEventListener("click", (mapsMouseEvent) => {
        // Close the current InfoWindow.
        infoWindow.close();

        // Create a new InfoWindow.
        infoWindow = new google.maps.InfoWindow({
            position: mapsMouseEvent.latLng,
        });
        infoWindow.setContent(
            JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2)
        );
        infoWindow.open(map);
    });
    // do something with the api using `mapRef.value.api`
    // or with the map instance using `mapRef.value.map`
})

watch(coords, () => {
    placeMarker.position = getCurentLocation()
}, { deep: true })
</script>

<template>
    <GoogleMap ref="mapRef"
               :api-key="mapKey"
               class="w-full h-[500px]"
               :center="getCurentLocation()"
               :zoom="20">
        <AdvancedMarker :options="placeMarker" :pin-options="pinOptions"/>
        <InfoWindow v-if="placeMarker" :options="placeMarker">
            Content passed through slot
        </InfoWindow>
    </GoogleMap>
    <Button @click="">Get Curent Location</Button>
</template>

<style scoped>

</style>
