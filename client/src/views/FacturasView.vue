<script setup>
import { ref, computed } from "vue";
import { useRoute } from "vue-router";

import {
  useFacturas,
  useGenerarMasiva,
  useEliminarFactura,
  useCambiarEstado,
  useDescargarPDFMasivo,
  nombreMes,
} from "@/composables/useFacturas";
import { usePaginacion } from "@/composables/usePaginacion";

import { confirmDialog, notifyError, toast } from "@/utils/swal";
import ScreenLoader from "@/components/ui/ScreenLoader.vue";
import ModalFacturaBolo from "@/components/ui/ModalFacturaBolo.vue";
import FacturaComponent from "@/components/ui/FacturaComponent.vue";
import PaginacionComponent from "@/components/ui/PaginacionComponent.vue";

const route = useRoute();
const { facturas, isLoading } = useFacturas();
const { mutateAsync: generarMasivaFn, isPending: generando } =
  useGenerarMasiva();
const { mutateAsync: eliminarFn } = useEliminarFactura();
const { mutateAsync: cambiarEstadoFn } = useCambiarEstado();
const { descargarMasivo, descargando: descargandoZip } =
  useDescargarPDFMasivo();

const serie = computed(() => route.meta.serie);
const titulo = computed(() =>
  serie.value === "C" ? "Facturas de Clases" : "Facturas de Bolos",
);
const subtitulo = computed(() =>
  serie.value === "C"
    ? "Ingresos por cuotas mensuales de alumnos (Serie C)."
    : "Ingresos por conciertos y actuaciones (Serie B).",
);

const mostrarModalBolo = ref(false);

// Filtros de UI
const filtroMes = ref("");
const filtroEstado = ref("");

const mesesDisponibles = computed(() => {
  const meses = new Set();
  (facturas.value ?? [])
    .filter((f) => f.serie === serie.value)
    .forEach((f) => {
      const d = new Date(f.fecha_emision);
      meses.add(
        `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`,
      );
    });
  return Array.from(meses).sort().reverse();
});

const facturasFiltradas = computed(() => {
  return (facturas.value ?? []).filter((f) => {
    if (f.serie !== serie.value) return false;
    const matchEstado = !filtroEstado.value || f.estado === filtroEstado.value;
    const d = new Date(f.fecha_emision);
    const mesAnio = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
    const matchMes = !filtroMes.value || mesAnio === filtroMes.value;
    return matchEstado && matchMes;
  });
});

const { paginado, pagina, totalPaginas, paginas, desde, hasta, total, irA } =
  usePaginacion(facturasFiltradas, 10);

async function descargarZip() {
  if (!facturasFiltradas.value.length) return;
  const etiqueta = filtroMes.value ? nombreMes(filtroMes.value) : "todas";
  const nombreZip = `facturas-${serie.value === "C" ? "clases" : "bolos"}-${etiqueta}.zip`;
  try {
    await descargarMasivo(facturasFiltradas.value, nombreZip);
  } catch {
    notifyError("Error", "No se pudieron descargar los PDFs.");
  }
}

async function generarMasiva() {
  const result = await confirmDialog(
    "Generar Facturas de Clases",
    "Se generarán las facturas del mes actual para todos los alumnos. ¿Continuar?",
    "info",
  );
  if (!result.isConfirmed) return;
  try {
    const r = await generarMasivaFn();
    toast(r.mensaje);
  } catch {
    notifyError("Error", "No se pudieron generar las facturas.");
  }
}

async function eliminarFactura(id) {
  const result = await confirmDialog(
    "¿Eliminar factura?",
    "¿Estás seguro de eliminar esta factura permanentemente?",
    "warning",
  );
  if (!result.isConfirmed) return;
  try {
    await eliminarFn(id);
    toast("Factura eliminada");
  } catch {
    notifyError("Error", "No se pudo eliminar.");
  }
}

async function toggleEstado(factura) {
  const nuevoEstado = factura.estado === "pendiente" ? "pagada" : "pendiente";
  try {
    await cambiarEstadoFn({ id: factura.id, nuevoEstado });
    toast(`Factura marcada como ${nuevoEstado}`);
  } catch {
    notifyError("Error", "No se pudo cambiar el estado.");
  }
}
</script>

<template>
  <div v-if="isLoading">
    <ScreenLoader />
  </div>

  <div v-else class="space-y-6 overflow-x-hidden">
    <!-- Cabecera -->
    <div
      class="flex flex-col gap-4 md:flex-row md:justify-between md:items-center"
    >
      <div>
        <h2>{{ titulo }}</h2>
        <p class="text-sm text-slate-500">{{ subtitulo }}</p>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row">
        <!-- Botón Serie C: generación masiva -->
        <button
          v-if="serie === 'C'"
          @click="generarMasiva"
          :disabled="generando"
          class="bg-principal hover:bg-principal-hover text-white px-4 py-2 rounded-lg font-bold transition shadow-md shadow-principal-100 disabled:opacity-50 flex items-center justify-center gap-2 text-sm"
        >
          <span v-if="!generando">⚡ Generar Clases (Mes)</span>
          <span v-else>Generando...</span>
        </button>

        <!-- Botón Serie B: nueva factura bolo -->
        <button
          v-if="serie === 'B'"
          @click="mostrarModalBolo = true"
          class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-bold transition shadow-md shadow-purple-200 flex items-center justify-center gap-2 text-sm"
        >
          🎸 Nueva Factura Bolo
        </button>

        <!-- Descarga masiva: siempre visible si hay facturas -->
        <button
          @click="descargarZip"
          :disabled="descargandoZip || !facturasFiltradas.length"
          class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg font-bold transition disabled:opacity-40 flex items-center justify-center gap-2 text-sm"
        >
          <span v-if="!descargandoZip">⬇ Descargar ZIP</span>
          <span v-else>Descargando...</span>
        </button>
      </div>
    </div>

    <!-- Filtros -->
    <div
      class="bg-white p-4 rounded-xl border border-slate-200 flex flex-wrap gap-6 items-center shadow-sm"
    >
      <div class="flex items-center gap-3">
        <label
          class="text-[10px] font-black text-slate-400 uppercase tracking-widest"
        >
          Filtrar por Mes
        </label>
        <select
          v-model="filtroMes"
          class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 font-medium cursor-pointer"
        >
          <option value="">Todos los meses</option>
          <option v-for="m in mesesDisponibles" :key="m" :value="m">
            {{ nombreMes(m) }}
          </option>
        </select>
      </div>

      <div class="flex items-center gap-3 border-l border-slate-100 pl-6">
        <label
          class="text-[10px] font-black text-slate-400 uppercase tracking-widest"
        >
          Estado de Pago
        </label>
        <select
          v-model="filtroEstado"
          class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 font-bold cursor-pointer"
        >
          <option value="">Cualquier estado</option>
          <option value="pendiente">PENDIENTE</option>
          <option value="pagada">PAGADA</option>
        </select>
      </div>

      <button
        v-if="filtroMes || filtroEstado"
        @click="
          filtroMes = '';
          filtroEstado = '';
        "
        class="ml-auto text-xs font-bold text-red-500 hover:text-principal flex items-center gap-1 transition"
      >
        ✕ Limpiar filtros
      </button>
    </div>

    <!-- Lista de facturas -->
    <div
      class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden"
    >
      <div
        class="hidden md:grid md:grid-cols-12 md:gap-2 px-4 py-3 bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider"
      >
        <div class="col-span-2">Código</div>
        <div class="col-span-4">Cliente / Concepto</div>
        <div class="col-span-2">Fecha</div>
        <div class="col-span-2">Monto</div>
        <div class="col-span-1">Estado</div>
        <div class="col-span-1 text-right">Acc.</div>
      </div>

      <FacturaComponent
        v-for="factura in paginado"
        :key="factura.id"
        :factura="factura"
        @toggle-estado="toggleEstado"
        @eliminar="eliminarFactura"
      />

      <div v-if="!total" class="px-6 py-12 text-center text-slate-400">
        No hay facturas que coincidan con los filtros aplicados.
      </div>

      <PaginacionComponent
        :pagina="pagina"
        :total-paginas="totalPaginas"
        :paginas="paginas"
        :desde="desde"
        :hasta="hasta"
        :total="total"
        @ir="irA"
      />
    </div>

    <!-- Modal factura bolo (solo Serie B) -->
    <ModalFacturaBolo
      v-if="mostrarModalBolo"
      @close="mostrarModalBolo = false"
    />
  </div>
</template>
