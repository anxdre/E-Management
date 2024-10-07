<script setup lang="ts">
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import { Head } from '@inertiajs/vue3'
import { Button } from "@/shadcn/ui/button";
import { ChevronsRight, Gauge, UsersRound } from "lucide-vue-next";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/shadcn/ui/card";
import { ref } from "vue";
import { cn } from "@/lib/utils";

defineOptions({
    layout: LayoutWrapper
})

const hoverState = ref(1)
const focussedCard = cn('scale-95 bg-white text-black')

setInterval(()=>{
    if (hoverState.value === 5){
        hoverState.value = 0
    }
    hoverState.value++
},3500)
</script>

<template>
    <Head title="Landing Page"/>
    <section title="hero-1">
        <div class="w-full min-h-screen md:grid md:grid-cols-3 bg-primary justify-center items-center">
            <div class="w-full h-full bg-black flex flex-col justify-evenly p-12">
                <div class="flex flex-col">
                    <UsersRound :stroke-width="2" class="size-16 text-white"/>
                    <h1 class="text-white text-pretty tracking-tight">Teamway®</h1>
                    <h2 class="text-white text-pretty tracking-tight mt-4">Easy Team Management, Maximum Results</h2>
                </div>
                <div class="flex-col flex gap-4">
                    <p class="text-white">Empower your organization with <b>Teamway</b> a powerful HR management
                        solution designed to
                        make team management as smooth and efficient as possible.</p>
                    <Button
                        class="py-6 w-fit pl-4 pr-8 hover:bg-black hover:cursor-pointer  border border-black hover:border-primary font-semibold gap-4 align-middle">
                        <ChevronsRight class="size-10 animate-pulse"/>
                        <span class="text-lg">Try It Now</span>
                    </Button>
                </div>
            </div>
            <div class="w-full h-full col-span-2 justify-center flex p-4 lg:gap-0 gap-2 space-y-2 flex-col">
                <div class="flex flex-col-reverse gap-4 lg:gap-0 lg:grid grid-cols-4 lg:space-x-2">
                    <div class="col-span-1">
                        <Card
                            class="bg-black h-full text-white border-0 shadow-lg hover:scale-95 hover:bg-white hover:text-black transition-all duration-1000"
                            :class="hoverState == 1 && focussedCard">
                            <card-header>
                                <CardTitle>Blazingly Fast</CardTitle>
                                <CardDescription>
                                    Powered by <b> Laravel Reverb</b> for realtime update
                                </CardDescription>
                            </card-header>
                            <CardContent>
                                <Gauge class="size-16"/>
                            </CardContent>
                            <CardFooter>
                                <small class="text-muted-foreground">*Required stable internet connection</small>
                            </CardFooter>
                        </Card>
                    </div>
                    <div class="col-span-3">
                        <Card
                            class="w-full p-4 h-full bg-black text-white border-0 shadow-lg hover:scale-95 hover:bg-white hover:text-black transition-all duration-1000"
                            :class="hoverState == 4 && focussedCard">
                            <div class="flex flex-col-reverse lg:flex lg:flex-row w-full gap-4 h-full items-center">
                                <div class="flex-col flex w-full justify-center h-full mr-3">
                                    <h3 class="w-full lg:text-end ">Maximize your productivity</h3>
                                    <p class="w-full lg:text-end text-muted-foreground text-pretty">Simplifies every aspect
                                        of managing your
                                        workforce, from onboarding and scheduling to salary tracking and team
                                        announcement.</p>
                                </div>
                                <img class="w-full lg:size-64 object-cover rounded-lg" src="../../../img/hero-1/banner-1.jpg"
                                     alt="">
                            </div>
                        </Card>
                    </div>
                </div>
                <div class="flex flex-col gap-4 lg:gap-0 lg:grid grid-cols-4 lg:space-x-2">
                    <div class="col-span-2">
                        <Card
                            class="bg-black h-full text-white border-0 shadow-lg hover:scale-95 hover:bg-white hover:text-black transition-all duration-1000"
                            :class="hoverState == 2 && focussedCard">
                            <card-header>
                                <CardTitle>Blazingly Fast</CardTitle>
                                <CardDescription>
                                    Powered by <b> Laravel Reverb</b> for realtime update
                                </CardDescription>
                            </card-header>
                            <CardContent>
                                <Gauge class="size-16"/>
                            </CardContent>
                            <CardFooter>
                                <small class="text-muted-foreground">*Required stable internet connection</small>
                            </CardFooter>
                        </Card>
                    </div>
                    <div class="col-span-1">
                        <Card
                            class="bg-black text-white border-0 shadow-lg hover:scale-95 hover:bg-white hover:text-black transition-all duration-1000"
                            :class="hoverState == 5 && focussedCard">
                            <card-header>
                                <CardTitle>Blazingly Fast</CardTitle>
                                <CardDescription>
                                    Powered by <b> Laravel Reverb</b> for realtime update
                                </CardDescription>
                            </card-header>
                            <CardContent>
                                <Gauge class="size-16"/>
                            </CardContent>
                            <CardFooter>
                                <small class="text-muted-foreground">*Required stable internet connection</small>
                            </CardFooter>
                        </Card>
                    </div>
                    <div class="col-span-1">
                        <Card
                            class="bg-black text-white border-0 shadow-lg hover:scale-95 hover:bg-white hover:text-black transition-all duration-1000"
                            :class="hoverState == 3 && focussedCard">
                            <card-header>
                                <CardTitle>Blazingly Fast</CardTitle>
                                <CardDescription>
                                    Powered by <b> Laravel Reverb</b> for realtime update
                                </CardDescription>
                            </card-header>
                            <CardContent>
                                <Gauge class="size-16"/>
                            </CardContent>
                            <CardFooter>
                                <small class="text-muted-foreground">*Required stable internet connection</small>
                            </CardFooter>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--slider-->
    <!--Who Are Are-->
    <!--Feature-->
    <!--Testimonial / partner-->
    <!--FaQ-->
    <!--Contact Us-->
    <!--Footer-->
</template>

<style scoped>

</style>
