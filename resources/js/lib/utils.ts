import {type ClassValue, clsx} from 'clsx'
import {twMerge} from 'tailwind-merge'
import {router} from "@inertiajs/vue3";
import {useGlobalLoaderStrore} from "@/lib/GlobalLoaderStore";
import {useToast} from "@/shadcn/ui/toast";

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs))
}

export const imgFolder = '../../../img/'

export function getImageUrl(path: string) {
    return new URL('../../../img/' + path, import.meta.url).href
}

export function navigateLink(route: any, data?: any, options?: any) {
    useToast().dismiss()
    router.get(route, data, {
        ...options,
        onBefore: () => {
            useGlobalLoaderStrore().isLoading = true
            useGlobalLoaderStrore().darkenBg = true
        },
        onFinish: () => {
            useGlobalLoaderStrore().isLoading = false
            useGlobalLoaderStrore().darkenBg = false
        }
    })
}

