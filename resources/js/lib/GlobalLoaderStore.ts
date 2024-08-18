import { ref } from "vue";
import { defineStore } from "pinia";

export const useGlobalLoaderStrore = defineStore('GlobalLoader', () => {
    const isLoading = ref(false)
    const darkenBg = ref(false)
    return { isLoading ,darkenBg}
})
