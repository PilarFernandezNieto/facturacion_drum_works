<script setup>
import { reactive, ref, watchEffect } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import InputError from "@/components/InputError.vue";
import ApplicationLogo from "@/components/ApplicationLogo.vue";
import PrimaryButton from "@/components/buttons/PrimaryButton.vue";

const authStore = useAuthStore();
const router = useRouter();

const form = reactive({
  email: "",
  password: "",
});

const processing = ref(false);
const errors = ref({});
const status = ref(null);

const route = useRoute();

watchEffect(() => {
  if (route.query.reset && route.query.reset?.length > 0) {
    status.value = atob(route.query?.reset);
  } else {
    status.value = null;
  }
});
const handleLogin = async () => await authStore.login(processing, errors, form);
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-100 px-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
      <div class="flex flex-col items-center mb-10">
        <ApplicationLogo class="w-25 h-25 fill-current text-gray-500" />
        <p class="text-slate-500 mt-2">
          Gestiona tus facturas de forma sencilla
        </p>
      </div>

      <form @submit.prevent="handleLogin" novalidate class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1"
            >Correo Electrónico</label
          >
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-principal focus:border-transparent outline-none transition"
            placeholder="ejemplo@academia.com"
          />
          <InputError class="mt-2" :message="errors.email?.[0]" />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1"
            >Contraseña</label
          >
          <input
            v-model="form.password"
            type="password"
            required
            class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-principal focus:border-transparent outline-none transition"
            placeholder="••••••••"
          />
          <InputError class="mt-2" :message="errors.password?.[0]" />
        </div>

        <div
          v-if="errors.message"
          class="bg-red-50 text-principal p-3 rounded-lg text-sm border border-red-200"
        >
          {{ errors.message }}
        </div>

        <PrimaryButton type="submit" :disabled="processing" class="w-full">
          {{ processing ? "Iniciando sesión..." : "Iniciar Sesión" }}
        </PrimaryButton>
      </form>
      <!-- 
      <div class="mt-8 text-center text-sm text-slate-600">
        ¿No tienes cuenta?
        <router-link
          :to="{ name: 'registro' }"
          class="text-principal font-semibold hover:underline"
        >
          Regístrate aquí
        </router-link>
      </div> -->
    </div>
  </div>
</template>
