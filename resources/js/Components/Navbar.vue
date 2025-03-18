<script setup lang="ts">
import { CircleUser, Menu, UsersRound,UserRound } from "lucide-vue-next";
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger, } from '@/shadcn/ui/sheet'
import { Button } from "@/shadcn/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/shadcn/ui/dropdown-menu'
import { navigateLink } from "@/lib/utils";
import { router } from "@inertiajs/vue3";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/shadcn/ui/accordion'
import { Popover, PopoverContent, PopoverTrigger, } from '@/shadcn/ui/popover'
</script>

<template>
    <nav v-if="$attrs.auth?.user != undefined || null"
         class="hidden flex-col gap-6 text-lg font-medium md:flex md:flex-row md:items-center md:gap-5 md:text-sm lg:gap-6">
        <a
            href="#"
            class="inline-flex items-center text-lg"
        >
            <UsersRound :stroke-width="2" class="size-6 text-black"/>
        </a>
        <Button @click="navigateLink(route('dashboard.index'))"
                :variant="$page.url.startsWith('/Dashboard') ? 'default':'ghost'"
                class="hover:text-foreground">
            Dashboard
        </Button>
        <div class="inline-flex h-fit">
            <Popover>
                <PopoverTrigger>
                    <Button :variant="$page.url.startsWith('/Presence') ? 'default':'ghost'"
                            class="hover:text-foreground">
                        Presence
                    </Button>
                </PopoverTrigger>
                <PopoverContent class="space-y-2 px-0 py-1">
                    <ul>
                        <Button @click="navigateLink(route('presence-location.index'))"
                                variant="ghost"
                                class="hover:text-foreground justify-start w-full rounded">
                            Presence Location
                        </Button>
                    </ul>
                    <ul>
                        <Button @click="navigateLink(route('dashboard.index'))"
                                variant="ghost"
                                class="hover:text-foreground justify-start w-full rounded">
                            Employee Presence
                        </Button>
                    </ul>
                    <ul>
                        <Button @click="navigateLink(route('dashboard.index'))"
                                variant="ghost"
                                class="hover:text-foreground justify-start w-full rounded">
                            Dashboard
                        </Button>
                    </ul>
                </PopoverContent>
            </Popover>
        </div>
        <div class="inline-flex h-fit">
            <Popover>
                <PopoverTrigger>
                    <Button :variant="$page.url.startsWith('/Employee') ? 'default':'ghost'"
                            class="hover:text-foreground">
                        Employee
                    </Button>
                </PopoverTrigger>
                <PopoverContent class="space-y-2 px-0 py-1 w-fit">
                    <ul>
                        <Button @click="navigateLink(route('employee-group.index'))"
                                variant="ghost"
                                class="hover:text-foreground gap-2 justify-start w-full rounded">
                            <UsersRound size="18"/>
                            Employee Group
                        </Button>
                    </ul>
                    <ul>
                        <Button @click="navigateLink(route('employee-account.index'))"
                                variant="ghost"
                                class="hover:text-foreground gap-2 justify-start w-full rounded">
                            <UserRound size="18"/>
                            Employee Account
                        </Button>
                    </ul>
                </PopoverContent>
            </Popover>
        </div>
        <div class="inline-flex h-fit">
            <Popover>
                <PopoverTrigger>
                    <Button :variant="$page.url.startsWith('/Management') ? 'default':'ghost'"
                            class="hover:text-foreground">
                        Management
                    </Button>
                </PopoverTrigger>
                <PopoverContent class="space-y-2 px-0 py-1 w-fit">
                    <ul>
                        <Button @click="navigateLink(route('employee-group.index'))"
                                variant="ghost"
                                class="hover:text-foreground gap-2 justify-start w-full rounded">
                            <UsersRound size="18"/>
                            Employee Group
                        </Button>
                    </ul>
                    <ul>
                        <Button @click="navigateLink(route('employee-account.index'))"
                                variant="ghost"
                                class="hover:text-foreground gap-2 justify-start w-full rounded">
                            <UserRound size="18"/>
                            Employee Account
                        </Button>
                    </ul>
                </PopoverContent>
            </Popover>
        </div>
    </nav>
    <nav v-else
         class="hidden flex-col gap-6 text-lg font-medium md:flex md:flex-row md:items-center md:gap-5 md:text-sm lg:gap-6">
        <a
            href="#"
            class="inline-flex items-center text-lg"
        >
            <UsersRound :stroke-width="2" class="size-6 text-black"/>
        </a>
        <a class="text-xl font-bold">Teamway</a>
    </nav>
    <Sheet>
        <SheetTrigger as-child>
            <Button
                variant="outline"
                size="icon"
                class="shrink-0 md:hidden"
            >
                <Menu class="h-5 w-5"/>
                <span class="sr-only">Toggle navigation menu</span>
            </Button>
        </SheetTrigger>
        <SheetContent class="h-full p-0 justify-between flex flex-col overflow-y-scroll" side="left">
            <div>
                <SheetHeader>
                    <SheetTitle/>
                    <SheetDescription/>
                </SheetHeader>
                <nav class="grid gap-6 text-lg p-8 font-medium">
                    <a
                        href="#"
                        class="flex items-center gap-2 text-lg font-semibold"
                    >
                        <UsersRound :stroke-width="2" class="size-6"/>
                        <span>E-Management</span>
                    </a>
                    <Button @click="navigateLink(route('dashboard.index'))"
                            :variant="$page.url.startsWith('/Dashboard') ? 'default':'ghost'"
                            class="hover:text-foreground justify-start">
                        Dashboard
                    </Button>
                    <Accordion type="single" collapsible>
                        <AccordionItem value="item-1">
                            <AccordionTrigger
                                :class="{'text-white font-semibold bg-primary':$page.url.startsWith('/Presence')}"
                                class="py-2 px-4 rounded-md">
                                Presence
                            </AccordionTrigger>
                            <AccordionContent class="space-y-4 w-full p-4">
                                <ul>
                                    <Button @click="navigateLink(route('presence-location.index'))"
                                            variant="ghost"
                                            class="hover:text-foreground justify-start w-full rounded">
                                        Presence Location
                                    </Button>
                                </ul>
                                <ul>
                                    <Button @click="navigateLink(route('dashboard.index'))"
                                            variant="ghost"
                                            class="hover:text-foreground justify-start w-full rounded">
                                        Employee Presence
                                    </Button>
                                </ul>
                                <ul>
                                    <Button @click="navigateLink(route('dashboard.index'))"
                                            variant="ghost"
                                            class="hover:text-foreground justify-start w-full">
                                        Dashboard
                                    </Button>
                                </ul>
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                    <Accordion type="single" collapsible>
                        <AccordionItem value="item-1">
                            <AccordionTrigger
                                :class="{'text-white font-semibold bg-primary':$page.url.startsWith('/Employee')}"
                                class="py-2 px-4 rounded-md">
                                Employee
                            </AccordionTrigger>
                            <AccordionContent class="space-y-4 w-full p-4">
                                <ul>
                                    <Button @click="navigateLink(route('employee-group.index'))"
                                            variant="ghost"
                                            class="hover:text-foreground gap-2 justify-start w-full rounded">
                                        <UsersRound size="18"/>
                                        Employee Group
                                    </Button>
                                </ul>
                                <ul>
                                    <Button @click="navigateLink(route('employee-account.index'))"
                                            variant="ghost"
                                            class="hover:text-foreground gap-2 justify-start w-full rounded">
                                        <UserRound size="18"/>
                                        Employee Account
                                    </Button>
                                </ul>
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                    <Accordion type="single" collapsible>
                        <AccordionItem value="item-1">
                            <AccordionTrigger
                                :class="{'text-white font-semibold bg-primary':$page.url.startsWith('/Management')}"
                                class="py-2 px-4 rounded-md">
                                Management
                            </AccordionTrigger>
                            <AccordionContent class="space-y-4 w-full p-4">
                                <ul>
                                    <Button @click="navigateLink(route('employee-group.index'))"
                                            variant="ghost"
                                            class="hover:text-foreground gap-2 justify-start w-full rounded">
                                        <UsersRound size="18"/>
                                        Employee Group
                                    </Button>
                                </ul>
                                <ul>
                                    <Button @click="navigateLink(route('dashboard.index'))"
                                            variant="ghost"
                                            class="hover:text-foreground justify-start w-full">
                                        Dashboard
                                    </Button>
                                </ul>
                                <ul>
                                    <Button @click="navigateLink(route('dashboard.index'))"
                                            variant="ghost"
                                            class="hover:text-foreground justify-start w-full">
                                        Dashboard
                                    </Button>
                                </ul>
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </nav>
            </div>
            <div @click="navigateLink(route('auth.sign-in'))"
                 class="flex flex-row cursor-pointer  items-center w-full mb-8 py-4 px-4 gap-4 bg-primary hover:bg-gray-800">
                <div size="icon" class="bg-white p-1 rounded-full">
                    <CircleUser class="h-5 w-5"/>
                </div>
                <span v-if="$attrs.auth?.user"
                      class="font-semibold text-primary-foreground">{{
                        $attrs.auth?.user?.first_name + $attrs.auth?.user?.last_name
                    }}</span>
                <span v-else class="font-semibold text-primary-foreground">Sign In</span>
            </div>
        </SheetContent>
    </Sheet>
    <div v-if="$attrs.auth?.user != undefined || null"
         class="flex flex-row w-full items-center justify-end gap-4 md:ml-auto md:gap-2 lg:gap-4">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="secondary" size="icon" class="rounded-full">
                    <CircleUser class="h-5 w-5"/>
                    <span class="sr-only">Toggle user menu</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuLabel>My Account</DropdownMenuLabel>
                <DropdownMenuSeparator/>
                <DropdownMenuItem>Settings</DropdownMenuItem>
                <DropdownMenuItem>Support</DropdownMenuItem>
                <DropdownMenuSeparator/>
                <DropdownMenuItem @click="router.post(route('auth.sign-out'))">Logout</DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
    <div v-else
         class="flex flex-row w-full items-center justify-end gap-4 md:ml-auto md:gap-2 lg:gap-4">
        <Button
            @click="navigateLink(route('auth.sign-in'))">
            Sign In
        </Button>
    </div>
</template>

<style scoped>

</style>
