<script setup lang="ts">
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import { Badge } from '@/shadcn/ui/badge'
import { Button } from '@/shadcn/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shadcn/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/ui/table'
import { Activity, ArrowUpRight, CircleUser, CreditCard, DollarSign, Menu, Package2, Search, Users } from 'lucide-vue-next'
import { Avatar } from "@/shadcn/ui/avatar";

defineOptions({
    layout: LayoutWrapper
})
</script>

<template>
    <main class="flex flex-1 flex-col gap-4 p-4 md:gap-8 md:p-8">
        <div class="grid gap-4 md:grid-cols-2 md:gap-8 lg:grid-cols-4">
            <Card class="outline outline-destructive">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">
                        Total Late Employee
                    </CardTitle>
                    <DollarSign class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        12
                    </div>
                    <p class="text-xs text-muted-foreground">
                        +20.1% from last month
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">
                       Total On Time Employee
                    </CardTitle>
                    <Users class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        23
                    </div>
                    <p class="text-xs text-muted-foreground">
                        +180.1% from last month
                    </p>
                </CardContent>
            </Card>
            <Card class="outline outline-destructive">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">
                       Total Non-Active Employee
                    </CardTitle>
                    <Activity class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        3
                    </div>
                    <p class="text-xs text-muted-foreground">
                        -2 since yesterday
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">
                        Total Active Now Employee
                    </CardTitle>
                    <CreditCard class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        35
                    </div>
                    <p class="text-xs text-muted-foreground">
                        +2 since yesterday
                    </p>
                </CardContent>
            </Card>
        </div>
        <div class="grid gap-4 md:gap-8 lg:grid-cols-2 xl:grid-cols-3">
            <Card class="xl:col-span-2">
                <CardHeader class="flex flex-row items-center">
                    <div class="grid gap-2">
                        <CardTitle>Salary Transactions</CardTitle>
                        <CardDescription>
                            Recent Salary Transactions from your company.
                        </CardDescription>
                    </div>
                    <Button as-child size="sm" class="ml-auto gap-1 bg-black">
                        <a href="#">
                            View All
                            <ArrowUpRight class="h-4 w-4" />
                        </a>
                    </Button>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Customer</TableHead>
                                <TableHead class="hidden xl:table-column">
                                    Type
                                </TableHead>
                                <TableHead class="hidden xl:table-column">
                                    Status
                                </TableHead>
                                <TableHead class="hidden xl:table-column">
                                    Date
                                </TableHead>
                                <TableHead class="text-right">
                                    Amount
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow>
                                <TableCell>
                                    <div class="font-medium">
                                        Liam Johnson
                                    </div>
                                    <div class="hidden text-sm text-muted-foreground md:inline">
                                        liam@example.com
                                    </div>
                                </TableCell>
                                <TableCell class="hidden xl:table-column">
                                    Sale
                                </TableCell>
                                <TableCell class="hidden xl:table-column">
                                    <Badge class="text-xs" variant="outline">
                                        Approved
                                    </Badge>
                                </TableCell>
                                <TableCell class="hidden md:table-cell lg:hidden xl:table-column">
                                    2023-06-23
                                </TableCell>
                                <TableCell class="text-right">
                                    $250.00
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell>
                                    <div class="font-medium">
                                        Olivia Smith
                                    </div>
                                    <div class="hidden text-sm text-muted-foreground md:inline">
                                        olivia@example.com
                                    </div>
                                </TableCell>
                                <TableCell class="hidden xl:table-column">
                                    Refund
                                </TableCell>
                                <TableCell class="hidden xl:table-column">
                                    <Badge class="text-xs" variant="outline">
                                        Declined
                                    </Badge>
                                </TableCell>
                                <TableCell class="hidden md:table-cell lg:hidden xl:table-column">
                                    2023-06-24
                                </TableCell>
                                <TableCell class="text-right">
                                    $150.00
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell>
                                    <div class="font-medium">
                                        Noah Williams
                                    </div>
                                    <div class="hidden text-sm text-muted-foreground md:inline">
                                        noah@example.com
                                    </div>
                                </TableCell>
                                <TableCell class="hidden xl:table-column">
                                    Subscription
                                </TableCell>
                                <TableCell class="hidden xl:table-column">
                                    <Badge class="text-xs" variant="outline">
                                        Approved
                                    </Badge>
                                </TableCell>
                                <TableCell class="hidden md:table-cell lg:hidden xl:table-column">
                                    2023-06-25
                                </TableCell>
                                <TableCell class="text-right">
                                    $350.00
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell>
                                    <div class="font-medium">
                                        Emma Brown
                                    </div>
                                    <div class="hidden text-sm text-muted-foreground md:inline">
                                        emma@example.com
                                    </div>
                                </TableCell>
                                <TableCell class="hidden xl:table-column">
                                    Sale
                                </TableCell>
                                <TableCell class="hidden xl:table-column">
                                    <Badge class="text-xs" variant="outline">
                                        Approved
                                    </Badge>
                                </TableCell>
                                <TableCell class="hidden md:table-cell lg:hidden xl:table-column">
                                    2023-06-26
                                </TableCell>
                                <TableCell class="text-right">
                                    $450.00
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell>
                                    <div class="font-medium">
                                        Liam Johnson
                                    </div>
                                    <div class="hidden text-sm text-muted-foreground md:inline">
                                        liam@example.com
                                    </div>
                                </TableCell>
                                <TableCell class="hidden xl:table-column">
                                    Sale
                                </TableCell>
                                <TableCell class="hidden xl:table-column">
                                    <Badge class="text-xs" variant="outline">
                                        Approved
                                    </Badge>
                                </TableCell>
                                <TableCell class="hidden md:table-cell lg:hidden xl:table-column">
                                    2023-06-27
                                </TableCell>
                                <TableCell class="text-right">
                                    $550.00
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell>
                                    <div class="font-medium">
                                        Liam Johnson
                                    </div>
                                    <div class="hidden text-sm text-muted-foreground md:inline">
                                        liam@example.com
                                    </div>
                                </TableCell>
                                <TableCell class="hidden xl:table-column">
                                    Sale
                                </TableCell>
                                <TableCell class="hidden xl:table-column">
                                    <Badge class="text-xs" variant="outline">
                                        Approved
                                    </Badge>
                                </TableCell>
                                <TableCell class="hidden md:table-cell lg:hidden xl:table-column">
                                    2023-06-27
                                </TableCell>
                                <TableCell class="text-right">
                                    $550.00
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>Recent Presence</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-8">
                    <div class="flex items-center gap-4">
                        <Avatar class="hidden h-9 w-9 sm:flex">
                            <AvatarImage src="/avatars/01.png" alt="Avatar" />
                            <AvatarFallback>OM</AvatarFallback>
                        </Avatar>
                        <div class="grid gap-1">
                            <p class="text-sm font-medium leading-none">
                                Olivia Martin
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Mulyorej Office
                            </p>
                        </div>
                        <div class="ml-auto font-medium">
                            08.30
                        </div>
                        <Badge class="text-xs bg-green-500">On Time</Badge>
                    </div>
                    <div class="flex items-center gap-4">
                        <Avatar class="hidden h-9 w-9 sm:flex">
                            <AvatarImage src="/avatars/02.png" alt="Avatar" />
                            <AvatarFallback>JL</AvatarFallback>
                        </Avatar>
                        <div class="grid gap-1">
                            <p class="text-sm font-medium leading-none">
                                Jackson Lee
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Mulyorejo Office
                            </p>
                        </div>
                        <div class="ml-auto font-medium">
                            08.39
                        </div>
                        <Badge class="text-xs" variant="destructive">Late 9m</Badge>
                    </div>
                    <div class="flex items-center gap-4">
                        <Avatar class="hidden h-9 w-9 sm:flex">
                            <AvatarImage src="/avatars/02.png" alt="Avatar" />
                            <AvatarFallback>JL</AvatarFallback>
                        </Avatar>
                        <div class="grid gap-1">
                            <p class="text-sm font-medium leading-none">
                                Jackson Lee
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Mulyorejo Office
                            </p>
                        </div>
                        <div class="ml-auto font-medium">
                            08.39
                        </div>
                        <Badge class="text-xs" variant="destructive">Late 9m</Badge>
                    </div>
                    <div class="flex items-center gap-4">
                        <Avatar class="hidden h-9 w-9 sm:flex">
                            <AvatarImage src="/avatars/02.png" alt="Avatar" />
                            <AvatarFallback>JL</AvatarFallback>
                        </Avatar>
                        <div class="grid gap-1">
                            <p class="text-sm font-medium leading-none">
                                Jackson Lee
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Mulyorejo Office
                            </p>
                        </div>
                        <div class="ml-auto font-medium">
                            08.39
                        </div>
                        <Badge class="text-xs" variant="destructive">Late 9m</Badge>
                    </div>
                    <div class="flex items-center gap-4">
                        <Avatar class="hidden h-9 w-9 sm:flex">
                            <AvatarImage src="/avatars/01.png" alt="Avatar" />
                            <AvatarFallback>OM</AvatarFallback>
                        </Avatar>
                        <div class="grid gap-1">
                            <p class="text-sm font-medium leading-none">
                                Olivia Martin
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Mulyorej Office
                            </p>
                        </div>
                        <div class="ml-auto font-medium">
                            08.30
                        </div>
                        <Badge class="text-xs bg-green-500">On Time</Badge>
                    </div>
                </CardContent>
            </Card>
        </div>
    </main>
</template>

<style scoped>

</style>
