<script setup lang="ts">
import { type HTMLAttributes, computed } from 'vue'
import { Label, type LabelProps } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps<
    LabelProps & {
    class?: HTMLAttributes['class']
    required?: boolean
}
>()

const delegatedProps = computed(() => {
    const { class: _, required, ...delegated } = props
    return delegated
})
</script>

<template>
    <Label
        v-bind="delegatedProps"
        :class="
      cn(
        'text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
        props.class,
      )
    "
    >
        <span v-if="props.required" class="text-red-500 ml-1">*</span>
        <slot />
    </Label>
</template>
