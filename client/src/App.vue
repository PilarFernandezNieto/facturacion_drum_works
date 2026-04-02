<script setup>
import { RouterView, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import ApplicationLogo from "@/components/ApplicationLogo.vue";

const authStore = useAuthStore();
const router = useRouter();

const handleLogout = () => {
  authStore.logout();
  router.push({ name: "login" });
};
</script>

<template>
  <div v-if="!authStore.isLoggedIn">
    <Suspense>
      <RouterView />
    </Suspense>
  </div>

  <div v-else class="flex min-h-screen bg-slate-50">
    <aside
      class="w-64 bg-white border-r border-slate-200 flex flex-col fixed inset-y-0 left-0 shadow-sm z-10"
    >
      <div
        class="p-6 border-b border-slate-100 flex items-center justify-center"
      >
        <ApplicationLogo class="w-20 h-20 fill-current text-gray-500" />
      </div>

      <nav class="flex-1 p-4 space-y-2">
        <router-link
          :to="{ name: 'dashboard' }"
          class="flex items-center px-4 py-3 rounded-lg hover:bg-principal-50 hover:text-slate-900 transition group"
          active-class="bg-principal !text-white shadow-sm shadow-principal-50"
        >
          <span class="font-medium">Inicio</span>
        </router-link>

        <router-link
          :to="{ name: 'clientes' }"
          class="flex items-center px-4 py-3 rounded-lg text-slate-600 hover:bg-blue-50 hover:text-blue-600 transition"
          active-class="bg-blue-600 !text-white shadow-md shadow-blue-200"
        >
          <span class="font-medium">Clientes</span>
        </router-link>

        <router-link
          :to="{ name: 'facturas' }"
          class="flex items-center px-4 py-3 rounded-lg text-slate-600 hover:bg-blue-50 hover:text-blue-600 transition"
          active-class="bg-blue-600 !text-white shadow-md shadow-blue-200"
        >
          <span class="font-medium">Facturas</span>
        </router-link>
      </nav>

      <div class="p-4 border-t border-slate-100">
        <button
          @click="handleLogout"
          class="w-full flex items-center px-4 py-3 rounded-lg text-red-500 hover:bg-red-50 transition"
        >
          <span class="font-medium">Cerrar Sesión</span>
        </button>
      </div>
    </aside>

    <!-- Contenido Principal -->
    <main class="ml-64 flex-1 p-8">
      <header class="mb-8 flex justify-between items-center">
        <h1>
          {{
            $route.name === "dashboard"
              ? "Bienvenido al Panel"
              : $route.name
                ? $route.name.charAt(0).toUpperCase() + $route.name.slice(1)
                : ""
          }}
        </h1>
        <div class="flex items-center gap-3">
          <span class="text-sm font-medium text-slate-500 italic">{{
            authStore.user?.name
          }}</span>
          <div
            class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold uppercase border-2 border-white shadow-sm"
          >
            {{ authStore.user?.name?.charAt(0) }}
          </div>
        </div>
      </header>
      <Suspense>
        <RouterView />
      </Suspense>
    </main>
  </div>
</template>

<style>
/* Estilos globales rápidos */
.v-enter-active,
.v-leave-active {
  transition: opacity 0.3s ease;
}
.v-enter-from,
.v-leave-to {
  opacity: 0;
}
</style>
