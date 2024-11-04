<script setup lang="ts">
import {onMounted, reactive, ref, watch} from "vue";
import '@tomtom-international/web-sdk-maps/dist/maps.css';
import tt, {LngLat} from "@tomtom-international/web-sdk-maps";

const mapRef = ref<HTMLElement | null>(null);

const props = defineProps<{
    position: LngLat
}>()
const radius = defineModel<any[]>('radius', {required: false, default: [1000]}) // meter
const userPosition = reactive(props.position)
const emit = defineEmits<{
    (e: 'markerChange', lngLat: LngLat): void
}>()

let globalMarker: tt.Marker
let circleLayerId = "marker-circle";

function addCircleLayer(map: tt.Map, center: LngLat, radius: number) {
    if (map.getLayer(circleLayerId)) {
        map.removeLayer(circleLayerId);
        map.removeSource(circleLayerId);
    }

    // Tambahkan source GeoJSON untuk circle
    map.addSource(circleLayerId, {
        type: "geojson",
        data: {
            type: "FeatureCollection",
            features: [createCircle(center, radius)],
        },
    });

    // Tambahkan layer circle dengan konfigurasi style
    map.addLayer({
        id: circleLayerId,
        type: "fill",
        source: circleLayerId,
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

onMounted(() => {
    if (!mapRef.value) return;

    const map = tt.map({
        key: "YfCCUSubfF0dz5KH5lwkQxQbuCGwKGYy",
        container: mapRef.value,
        center: userPosition,
        zoom: 20,
    });
    window.map = map;
    map.addControl(new tt.NavigationControl());
    window.map.on("load", () => {
        window.addEventListener("resize", () => map.resize());
        map.resize();
    });

    window.map.on("click", (location: any) => {
        emit('markerChange', location.lngLat)
        if (globalMarker) {
            globalMarker.setLngLat(location.lngLat)
            addCircleLayer(map, location.lngLat, radius.value[0])
            return
        }
        const popupOffset = 25;

        const marker = new tt.Marker({draggable: true, anchor: 'center'})
            .setLngLat(location.lngLat)
            .addTo(map);
        globalMarker = marker

        marker.on("dragend", () => {
            const {lng, lat} = marker.getLngLat();
            userPosition.lat = lat
            userPosition.lng = lng;
            addCircleLayer(map, location.lngLat, radius.value[0]);
        });

        const popup = new tt.Popup({offset: popupOffset})
            .setHTML("Set this location ?");
        marker.setPopup(popup).togglePopup();
        map.setCenter(location.lngLat);
        addCircleLayer(map, location.lngLat, radius.value[0]);

    });
});

watch(radius, (newRadius) => {
    if (globalMarker) {
        addCircleLayer(window.map, globalMarker.getLngLat(), newRadius[0]);
    }
});

</script>

<template>
    <div ref="mapRef" class="w-full h-[500px]"></div>
</template>

<style scoped>

</style>
