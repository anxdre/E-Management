import type { Updater } from '@tanstack/vue-table'
import type { Ref } from 'vue'
import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'
import { router } from "@inertiajs/vue3";
import { useGlobalLoaderStrore } from "@/lib/GlobalLoaderStore";
import { toast, useToast } from "@/shadcn/ui/toast";
import dayjs, { ConfigType } from "dayjs";

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs))
}

export function valueUpdater<T extends Updater<any>>(updaterOrValue: T, ref: Ref) {
    ref.value
        = typeof updaterOrValue === 'function'
        ? updaterOrValue(ref.value)
        : updaterOrValue
}

export const imgFolder = '../../../img/'

export function getImageUrl(path: string) {
    return new URL('../../../img/' + path, import.meta.url).href
}

export function extractError(errors: any) {
    return Object.values(errors).join('\n')
}

export const PhoneRegex = new RegExp(
    /^([+]?[\s0-9]+)?(\d{3}|[(]?[0-9]+[)])?([-]?[\s]?[0-9])+$/
);

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

// Helper function to create FormData
export function createFormData(values) {
    const formData = new FormData()

    Object.keys(values).forEach((key) => {
        const value = values[key]

        // Check if the value is an array (useful if you want to handle multiple files or arrays of data)
        if (Array.isArray(value)) {
            value.forEach((item, index) => {
                formData.append(`${key}[${index}]`, item)
            })
        }
        // Check if the value is a File or Blob (for file uploads)
        else if (value instanceof File || value instanceof Blob) {
            formData.append(key, value)
        }
        // If it's not a file or array, append it as a string or primitive value
        else {
            formData.append(key, value)
        }
    })
    return formData
}

export function formatWorkingHour(inTime:string, outTime:string) {
    if (!inTime || !outTime) return null;
    const ms = dayjs(outTime).diff(dayjs(inTime))
    const totalSec = Math.floor(ms / 1000);
    const hours = Math.floor(totalSec / 3600);
    const minutes = Math.floor((totalSec % 3600) / 60);
    const seconds = totalSec % 60;
    const pad = (n) => String(n).padStart(2, '0');
    return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
}

export const formatDate = (dateRaw,withTime:boolean = true) => {
    if (!dateRaw) return '-'
    const date = new Date(dateRaw)

    const day = date.getDate();
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
    const time = dayjs(dateRaw).format('HH:mm:ss')

    if (!withTime) return `${day}/${month}/${year}`

    return `${day}/${month}/${year} ${time}`;
}

export const appUrl = import.meta.env.VITE_APP_URL

export function showGlobalLoader() {
    useGlobalLoaderStrore().isLoading = true
    useGlobalLoaderStrore().darkenBg = true
}

export function hideGlobalLoader() {
    useGlobalLoaderStrore().isLoading = false
    useGlobalLoaderStrore().darkenBg = false
}
