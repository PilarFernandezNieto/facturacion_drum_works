<script setup>
import { useDescargarPDF } from "@/composables/useFacturas";

defineProps({
  factura: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["toggle-estado", "eliminar"]);

const { descargarPDF, descargando } = useDescargarPDF();

function formatearFecha(fecha) {
  return new Date(fecha).toLocaleDateString("es-ES", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}
</script>

<template>
  <!-- DESKTOP: grid de 12 columnas alineado con la cabecera               -->
  <!-- MÓVIL:   flex column con dos filas internas                         -->
  <div
    class="border-b border-slate-100 hover:bg-slate-50/50 transition px-4 py-4 flex flex-col gap-2 md:grid md:grid-cols-12 md:gap-2 md:items-center md:py-3"
  >
    <!-- Código · col-span-2 -->
    <div class="md:col-span-2">
      <span class="font-mono font-bold text-slate-900 text-sm">{{
        factura.codigo
      }}</span>
    </div>

    <!-- Cliente / Concepto · col-span-4 -->
    <div class="md:col-span-4 min-w-0">
      <div class="font-medium text-slate-800 truncate">
        {{ factura.cliente?.nombre }}
      </div>
      <div class="text-xs text-slate-400 truncate">{{ factura.concepto }}</div>
    </div>

    <!-- Fecha · col-span-2 -->
    <div class="md:col-span-2 text-sm text-slate-500">
      {{ formatearFecha(factura.fecha_emision) }}
    </div>

    <!-- Monto · col-span-2 -->
    <div class="md:col-span-2">
      <span class="font-bold text-slate-900">{{ factura.monto }}€</span>
      <span
        v-if="factura.iva_monto > 0"
        class="block text-[10px] text-slate-400"
        >IVA incl.</span
      >
    </div>

    <!-- Estado · col-span-1 -->
    <div class="md:col-span-1">
      <span
        @click="emit('toggle-estado', factura)"
        :class="
          factura.estado === 'pendiente'
            ? 'bg-amber-100 text-amber-700'
            : 'bg-emerald-100 text-emerald-700'
        "
        class="inline-block px-2 py-1 rounded-full text-xs font-bold cursor-pointer hover:opacity-80 transition whitespace-nowrap"
      >
        {{ factura.estado === "pendiente" ? "PDTE" : "PAGADA" }}
      </span>
    </div>

    <!-- Acciones · col-span-1 -->
    <div class="md:col-span-1 flex items-center justify-end gap-1">
      <!-- <a
        target="_blank"
        class="text-purple-600 hover:bg-purple-100 px-2 py-1 rounded border border-principal/20 text-xs font-bold transition"
      >
        PDF
      </a> -->
      <button
        @click="descargarPDF(factura.id, factura.codigo)"
        :disabled="descargando"
        class="text-purple-600 hover:bg-purple-100 px-2 py-1 rounded border border-purple-600/20 text-xs font-bold transition disabled:opacity-50"
      >
        {{ descargando ? "..." : "PDF" }}
      </button>
      <button
        @click="emit('eliminar', factura.id)"
        class="text-red-400 hover:text-red-600 transition p-1 rounded hover:bg-red-50"
        title="Eliminar factura"
      >
        <svg
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M4 6H20M16 6L15.7294 5.18807C15.4671 4.40125 15.3359 4.00784 15.0927 3.71698C14.8779 3.46013 14.6021 3.26132 14.2905 3.13878C13.9376 3 13.523 3 12.6936 3H11.3064C10.477 3 10.0624 3 9.70951 3.13878C9.39792 3.26132 9.12208 3.46013 8.90729 3.71698C8.66405 4.00784 8.53292 4.40125 8.27064 5.18807L8 6M18 6V16.2C18 17.8802 18 18.7202 17.673 19.362C17.3854 19.9265 16.9265 20.3854 16.362 20.673C15.7202 21 14.8802 21 13.2 21H10.8C9.11984 21 8.27976 21 7.63803 20.673C7.07354 20.3854 6.6146 19.9265 6.32698 19.362C6 18.7202 6 17.8802 6 16.2V6M14 10V17M10 10V17"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </button>
    </div>
  </div>
</template>
