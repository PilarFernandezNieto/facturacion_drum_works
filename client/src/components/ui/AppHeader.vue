<script setup>
import { useRoute } from "vue-router";
import { computed } from "vue";

defineProps({
  user: { type: Object, default: null },
});

const route = useRoute();

// const pageTitle = computed(() => {
//   if (route.name === "dashboard") return "Bienvenido al Panel";
//   return route.name
//     ? route.name.charAt(0).toUpperCase() + route.name.slice(1)
//     : "";
// });
const pageTitle = computed(() => route.meta?.title ?? "");
</script>

<template>
  <header class="mb-8 flex justify-between items-center">
    <h1>{{ pageTitle }}</h1>
    <div class="flex items-center gap-3">
      <span class="text-sm font-medium text-slate-500 italic">{{
        user?.name
      }}</span>
      <div
        class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-principal font-bold uppercase border-2 border-white shadow-sm"
      >
        {{ user?.name?.charAt(0) }}
      </div>
    </div>
  </header>
</template>
