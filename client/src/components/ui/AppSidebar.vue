<script setup>
import ApplicationLogo from "@/components/ApplicationLogo.vue";
import AppNavLink from "../links/AppNavLink.vue";

defineProps({
  open: { type: Boolean, default: false },
});

defineEmits(["logout", "close"]);
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
      <AppNavLink :to="{ name: 'clientes' }" @click="$emit('close')"
        >Clientes</AppNavLink
      >
      <AppNavLink :to="{ name: 'facturas' }" @click="$emit('close')"
        >Facturas</AppNavLink
      >
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
