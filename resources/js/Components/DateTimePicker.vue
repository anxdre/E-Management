<template>
    <Popover v-model:open="open">
        <PopoverTrigger>
            <Button variant="outline" class="w-full justify-start text-left font-normal">
                <CalendarIcon class="size-3" />
                {{ modelValue ? formatDate(modelValue) : 'Pilih tanggal & waktu' }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-[300px] p-2" align="start">
            <VueDatePicker
                v-model="internalValue"
                :is24="true"
                inline auto-apply
                :enable-time-picker="true"
                :format="'DD-MM-YYYY HH:mm'"
                :preview-format="'DD-MM-YYYY HH:mm'"
                @change="onChange"
            />
        </PopoverContent>
    </Popover>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'
import { Popover, PopoverContent, PopoverTrigger } from '@/shadcn/ui/popover'
import { Button } from '@/shadcn/ui/button'
import { CalendarIcon } from 'lucide-vue-next'
import dayjs from 'dayjs'

dayjs.extend(utc)
dayjs.extend(timezone)

dayjs.tz.setDefault('Asia/Jakarta')

// Props & Emit
const modelValue = defineModel<Date | null>()
const internalValue = ref(modelValue.value)
const open = ref(false)

const onChange = (val: Date) => {
    modelValue.value = val
}

watch(() => modelValue.value, (val) => {
    internalValue.value = val
})

const formatDate = (val: Date) => dayjs(val).format('DD-MM-YYYY HH:mm')
</script>

<style>
/* Styling override (biar match shadcn) */
.v3dp__popout {
    @apply bg-white shadow-xl rounded-xl p-2;
}
.v3dp__input {
    @apply border border-input text-sm rounded-md p-2;
}
</style>
