<script setup>
import { reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import InputError from "@/components/InputError.vue";
import PrimaryButton from "@/components/buttons/PrimaryButton.vue";

const authStore = useAuthStore();
const router = useRouter();

const form = reactive({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});

const processing = ref(false);
const errors = ref({});

const manejarRegistro = async () =>
  await authStore.register(processing, errors, form);
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-100 px-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
      <div class="text-center mb-10">
        <h1>Crear Cuenta</h1>
        <p class="text-slate-500 mt-2">Únete para gestionar tu academia</p>
      </div>

      <form @submit.prevent="manejarRegistro" novalidate class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1"
            >Nombre Completo</label
          >
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
            placeholder="Juan Pérez"
          />
          <InputError class="mt-2" :message="errors.name?.[0]" />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1"
            >Correo Electrónico</label
          >
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
            placeholder="juan@ejemplo.com"
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
            class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
            placeholder="••••••••"
          />
          <InputError class="mt-2" :message="errors.password?.[0]" />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1"
            >Confirmar Contraseña</label
          >
          <input
            v-model="form.password_confirmation"
            type="password"
            required
            class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
            placeholder="••••••••"
          />
        </div>

        <div
          v-if="errors.message"
          class="bg-red-50 text-principal p-3 rounded-lg text-sm border border-red-200"
        >
          {{ errors.message }}
        </div>

        <PrimaryButton type="submit" :disabled="processing" class="w-full">
          {{ processing ? "Registrando..." : "Empezar ahora" }}
        </PrimaryButton>
      </form>

      <div class="mt-8 text-center text-sm text-slate-600">
        ¿Ya tienes cuenta?
        <router-link
          :to="{ name: 'login' }"
          class="text-principal font-semibold hover:underline"
        >
          Inicia sesión
        </router-link>
      </div>
    </div>
  </div>
</template>
