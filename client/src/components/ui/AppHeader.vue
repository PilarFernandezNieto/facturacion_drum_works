<script setup>
import { useRoute } from "vue-router";
import { computed } from "vue";

defineProps({
  user: { type: Object, default: null },
});

defineEmits(["toggle-sidebar"]);

const route = useRoute();
const pageTitle = computed(() => route.meta?.title ?? "");
</script>

<template>
  <header class="mb-8 flex justify-between items-center">
    <div class="flex items-center gap-4 min-w-0">
      <!-- Botón hamburguesa solo en móvil -->
      <button
        class="lg:hidden shrink-0 p-2 rounded-lg hover:bg-slate-100 transition text-slate-600"
        aria-label="Abrir menú"
        @click="$emit('toggle-sidebar')"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="w-6 h-6"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 6h16M4 12h16M4 18h16"
          />
        </svg>
      </button>
      <h1>{{ pageTitle }}</h1>
    </div>

    <div class="flex items-center gap-3 shrink-0">
      <span class="hidden sm:block text-sm font-medium text-slate-500 italic">
        {{ user?.name }}
      </span>
      <div
        class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-principal font-bold uppercase border-2 border-white shadow-sm"
      >
        {{ user?.name?.charAt(0) }}
      </div>
    </div>
  </header>
</template>
