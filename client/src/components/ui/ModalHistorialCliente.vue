<script setup>
import { computed } from "vue";
import {
  useFacturas,
  useDescargarPDF,
  useDescargarPDFMasivo,
} from "@/composables/useFacturas";

const props = defineProps({
  cliente: { type: Object, required: true },
});

const emit = defineEmits(["close"]);

const { facturas } = useFacturas();
const { descargarPDF, descargando: descargandoUno } = useDescargarPDF();
const { descargarMasivo, descargando: descargandoZip } =
  useDescargarPDFMasivo();

const historial = computed(() =>
  (facturas.value ?? [])
    .filter((f) => f.cliente_id === props.cliente.id)
    .sort((a, b) => new Date(b.fecha_emision) - new Date(a.fecha_emision)),
);

const totalFacturado = computed(() =>
  historial.value.reduce((s, f) => s + parseFloat(f.monto || 0), 0),
);

const totalPendiente = computed(() =>
  historial.value
    .filter((f) => f.estado === "pendiente")
    .reduce((s, f) => s + parseFloat(f.monto || 0), 0),
);

function formatearFecha(fecha) {
  return new Date(fecha).toLocaleDateString("es-ES", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}

async function descargarTodo() {
  await descargarMasivo(
    historial.value,
    `historial-${props.cliente.nombre.replace(/\s+/g, "-").toLowerCase()}.zip`,
  );
}
</script>

<template>
  <div
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4 z-50"
  >
    <div
      class="bg-white p-4 rounded-t-2xl sm:rounded-2xl w-full sm:w-[95%] lg:w-[65%] shadow-2xl animate-in fade-in slide-in-from-bottom-4 sm:zoom-in duration-200 max-h-[90dvh] flex flex-col"
    >
      <!-- Cabecera -->
      <div
        class="p-5 border-b border-slate-100 flex justify-between items-start bg-slate-50 rounded-t-2xl shrink-0"
      >
        <div>
          <h3 class="text-xl font-bold text-slate-800">
            {{ cliente.nombre }}
          </h3>
          <p class="text-sm text-slate-500 mt-0.5">
            Historial de facturas · {{ historial.length }} registros
          </p>
        </div>
        <button
          @click="$emit('close')"
          class="text-slate-400 hover:text-slate-600 w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-200 transition"
        >
          ✕
        </button>
      </div>

      <!-- Resumen -->
      <div
        class="px-5 py-3 bg-white border-b border-slate-100 flex gap-6 shrink-0"
      >
        <div>
          <p
            class="text-[10px] font-black text-slate-400 uppercase tracking-widest"
          >
            Total facturado
          </p>
          <p class="text-xl font-bold text-slate-800">
            {{ totalFacturado.toFixed(2) }} €
          </p>
        </div>
        <div class="border-l border-slate-100 pl-6">
          <p
            class="text-[10px] font-black text-slate-400 uppercase tracking-widest"
          >
            Pendiente de cobro
          </p>
          <p
            class="text-xl font-bold"
            :class="totalPendiente > 0 ? 'text-amber-600' : 'text-emerald-600'"
          >
            {{ totalPendiente.toFixed(2) }} €
          </p>
        </div>
        <div class="ml-auto self-center">
          <button
            @click="descargarTodo"
            :disabled="descargandoZip || !historial.length"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 rounded-lg text-sm font-bold transition disabled:opacity-40 flex items-center gap-2"
          >
            <span v-if="!descargandoZip">⬇ Descargar todo</span>
            <span v-else>Descargando...</span>
          </button>
        </div>
      </div>

      <!-- Lista -->
      <div class="overflow-y-auto flex-1">
        <!-- Cabecera tabla (desktop) -->
        <div
          class="hidden md:grid md:grid-cols-12 px-5 py-2 bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider sticky top-0"
        >
          <div class="col-span-2">Código</div>
          <div class="col-span-4">Concepto</div>
          <div class="col-span-2">Fecha</div>
          <div class="col-span-2">Importe</div>
          <div class="col-span-1">Estado</div>
          <div class="col-span-1 text-right">PDF</div>
        </div>

        <!-- Filas -->
        <div
          v-for="f in historial"
          :key="f.id"
          class="border-b border-slate-100 hover:bg-slate-50/50 transition px-5 py-3 flex flex-col gap-1 md:grid md:grid-cols-12 md:items-center"
        >
          <div class="md:col-span-2">
            <span class="font-mono font-bold text-slate-800 text-sm">{{
              f.codigo
            }}</span>
          </div>
          <div class="md:col-span-4 text-sm text-slate-600 truncate">
            {{ f.concepto }}
          </div>
          <div class="md:col-span-2 text-sm text-slate-500">
            {{ formatearFecha(f.fecha_emision) }}
          </div>
          <div class="md:col-span-2 font-bold text-slate-900">
            {{ parseFloat(f.monto).toFixed(2) }} €
          </div>
          <div class="md:col-span-1">
            <span
              :class="
                f.estado === 'pendiente'
                  ? 'bg-amber-100 text-amber-700'
                  : 'bg-emerald-100 text-emerald-700'
              "
              class="inline-block px-2 py-0.5 rounded-full text-xs font-bold whitespace-nowrap"
            >
              {{ f.estado === "pendiente" ? "PDTE" : "PAGADA" }}
            </span>
          </div>
          <div class="md:col-span-1 flex justify-end">
            <button
              @click="descargarPDF(f.id, f.codigo)"
              :disabled="descargandoUno"
              class="text-purple-600 hover:bg-purple-100 px-2 py-1 rounded border border-purple-600/20 text-xs font-bold transition disabled:opacity-50"
            >
              PDF
            </button>
          </div>
        </div>

        <div
          v-if="!historial.length"
          class="px-5 py-12 text-center text-slate-400"
        >
          Este cliente no tiene facturas todavía.
        </div>
      </div>
    </div>
  </div>
</template>
