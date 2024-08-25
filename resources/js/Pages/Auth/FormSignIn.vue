<script setup lang="ts">

import { Label } from "@/shadcn/ui/label";
import { Input } from "@/shadcn/ui/input";
import { Button } from "@/shadcn/ui/button";
</script>

<template>
    <form class="space-y-4">
        <div class="grid gap-2">
            <Label for="email">Email</Label>
            <Input
                id="email"
                type="email"
                placeholder="enter your email company"
                required
            />
        </div>
        <div class="grid gap-2">
            <div class="flex items-center">
                <Label for="password">Password</Label>
                <a
                    href="/forgot-password"
                    class="ml-auto inline-block text-sm underline"
                >
                    Forgot your password?
                </a>
            </div>
            <Input id="password" type="password" placeholder="enter password min 8 character" autocomplete="current-password" required/>
        </div>
        <Button type="submit" class="w-full">
            Sign In
        </Button>
    </form>
</template>

<style scoped>

</style>
