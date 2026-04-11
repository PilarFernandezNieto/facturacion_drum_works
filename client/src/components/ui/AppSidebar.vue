<script setup>
import { ref } from "vue";
import { useRoute } from "vue-router";
import ApplicationLogo from "@/components/ApplicationLogo.vue";
import AppNavLink from "../links/AppNavLink.vue";

defineProps({
  open: { type: Boolean, default: false },
});

defineEmits(["logout", "close"]);

const route = useRoute();
const clientesAbierto = ref(
  route.name === "alumnos" || route.name === "bolos",
);
const facturasAbierto = ref(
  route.name === "facturas-clases" || route.name === "facturas-bolos",
);
</script>

<template>
  <!-- Overlay oscuro para móvil cuando el sidebar está abierto -->
  <div
    v-if="open"
    class="fixed inset-0 bg-black/40 z-20 lg:hidden"
    @click="$emit('close')"
  />
  <!-- Sidebar desktop-->
  <aside
    :class="[
      'w-64 bg-white border-r border-slate-200 flex flex-col fixed inset-y-0 left-0 shadow-sm z-30 transition-transform duration-300',
      open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
    ]"
  >
    <div class="p-6 border-b border-slate-100 flex items-center justify-center">
      <ApplicationLogo class="w-20 h-20 fill-current text-gray-500" />
    </div>

    <nav class="flex-1 p-4 space-y-2">
      <AppNavLink
        :to="{ name: 'dashboard' }"
        active-class="bg-principal !text-white shadow-sm shadow-principal-50"
        @click="$emit('close')"
      >
        Inicio
      </AppNavLink>

      <!-- Dropdown Clientes -->
      <div>
        <button
          @click="clientesAbierto = !clientesAbierto"
          :class="[
            'w-full flex items-center justify-between px-4 py-3 rounded-lg text-slate-600 hover:bg-principal-100 hover:text-white transition',
            (route.name === 'alumnos' || route.name === 'bolos') && 'bg-principal-100 text-slate-800 font-semibold',
          ]"
        >
          <span class="font-medium">Clientes</span>
          <svg
            :class="['w-4 h-4 transition-transform duration-200', clientesAbierto ? 'rotate-180' : '']"
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div v-show="clientesAbierto" class="mt-1 space-y-1 pl-3">
          <AppNavLink :to="{ name: 'alumnos' }" @click="$emit('close')">
            Alumnos
          </AppNavLink>
          <AppNavLink :to="{ name: 'bolos' }" @click="$emit('close')">
            Bolos
          </AppNavLink>
        </div>
      </div>

      <!-- Dropdown Facturas -->
      <div>
        <button
          @click="facturasAbierto = !facturasAbierto"
          :class="[
            'w-full flex items-center justify-between px-4 py-3 rounded-lg text-slate-600 hover:bg-principal-100 hover:text-white transition',
            (route.name === 'facturas-clases' || route.name === 'facturas-bolos') && 'bg-principal-100 text-slate-800 font-semibold',
          ]"
        >
          <span class="font-medium">Facturas</span>
          <svg
            :class="['w-4 h-4 transition-transform duration-200', facturasAbierto ? 'rotate-180' : '']"
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div v-show="facturasAbierto" class="mt-1 space-y-1 pl-3">
          <AppNavLink :to="{ name: 'facturas-clases' }" @click="$emit('close')">
            Clases
          </AppNavLink>
          <AppNavLink :to="{ name: 'facturas-bolos' }" @click="$emit('close')">
            Bolos
          </AppNavLink>
        </div>
      </div>
    </nav>

    <div class="p-4 border-t border-slate-100">
      <button
        @click="$emit('logout')"
        class="w-full flex items-center px-4 py-3 rounded-lg text-principal hover:bg-red-50 transition"
      >
        <span class="font-medium">Cerrar Sesión</span>
      </button>
    </div>
  </aside>
</template>
