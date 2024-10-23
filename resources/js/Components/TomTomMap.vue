<script setup lang="ts">
import { onMounted, reactive, ref, watch } from "vue";
import '@tomtom-international/web-sdk-maps/dist/maps.css';
import tt, { LngLat, Map } from "@tomtom-international/web-sdk-maps";
import { Slider } from "@/shadcn/ui/slider";

const mapRef = ref<HTMLElement | null>(null);

const props = defineProps<{
    position: LngLat
}>()
const markerPosition = reactive(props.position)

const radius = defineModel('radius', { required: false, default: 1000 }) // meter
let circleLayerId = "marker-circle";

// Fungsi untuk menambahkan marker ke peta
function addMarker(map: Map) {
    const location = new tt.LngLat(112.7166368, -7.272563); // Pakai LngLat object
    const popupOffset = 25;

    const marker = new tt.Marker({ draggable: true, anchor: 'center' }) // Disable drag
        .setLngLat(location)
        .addTo(map);

    marker.on("dragend", () => {
        const { lng, lat } = marker.getLngLat();
        markerPosition.lat = lat
        markerPosition.lng = lng;
        addCircleLayer(map, markerPosition, radius.value);
    });

    const popup = new tt.Popup({ offset: popupOffset })
        .setHTML("Set this location ?");
    marker.setPopup(popup).togglePopup();
    map.setCenter(location);
}

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
        center: markerPosition,
        zoom: 15,
    });
    window.map = map;
    map.addControl(new tt.NavigationControl());

    window.map.on("load", () => {
        map.resize();
        addMarker(map);
        addCircleLayer(map, markerPosition, radius.value);
    });
    window.addEventListener("resize", () => map.resize());
});

watch(radius,()=>{
    addCircleLayer(window.map, markerPosition, radius.value);
})

</script>

<template>
    <div ref="mapRef" class="w-full h-[500px] relative"></div>
    <Slider class="w-3/5"
        :default-value="[100]" :max="1000" :min="50" :step="50" v-model:model-value="radius" />
    <span>Radius : {{radius[0]}} / 2500 M</span>
</template>

<style scoped>

</style>
