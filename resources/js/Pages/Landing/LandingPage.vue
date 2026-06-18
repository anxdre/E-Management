<script setup lang="ts">
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import { Head } from '@inertiajs/vue3'
import { Button } from "@/shadcn/ui/button";
import {
    ChevronsRight, UsersRound, UserRound, MapPinHouse, HandCoins,
    LayoutDashboard, MapPinCheck, Cog, Settings, Smartphone, FileText,
    Building2, Clock, NotepadText
} from "lucide-vue-next";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/shadcn/ui/card";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/shadcn/ui/accordion'
import { ref } from "vue";
import { cn, navigateLink } from "@/lib/utils";

defineOptions({
    layout: LayoutWrapper
})

const hoverState = ref(1)
const focussedCard = cn('bg-white text-black')

setInterval(()=>{
    if (hoverState.value === 5){
        hoverState.value = 0
    }
    hoverState.value++
},3500)
</script>

<template>
    <Head title="Teamway — HR Management Solution"/>
    <section title="hero-1">
        <div class="w-full min-h-screen md:grid md:grid-cols-3 bg-primary justify-center items-center">
            <div class="w-full h-full bg-black flex flex-col justify-evenly p-12">
                <div class="flex flex-col">
                    <UsersRound :stroke-width="2" class="size-16 text-white"/>
                    <h1 class="text-white text-pretty tracking-tight">Teamway®</h1>
                    <h2 class="text-white text-pretty tracking-tight mt-4">Easy Team Management, Maximum Results</h2>
                </div>
                <div class="flex-col flex gap-4">
                    <p class="text-white">Empower your organization with <b>Teamway</b> — an integrated HR management
                        solution for employee management, GPS-based presence tracking, and automated payroll processing.</p>
                    <Button @click="navigateLink(route('auth.sign-in'))"
                        class="py-6 w-fit pl-4 pr-8 hover:bg-black hover:cursor-pointer border border-black hover:border-primary font-semibold gap-4 align-middle">
                        <ChevronsRight class="size-10 animate-pulse"/>
                        <span class="text-lg">Mulai Sekarang</span>
                    </Button>
                </div>
            </div>
            <div class="w-full h-full col-span-2 justify-center flex p-4 lg:gap-0 gap-2 space-y-2 flex-col">
                <div class="flex flex-col-reverse gap-4 lg:gap-0 lg:grid grid-cols-4 lg:space-x-2">
                    <div class="col-span-1">
                        <Card
                            class="bg-black h-full text-white border-0 shadow-lg hover:bg-white hover:text-black transition-all duration-1000"
                            :class="hoverState == 1 && focussedCard">
                            <CardHeader>
                                <CardTitle>Employee Management</CardTitle>
                                <CardDescription>
                                    Manage accounts, groups & departments
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <UserRound class="size-16"/>
                            </CardContent>
                            <CardFooter>
                                <small class="text-muted-foreground">*Full CRUD with group assignment</small>
                            </CardFooter>
                        </Card>
                    </div>
                    <div class="col-span-3">
                        <Card
                            class="w-full p-4 h-full bg-black text-white border-0 shadow-lg hover:bg-white hover:text-black transition-all duration-1000"
                            :class="hoverState == 4 && focussedCard">
                            <div class="flex flex-col-reverse lg:flex lg:flex-row w-full gap-4 h-full items-center">
                                <div class="flex-col flex w-full justify-center h-full mr-3">
                                    <h3 class="w-full lg:text-end">Simplify Your Workforce</h3>
                                    <p class="w-full lg:text-end text-muted-foreground text-pretty">From employee onboarding to automated payroll, Teamway streamlines every aspect of HR management with real-time presence tracking and smart salary calculations.</p>
                                </div>
                                <img class="w-full lg:size-64 object-cover rounded-lg" src="../../../img/hero-1/banner-1.jpg" alt="">
                            </div>
                        </Card>
                    </div>
                </div>
                <div class="flex flex-col gap-4 lg:gap-0 lg:grid grid-cols-4 lg:space-x-2">
                    <div class="col-span-2">
                        <Card
                            class="bg-black h-full text-white border-0 shadow-lg hover:bg-white hover:text-black transition-all duration-1000"
                            :class="hoverState == 2 && focussedCard">
                            <CardHeader>
                                <CardTitle>Presence Tracking</CardTitle>
                                <CardDescription>
                                    Real-time attendance with GPS verification
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <MapPinHouse class="size-16"/>
                            </CardContent>
                            <CardFooter>
                                <small class="text-muted-foreground">*Location radius + unique verification code</small>
                            </CardFooter>
                        </Card>
                    </div>
                    <div class="col-span-1">
                        <Card
                            class="bg-black text-white border-0 shadow-lg hover:bg-white hover:text-black transition-all duration-1000 h-full"
                            :class="hoverState == 3 && focussedCard">
                            <CardHeader>
                                <CardTitle>Smart Payroll</CardTitle>
                                <CardDescription>
                                    Fixed, hourly, presence & tax components
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <HandCoins class="size-16"/>
                            </CardContent>
                            <CardFooter>
                                <small class="text-muted-foreground">*Automatic calculation per period</small>
                            </CardFooter>
                        </Card>
                    </div>
                    <div class="col-span-1">
                        <Card
                            class="bg-black text-white border-0 shadow-lg hover:bg-white hover:text-black transition-all duration-1000"
                            :class="hoverState == 5 && focussedCard">
                            <CardHeader>
                                <CardTitle>Digital Receipt</CardTitle>
                                <CardDescription>
                                    Payslips with full history & approval
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <NotepadText class="size-16"/>
                            </CardContent>
                            <CardFooter>
                                <small class="text-muted-foreground">*Pending → approved workflow</small>
                            </CardFooter>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="w-full py-24 px-6 md:px-12 bg-white">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-black">Why Teamway?</h2>
                <p class="text-muted-foreground mt-4 max-w-2xl mx-auto">All-in-one HR platform designed for modern workforce management</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center p-8 rounded-xl bg-muted hover:shadow-lg transition-shadow">
                    <div class="inline-flex p-4 rounded-full bg-primary/10 mb-6">
                        <LayoutDashboard class="size-12 text-primary"/>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">All-in-One Platform</h3>
                    <p class="text-muted-foreground">HR management, presence tracking, and payroll — fully integrated in one platform.</p>
                </div>
                <div class="text-center p-8 rounded-xl bg-muted hover:shadow-lg transition-shadow">
                    <div class="inline-flex p-4 rounded-full bg-primary/10 mb-6">
                        <MapPinCheck class="size-12 text-primary"/>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">GPS-Based Verification</h3>
                    <p class="text-muted-foreground">Accurate attendance with GPS radius check and unique daily verification codes.</p>
                </div>
                <div class="text-center p-8 rounded-xl bg-muted hover:shadow-lg transition-shadow">
                    <div class="inline-flex p-4 rounded-full bg-primary/10 mb-6">
                        <Cog class="size-12 text-primary"/>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Automated Payroll</h3>
                    <p class="text-muted-foreground">Salary calculations based on attendance data and configurable components — no manual work.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="w-full py-24 px-6 md:px-12 bg-black text-white">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold">Cara Kerja</h2>
                <p class="text-white/70 mt-4 max-w-2xl mx-auto">Tiga langkah sederhana untuk mengelola HR perusahaan Anda</p>
            </div>
            <div class="grid md:grid-cols-3 gap-12 relative">
                <div class="hidden md:block absolute top-24 left-[16%] right-[16%] h-0.5 bg-primary/30"></div>
                <div class="text-center relative">
                    <div class="inline-flex items-center justify-center size-16 rounded-full bg-primary text-white text-2xl font-bold mb-6 relative z-10">1</div>
                    <div class="inline-flex p-4 rounded-full bg-primary/20 mt-4 mb-6">
                        <Settings class="size-12 text-primary"/>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Setup</h3>
                    <p class="text-white/70">Admin mengatur lokasi presensi, grup karyawan, dan komponen gaji</p>
                </div>
                <div class="text-center relative">
                    <div class="inline-flex items-center justify-center size-16 rounded-full bg-primary text-white text-2xl font-bold mb-6 relative z-10">2</div>
                    <div class="inline-flex p-4 rounded-full bg-primary/20 mt-4 mb-6">
                        <Smartphone class="size-12 text-primary"/>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Check-In</h3>
                    <p class="text-white/70">Karyawan melakukan absensi via mobile app dengan GPS dan kode verifikasi</p>
                </div>
                <div class="text-center relative">
                    <div class="inline-flex items-center justify-center size-16 rounded-full bg-primary text-white text-2xl font-bold mb-6 relative z-10">3</div>
                    <div class="inline-flex p-4 rounded-full bg-primary/20 mt-4 mb-6">
                        <FileText class="size-12 text-primary"/>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Payroll</h3>
                    <p class="text-white/70">Slip gaji tergenerate otomatis — admin tinggal review dan approve</p>
                </div>
            </div>
        </div>
    </section>

    <section class="w-full py-24 px-6 md:px-12 bg-primary text-white">
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <Building2 class="size-12 mx-auto mb-4"/>
                    <div class="text-5xl font-bold mb-2">500+</div>
                    <div class="text-primary-foreground/80">Perusahaan Terdaftar</div>
                </div>
                <div>
                    <UsersRound class="size-12 mx-auto mb-4"/>
                    <div class="text-5xl font-bold mb-2">10rb+</div>
                    <div class="text-primary-foreground/80">Karyawan Aktif</div>
                </div>
                <div>
                    <Clock class="size-12 mx-auto mb-4"/>
                    <div class="text-5xl font-bold mb-2">50rb+</div>
                    <div class="text-primary-foreground/80">Presensi per Bulan</div>
                </div>
                <div>
                    <HandCoins class="size-12 mx-auto mb-4"/>
                    <div class="text-5xl font-bold mb-2">5rb+</div>
                    <div class="text-primary-foreground/80">Slip Gaji Terbit</div>
                </div>
            </div>
        </div>
    </section>

    <section class="w-full py-24 px-6 md:px-12 bg-white">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-black">FAQ</h2>
                <p class="text-muted-foreground mt-4">Pertanyaan umum seputar Teamway</p>
            </div>
            <Accordion type="single" collapsible class="w-full">
                <AccordionItem value="item-1">
                    <AccordionTrigger class="text-left font-semibold">Siapa yang bisa menggunakan Teamway?</AccordionTrigger>
                    <AccordionContent class="text-muted-foreground">Teamway digunakan oleh perusahaan/admin via dashboard web untuk mengelola karyawan, dan oleh karyawan via aplikasi mobile untuk melakukan presensi dan melihat slip gaji.</AccordionContent>
                </AccordionItem>
                <AccordionItem value="item-2">
                    <AccordionTrigger class="text-left font-semibold">Bagaimana sistem verifikasi presensi?</AccordionTrigger>
                    <AccordionContent class="text-muted-foreground">Presensi diverifikasi menggunakan kombinasi GPS (dengan radius toleransi yang ditentukan admin) dan kode verifikasi unik yang digenerate setiap hari untuk setiap lokasi absensi.</AccordionContent>
                </AccordionItem>
                <AccordionItem value="item-3">
                    <AccordionTrigger class="text-left font-semibold">Apakah karyawan bisa mengakses dashboard web?</AccordionTrigger>
                    <AccordionContent class="text-muted-foreground">Tidak. Dashboard web khusus untuk admin/company. Karyawan hanya mengakses sistem melalui aplikasi mobile untuk presensi dan melihat slip gaji.</AccordionContent>
                </AccordionItem>
                <AccordionItem value="item-4">
                    <AccordionTrigger class="text-left font-semibold">Bagaimana perhitungan gaji otomatis?</AccordionTrigger>
                    <AccordionContent class="text-muted-foreground">Admin mengatur komponen gaji (fixed, hourly, presence, tax) dan menugaskannya ke karyawan. Sistem akan mengkalkulasi secara otomatis berdasarkan data presensi dan komponen yang telah ditentukan setiap periode.</AccordionContent>
                </AccordionItem>
                <AccordionItem value="item-5">
                    <AccordionTrigger class="text-left font-semibold">Apakah data perusahaan aman?</AccordionTrigger>
                    <AccordionContent class="text-muted-foreground">Ya. Teamway menggunakan enkripsi AES-256 untuk token, Sanctum token untuk autentikasi API, rate limiting pada login, dan session-based auth untuk web.</AccordionContent>
                </AccordionItem>
            </Accordion>
        </div>
    </section>

    <footer class="w-full py-12 px-6 md:px-12 bg-black text-white">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-center gap-8">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <UsersRound :stroke-width="2" class="size-8 text-primary"/>
                        <span class="text-2xl font-bold">Teamway®</span>
                    </div>
                    <p class="text-white/60 text-sm">Easy Team Management, Maximum Results</p>
                </div>
                <div class="text-center md:text-right">
                    <p class="text-white/60 text-sm">© 2026 Teamway. All rights reserved.</p>
                    <Button @click="navigateLink(route('auth.sign-in'))" variant="link" class="text-primary p-0 h-auto mt-2">
                        Sign In
                    </Button>
                </div>
            </div>
        </div>
    </footer>
</template>

<style scoped>

</style>
