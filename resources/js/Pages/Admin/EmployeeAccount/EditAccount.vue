<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/shadcn/ui/dialog";
import { toTypedSchema } from "@vee-validate/zod";
import { z } from "zod";
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/shadcn/ui/form";
import { vAutoAnimate } from "@formkit/auto-animate";
import { useForm } from "vee-validate";
import { ref } from "vue";
import { Input } from "@/shadcn/ui/input";
import { Button } from "@/shadcn/ui/button";
import { LoaderCircleIcon } from "lucide-vue-next";
import axios from "axios";

const isShowing = defineModel<Boolean>('isShowing', { required: true })
const emit = defineEmits<{
    (e: 'successCreated', data: any): void
}>()
const props = defineProps<{
    errors?: any
}>()
const isLoading = ref(false)

const formSchema = toTypedSchema(z.object({
    name: z.string(),
}))

const { handleSubmit, isFieldDirty, setErrors, setFieldValue, values } = useForm({
    validationSchema: formSchema,
})

const onSubmit = handleSubmit((values, ctx) => {
    isLoading.value = true
    axios.post(route('employee-group.json.add'), values, {}).then(() => {
        emit('successCreated', values)
        isShowing.value = false
    }).catch((error) => {
        setErrors(error.response.data.errors)
    }).finally(() => {
        isLoading.value = false
    })
})
</script>

<template>
    <Dialog v-model:open="isShowing">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Create Employee Group</DialogTitle>
                <DialogDescription>
                    Make new group of employee here. Click save when you're done.
                </DialogDescription>
            </DialogHeader>
            <form class="space-y-4" @submit.prevent="onSubmit">
                <div class="grid gap-4">
                    <FormField v-slot="{ componentField }" name="name" :validate-on-blur="!isFieldDirty">
                        <FormItem v-auto-animate>
                            <FormLabel>Group Name</FormLabel>
                            <FormControl>
                                <Input class="drop-shadow-lg antialiased" type="text"
                                       placeholder="ex. Programmer"
                                       v-bind="componentField"/>
                            </FormControl>
                            <FormMessage/>
                        </FormItem>
                    </FormField>
                </div>
                <Button class="gap-2" type="submit" v-auto-animate>
                    Save
                    <LoaderCircleIcon v-if="isLoading" class="size-4 animate-spin"/>
                </Button>
            </form>
        </DialogContent>
    </Dialog>
</template>

<style scoped>

</style>
