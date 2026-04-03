<script setup>
import { RouterView, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import AppSidebar from "./components/ui/AppSidebar.vue";
import AppHeader from "./components/ui/AppHeader.vue";

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
    <AppSidebar @logout="handleLogout" />

    <main class="ml-64 flex-1 p-8">
      <AppHeader :user="authStore.user" />
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
