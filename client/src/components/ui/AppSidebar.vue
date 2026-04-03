<script setup>
import ApplicationLogo from "@/components/ApplicationLogo.vue";
import AppNavLink from "../links/AppNavLink.vue";

defineEmits(["logout"]);
</script>

<template>
  <aside
    class="w-64 bg-white border-r border-slate-200 flex flex-col fixed inset-y-0 left-0 shadow-sm z-10"
  >
    <div class="p-6 border-b border-slate-100 flex items-center justify-center">
      <ApplicationLogo class="w-20 h-20 fill-current text-gray-500" />
    </div>

    <nav class="flex-1 p-4 space-y-2">
      <AppNavLink
        :to="{ name: 'dashboard' }"
        active-class="bg-principal !text-white shadow-sm shadow-principal-50"
      >
        Inicio
      </AppNavLink>
      <AppNavLink :to="{ name: 'clientes' }">Clientes</AppNavLink>
      <AppNavLink :to="{ name: 'facturas' }">Facturas</AppNavLink>
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
