<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { Button } from '@/shadcn/ui/button'
import { Popover, PopoverTrigger, PopoverContent } from '@/shadcn/ui/popover'

const props = defineProps<{
    modelValue: string | null
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void
}>()

const selectedHour = ref<string>('00')
const selectedMinute = ref<string>('00')
const opened = ref<boolean>(false)

watch(
    () => props.modelValue,
    (value) => {
        if (value) {
            const [hour, minute] = value.split(':')
            selectedHour.value = hour
            selectedMinute.value = minute
        }
    },
    { immediate: true }
)

const formattedTime = computed(() => `${selectedHour.value}:${selectedMinute.value}`)

function save() {
    emit('update:modelValue', formattedTime.value)
    opened.value = false
}

function clear() {
    selectedHour.value = '00'
    selectedMinute.value = '00'
    emit('update:modelValue', null)
}

function pad(n: number) {
    return String(n).padStart(2, '0')
}
</script>

<template>
    <Popover v-model:open="opened">
        <PopoverTrigger as-child>
            <Button variant="outline" class="w-full justify-start text-left font-normal">
                <template v-if="modelValue">
                    {{ formattedTime }}
                </template>
                <template v-else>
                    Select Time...
                </template>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-60 p-4">
            <div class="grid grid-cols-2 gap-4">
                <select v-model="selectedHour" class="border rounded-md px-2 py-1">
                    <option v-for="hour in 24" :key="hour" :value="pad(hour - 1)">
                        {{ pad(hour - 1) }}
                    </option>
                </select>
                <select v-model="selectedMinute" class="border rounded-md px-2 py-1">
                    <option v-for="minute in 60" :key="minute" :value="pad(minute - 1)">
                        {{ pad(minute - 1) }}
                    </option>
                </select>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <Button variant="outline" @click="clear">Reset</Button>
                <Button @click="save">Simpan</Button>
            </div>
        </PopoverContent>
    </Popover>
</template>
