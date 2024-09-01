<script setup lang="ts">

</script>

<template>
<div class="mx-auto my-auto">
    <h1>Email Verification</h1>
    <p>Please verify your email address by clicking on the link we just emailed to you. If you didn’t receive the email, we will gladly send you another.</p>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit">Resend Verification Email</button>
    </form>
</div>
</template>

<style scoped>

</style>
