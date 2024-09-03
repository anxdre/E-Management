<script setup lang="ts">

import {LoaderCircle} from "lucide-vue-next";
import {useGlobalLoaderStrore} from "@/lib/GlobalLoaderStore";

const loader = useGlobalLoaderStrore()
</script>

<template>
    <div v-if="loader.isLoading"
         class="bg-black/70 fixed inset-0 flex flex-col justify-center items-center gap-2 z-50">
        <LoaderCircle :stroke-width="2.5" :size="64" class="animate-spin text-primary-foreground/80"/>
        <p class="text-primary-foreground/80 animate-pulse">Loading</p>
    </div>
</template>

<style scoped>

</style>
