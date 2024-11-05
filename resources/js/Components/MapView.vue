<script setup lang="ts">
import '@tomtom-international/web-sdk-maps/dist/maps.css';
import tt,{ LngLat, LngLatLike } from '@tomtom-international/web-sdk-maps';
import { onMounted, ref, watch } from 'vue';
import { watchPausable } from '@vueuse/core';

const markerPosition = defineModel<LngLatLike|undefined>('markerPosition',{required:true})
const markerRadius = defineModel('markerRadius',{required:true})
const mapRef = ref<HTMLElement|null>(null)

let globalMap:tt.Map
let globalMarker:tt.Marker
const globalCircleId = 'marker-circle'
const popUpOffset = 25

onMounted(()=>{
    markerWatcher.pause()
if(!mapRef.value) return

    globalMap = tt.map({
        key: "YfCCUSubfF0dz5KH5lwkQxQbuCGwKGYy",
        container: mapRef.value,
        center: markerPosition.value,
        zoom: 20,
    })

    globalMap.on("load",()=>{
        window.addEventListener("resize", ()=>globalMap.resize())
        markerWatcher.resume()
        addGlobalMarker(markerPosition.value)

    })

    globalMap.on("click", (location:any)=>{
        const {lng,lat}= location.lngLat
        markerPosition.value = new tt.LngLat(lng,lat)
        addGlobalMarker(location.lngLat)
        // addCircleLayer(globalMap,markerPosition.value, markerRadius.value)
    })
})

function addGlobalMarker(coordinate:LngLat){
    if (globalMarker){
        globalMarker.setLngLat(coordinate)
        addCircleLayer(globalMap, coordinate, markerRadius.value)
        return
    }

    try{
    globalMarker = new tt.Marker({draggable:true,anchor:'center'})
    .setLngLat(coordinate)
    .addTo(globalMap)
    }catch(e:any){
        console.log(e.message)
    }

    globalMarker.on("dragend", ()=>{
        const {lng,lat} = globalMarker.getLngLat()
        markerPosition.value = new tt.LngLat(lng,lat)
        // addCircleLayer(globalMap, markerPosition.value, markerRadius.value)
    })

    globalMarker.setPopup(new tt.Popup({
        offset:popUpOffset
    }).setHTML("Set presence on this location"))

    globalMap.panTo(markerPosition.value, {animate:true})
    addCircleLayer(globalMap, markerPosition.value, markerRadius.value)
}

function addCircleLayer(map: tt.Map, center: LngLat, radius: number) {
    if (map.getLayer(globalCircleId)){
        map.removeLayer(globalCircleId);
        map.removeSource(globalCircleId);
    }

    // Tambahkan source GeoJSON untuk circle
    map.addSource(globalCircleId, {
        type: "geojson",
        data: {
            type: "FeatureCollection",
            features: [createCircle(center, radius)],
        },
    });

    // Tambahkan layer circle dengan konfigurasi style
    map.addLayer({
        id: globalCircleId,
        type: "fill",
        source: globalCircleId,
        paint: {
            "fill-color": "#1e90ff",
            "fill-opacity": 0.4,
        },
    });
}

function createCircle(center: LngLat, radius: number, points = 64) {
    const coords = [];
    const earthRadius = 6371000; // Radius bumi dalam meter

    for (let i = 0; i <= points; i++) {
        const angle = (i / points) * (2 * Math.PI);
        const dx = radius * Math.cos(angle); // Offset X dalam meter
        const dy = radius * Math.sin(angle); // Offset Y dalam meter

        // Konversi offset meter ke derajat
        const latOffset = (dy / earthRadius) * (180 / Math.PI);
        const lngOffset = (dx / (earthRadius * Math.cos((center.lat * Math.PI) / 180))) * (180 / Math.PI);

        const lat = center.lat + latOffset;
        const lng = center.lng + lngOffset;
        coords.push([lng, lat]);
    }

    return {
        type: "Feature",
        geometry: {
            type: "Polygon",
            coordinates: [coords],
        },
    };
}

const markerWatcher = watchPausable(markerPosition,()=>{
    addCircleLayer(globalMap, markerPosition.value, markerRadius.value)
},{deep:true})

watch(markerRadius,()=>{
    addCircleLayer(globalMap, markerPosition.value, markerRadius.value)
})

</script>
<template>
    <div ref="mapRef" class="w-full h-[500px]"></div>
</template>
<style>

</style>
