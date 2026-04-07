<script setup>
import { ref } from "vue";
import { RouterView, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import AppSidebar from "./components/ui/AppSidebar.vue";
import AppHeader from "./components/ui/AppHeader.vue";

const authStore = useAuthStore();
const router = useRouter();
const sidebarOpen = ref(false);

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

  <div v-else class="flex min-h-screen bg-slate-50 overflow-x-hidden">
    <AppSidebar
      :open="sidebarOpen"
      @logout="handleLogout"
      @close="sidebarOpen = false"
    />

    <main class="flex-1 min-w-0 p-4 sm:p-8 lg:ml-64">
      <AppHeader
        :user="authStore.user"
        @toggle-sidebar="sidebarOpen = !sidebarOpen"
      />
      <Suspense>
        <RouterView />
      </Suspense>
    </main>
  </div>
</template>

<style>
.v-enter-active,
.v-leave-active {
  transition: opacity 0.3s ease;
}
.v-enter-from,
.v-leave-to {
  opacity: 0;
}
</style>
