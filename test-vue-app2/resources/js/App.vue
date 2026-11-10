<template>
    <main>
        <h1>SahilPHP + Vue</h1>
        <button type="button" @click="count++">Count: {{ count }}</button>
    </main>
</template>

<script setup>
import { ref } from 'vue';

const count = ref(0);
</script>
