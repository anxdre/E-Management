import {type ClassValue, clsx} from 'clsx'
import {twMerge} from 'tailwind-merge'
import {router} from "@inertiajs/vue3";
import {useGlobalLoaderStrore} from "@/lib/GlobalLoaderStore";
import { toast, useToast } from "@/shadcn/ui/toast";

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

export class PaginationOption {
    public currentPage: number = 1
    public from: number = 1
    public to: number = 1
    public nextPageUrl: string
    public prevPageUrl: string
    public totalData: number
    public perPageData: number = 10
    public searchQuery: string = ''
    public filter: string
    public sortBy: string
}

export function errorToast(title: string, msg: string = '') {
    toast({
        title: title,
        description: msg,
        variant: "destructive"
    });
}

export function warnToast(title: string, msg: string = '') {
    toast({
        title: title,
        description: msg,
        variant: "warn"
    });
}

export function successToast(title: string, msg: string = '') {
    toast({
        title: title,
        description: msg,
        variant: "success"
    });
}
export function defaultToast(title: string, msg: string = '') {
    toast({
        title: title,
        description: msg,
        variant: "default"
    });
}
