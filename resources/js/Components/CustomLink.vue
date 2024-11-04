<script setup lang="ts">
import {Link} from "@inertiajs/vue3";
import {useGlobalLoaderStrore} from "@/lib/GlobalLoaderStore";

const props = defineProps<{
    href: string
}>()

function onBefore() {
    useGlobalLoaderStrore().isLoading = true
    useGlobalLoaderStrore().darkenBg = true
}

function onFinish() {
    useGlobalLoaderStrore().isLoading = false
    useGlobalLoaderStrore().darkenBg = false
}
</script>

<template>
    <Link :href="href" :onBefore="onBefore" :onFinish="onFinish">
        <slot/>
    </Link>
</template>

<style scoped>

</style>
