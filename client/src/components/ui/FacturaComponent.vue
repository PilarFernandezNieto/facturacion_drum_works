<script setup>
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();
const backendUrl = import.meta.env.VITE_APP_BACKEND_URL;

defineProps({
  factura: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["toggle-estado", "eliminar"]);

function formatearFecha(fecha) {
  return new Date(fecha).toLocaleDateString("es-ES", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}
</script>

<template>
  <div
    class="grid grid-cols-12 gap-2 items-center p-4 border-b border-slate-100 hover:bg-slate-50/50 transition"
  >
    <!-- Código -->
    <div class="col-span-12 md:col-span-2">
      <span class="font-mono font-bold text-slate-900">{{
        factura.codigo
      }}</span>
    </div>

    <!-- Cliente y concepto -->
    <div class="col-span-12 md:col-span-4 min-w-0">
      <div class="font-medium text-slate-800">
        {{ factura.cliente?.nombre }}
      </div>
      <div class="text-xs text-slate-400 truncate">{{ factura.concepto }}</div>
    </div>

    <!-- Fecha -->
    <div class="col-span-4 md:col-span-1 text-sm text-slate-500">
      {{ formatearFecha(factura.fecha_emision) }}
    </div>

    <!-- Monto -->
    <div class="col-span-4 md:col-span-2">
      <div class="font-bold text-slate-900 text-center">
        {{ factura.monto }}€
      </div>
      <div v-if="factura.iva_monto > 0" class="text-[10px] text-slate-400">
        IVA incl.
      </div>
    </div>

    <!-- Estado -->
    <div class="col-span-4 sm:col-span-1 flex justify-end md:justify-end">
      <span
        @click="emit('toggle-estado', factura)"
        :class="
          factura.estado === 'pendiente'
            ? 'bg-amber-100 text-amber-700'
            : 'bg-emerald-100 text-emerald-700'
        "
        class="px-3 py-1 rounded-full text-xs font-bold cursor-pointer hover:opacity-80 transition text-center whitespace-nowrap"
      >
        {{ factura.estado.toUpperCase() }}
      </span>
    </div>

    <!-- Acciones -->
    <div
      class="col-span-12 md:col-span-2 flex items-center justify-end mt-4 md:mt-0 gap-3"
    >
      <a
        :href="`${backendUrl}/facturas/${factura.id}/pdf?token=${authStore.token()}`"
        target="_blank"
        class="text-purple-600 hover:bg-purple-100 px-2 py-1 rounded border border-principal/20 text-sm font-bold transition"
      >
        PDF
      </a>
      <button
        @click="emit('eliminar', factura.id)"
        class="text-red-400 hover:text-red-600 transition p-1 rounded hover:bg-red-50"
      >
        <svg
          width="20px"
          height="20px"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M4 6H20M16 6L15.7294 5.18807C15.4671 4.40125 15.3359 4.00784 15.0927 3.71698C14.8779 3.46013 14.6021 3.26132 14.2905 3.13878C13.9376 3 13.523 3 12.6936 3H11.3064C10.477 3 10.0624 3 9.70951 3.13878C9.39792 3.26132 9.12208 3.46013 8.90729 3.71698C8.66405 4.00784 8.53292 4.40125 8.27064 5.18807L8 6M18 6V16.2C18 17.8802 18 18.7202 17.673 19.362C17.3854 19.9265 16.9265 20.3854 16.362 20.673C15.7202 21 14.8802 21 13.2 21H10.8C9.11984 21 8.27976 21 7.63803 20.673C7.07354 20.3854 6.6146 19.9265 6.32698 19.362C6 18.7202 6 17.8802 6 16.2V6M14 10V17M10 10V17"
            stroke="#dc2626"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </button>
    </div>
  </div>
</template>
