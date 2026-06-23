<script setup lang="ts">
import maplibregl, { LngLat, LngLatLike } from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import MaplibreGeocoder from '@maplibre/maplibre-gl-geocoder';
import '@maplibre/maplibre-gl-geocoder/dist/maplibre-gl-geocoder.css';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { watchPausable } from '@vueuse/core';
import { cn } from "@/lib/utils";


const markerPosition = defineModel<LngLatLike | undefined>('markerPosition', { required: true })
const markerRadius = defineModel<number>('markerRadius', { required: true })
const mapRef = ref<HTMLElement | null>(null)

let globalMap: maplibregl.Map
let globalMarker: maplibregl.Marker | null = null
const globalCircleId = 'marker-circle'
const popUpOffset = 25

const props = defineProps<{
    class?: string,
    viewOnly?: boolean
}>()

const tomtomKey = "YfCCUSubfF0dz5KH5lwkQxQbuCGwKGYy"

onMounted(() => {
    markerWatcher.pause()
    if (!mapRef.value) return

    const styleUrl = `https://api.tomtom.com/map/1/style/25.2.3-0/basic_main.json?key=${tomtomKey}`
    globalMap = new maplibregl.Map({
        container: mapRef.value,
        style: styleUrl,
        center: markerPosition.value as maplibregl.LngLatLike ?? [106.865, -6.175],
        zoom: 15,
    })

    globalMap.addControl(new maplibregl.NavigationControl(), 'top-right')

    if (!props.viewOnly) {
        const geocodingAPI = {
            forwardGeocode: async (config: { query: string; limit?: number }) => {
                const url = `https://api.tomtom.com/search/2/search/${encodeURIComponent(config.query)}.json` +
                    `?key=${tomtomKey}&countrySet=ID&limit=${config.limit || 5}&language=id-ID`
                const res = await fetch(url)
                const data = await res.json()
                return {
                    type: 'FeatureCollection' as const,
                    features: (data.results ?? []).map((r: any) => ({
                        type: 'Feature' as const,
                        geometry: {
                            type: 'Point' as const,
                            coordinates: [r.position.lon, r.position.lat],
                        },
                        place_name: r.address?.freeformAddress || r.poi?.name || '',
                        properties: {
                            address: r.address?.freeformAddress,
                            name: r.poi?.name,
                            id: r.id,
                        },
                    })),
                }
            },
        }

        const geocoder = new MaplibreGeocoder(geocodingAPI, {
            maplibregl: maplibregl,
            marker: false,
            flyTo: false,
            showResultsWhileTyping: true,
            placeholder: 'Cari alamat...',
            limit: 5,
            debounceSearch: 300,
        })
        globalMap.addControl(geocoder, 'top-left')

        geocoder.on('result', (e: any) => {
            const feature = e.result
            const coords = feature.geometry.coordinates as [number, number]
            const lngLat = new maplibregl.LngLat(coords[0], coords[1])
            markerPosition.value = lngLat
            addGlobalMarker(lngLat)
            globalMap.flyTo({ center: coords, zoom: 16 })
        })

        const locateBtn = document.createElement('button')
        locateBtn.className = 'maplibregl-ctrl-icon'
        locateBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/></svg>'
        locateBtn.title = 'Dapatkan lokasi saya'
        locateBtn.style.display = 'flex'
        locateBtn.style.alignItems = 'center'
        locateBtn.style.justifyContent = 'center'

        const locateContainer = document.createElement('div')
        locateContainer.className = 'maplibregl-ctrl maplibregl-ctrl-group'
        locateContainer.appendChild(locateBtn)

        locateBtn.onclick = () => {
            if (!navigator.geolocation || !globalMap) return
            locateBtn.disabled = true
            locateBtn.style.opacity = '0.5'
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const userLoc = new maplibregl.LngLat(pos.coords.longitude, pos.coords.latitude)
                    markerPosition.value = userLoc
                    addGlobalMarker(userLoc)
                    globalMap.flyTo({ center: userLoc, zoom: 15 })
                    locateBtn.disabled = false
                    locateBtn.style.opacity = '1'
                },
                () => {
                    locateBtn.disabled = false
                    locateBtn.style.opacity = '1'
                },
                { enableHighAccuracy: false, timeout: 5000, maximumAge: 30000 }
            )
        }

        globalMap.addControl({
            onAdd: () => locateContainer,
            onRemove: () => {},
        }, 'top-right')

    }

    let resizeHandler: (() => void) | null = null
    globalMap.on("load", () => {
        resizeHandler = () => globalMap.resize()
        window.addEventListener("resize", resizeHandler)
        markerWatcher.resume()
        addGlobalMarker(toLngLat(markerPosition.value))
    })

    globalMap.on("click", (location: any) => {
        if (props.viewOnly) return
        const { lng, lat } = location.lngLat
        markerPosition.value = new maplibregl.LngLat(lng, lat)
        addGlobalMarker(location.lngLat)
    })
})

onUnmounted(() => {
    globalMarker?.remove()
    globalMap?.remove()
})

function addGlobalMarker(coordinate: LngLat | undefined) {
    if (!coordinate) return

    if (globalMarker) {
        globalMarker.setLngLat(coordinate)
        addCircleLayer(globalMap, coordinate, markerRadius.value)
        return
    }

    try {
        globalMarker = new maplibregl.Marker({ draggable: !props.viewOnly })
            .setLngLat(coordinate)
            .addTo(globalMap)
    } catch (e: any) {
        console.log(e.message)
    }

    if (globalMarker) {
        globalMarker.on("dragend", () => {
            const { lng, lat } = globalMarker!.getLngLat()
            markerPosition.value = new maplibregl.LngLat(lng, lat)
        })

        globalMarker.setPopup(new maplibregl.Popup({
            offset: popUpOffset
        }).setHTML("Set presence on this location"))
    }

    addCircleLayer(globalMap, coordinate, markerRadius.value)
}

function addCircleLayer(map: maplibregl.Map, center: LngLat, radius: number) {
    if (map.getLayer(globalCircleId)) {
        map.removeLayer(globalCircleId);
        map.removeSource(globalCircleId);
    }

    map.addSource(globalCircleId, {
        type: "geojson",
        data: {
            type: "FeatureCollection",
            features: [{
                type: "Feature",
                properties: {},
                geometry: createCircle(center, radius),
            }],
        },
    });

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
    const coords: [number, number][] = [];
    const earthRadius = 6371000;

    for (let i = 0; i <= points; i++) {
        const angle = (i / points) * (2 * Math.PI);
        const dx = radius * Math.cos(angle);
        const dy = radius * Math.sin(angle);

        const latOffset = (dy / earthRadius) * (180 / Math.PI);
        const lngOffset = (dx / (earthRadius * Math.cos((center.lat * Math.PI) / 180))) * (180 / Math.PI);

        coords.push([center.lng + lngOffset, center.lat + latOffset]);
    }

    return {
        type: "Polygon" as const,
        coordinates: [coords],
    };
}

function toLngLat(pos: LngLatLike | undefined): LngLat | undefined {
    if (!pos) return undefined
    if (pos instanceof LngLat) return pos
    if (Array.isArray(pos)) return new LngLat(pos[0], pos[1])
    return new LngLat(pos.lng, pos.lat)
}

const markerWatcher = watchPausable(markerPosition, () => {
    const pos = toLngLat(markerPosition.value)
    if (globalMarker && pos) {
        globalMarker.setLngLat(pos)
        addCircleLayer(globalMap, pos, markerRadius.value)
    }
}, { deep: true })

watch(markerRadius, () => {
    const pos = toLngLat(markerPosition.value)
    if (globalMarker && pos) {
        addCircleLayer(globalMap, pos, markerRadius.value)
    }
})

</script>
<template>
    <div ref="mapRef" :class="cn('w-full h-[500px] md:h-[800px]', props.class)"></div>
</template>
