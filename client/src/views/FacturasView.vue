<script setup>
import { ref, onMounted } from "vue";
import { useFacturaStore } from "@/stores/factura";
import { useClienteStore } from "@/stores/cliente";
import { storeToRefs } from "pinia";

import { confirmDialog, notifyError, toast } from "@/utils/swal";
import ScreenLoader from "@/components/ui/ScreenLoader.vue";
import ModalFacturaBolo from "@/components/ui/ModalFacturaBolo.vue";
import FacturaComponent from "@/components/ui/FacturaComponent.vue";

const clienteStore = useClienteStore();
const facturaStore = useFacturaStore();

const {
  isLoading,
  generando,
  filtroMes,
  filtroEstado,
  mesesDisponibles,
  facturasFiltradas,
} = storeToRefs(facturaStore);

const mostrarModalBolo = ref(false);

async function cargarDatos() {
  try {
    await Promise.all([
      facturaStore.cargarFacturas(),
      clienteStore.cargarClientes(),
    ]);
  } catch (error) {
    console.error("Error al inicializar datos:", error);
  }
}

async function generarMasiva() {
  const result = await confirmDialog(
    "Generar Facturas",
    "Se van a generar las facturas de todos los alumnos de CLASES para el mes actual. ¿Continuar?",
    "info",
  );
  if (!result.isConfirmed) return;

  try {
    const r = await facturaStore.generarMasiva();
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
    await facturaStore.eliminarFactura(id);
    toast("Factura eliminada");
  } catch {
    notifyError("Error", "No se pudo eliminar.");
  }
}

async function toggleEstado(factura) {
  try {
    const { nuevoEstado } = await facturaStore.cambiarEstado(
      factura.id,
      factura.estado,
    );
    toast(`Factura marcada como ${nuevoEstado}`);
  } catch {
    notifyError("Error", "No se pudo cambiar el estado.");
  }
}
const cerrarModal = async () => {
  mostrarModalBolo.value = false;
  await facturaStore.cargarFacturas();
};

onMounted(cargarDatos);
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
        <h2>Control de Facturación</h2>
        <p class="text-sm text-slate-500">
          Gestiona tus ingresos de clases (C) y bolos (B).
        </p>
      </div>
      <div class="flex flex-col gap-3 sm:flex-row">
        <button
          @click="mostrarModalBolo = true"
          class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-bold transition shadow-md shadow-purple-200 flex items-center justify-center gap-2 text-sm"
        >
          🎸 Nueva Factura Bolo
        </button>
        <button
          @click="generarMasiva"
          :disabled="generando"
          class="bg-principal hover:bg-principal-hover text-white px-4 py-2 rounded-lg font-bold transition shadow-md shadow-principal-100 disabled:opacity-50 flex items-center justify-center gap-2 text-sm"
        >
          <span v-if="!generando">⚡ Generar Clases (Mes)</span>
          <span v-else>Generando...</span>
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
            {{ facturaStore.nombreMes(m) }}
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
          <option value="pendiente" class="text-amber-600 font-bold">
            PENDIENTE
          </option>
          <option value="pagada" class="text-emerald-600 font-bold">
            PAGADA
          </option>
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
      <!--
        Cabecera desktop. Columnas en el mismo orden y tamaño que FacturaRow:
          col-span-2  Código
          col-span-4  Cliente / Concepto
          col-span-2  Fecha
          col-span-2  Monto
          col-span-1  Estado
          col-span-1  Acciones
      -->
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

      <!-- Filas -->
      <FacturaComponent
        v-for="factura in facturasFiltradas"
        :key="factura.id"
        :factura="factura"
        @toggle-estado="toggleEstado"
        @eliminar="eliminarFactura"
      />

      <!-- Sin resultados -->
      <div
        v-if="!facturasFiltradas.length"
        class="px-6 py-12 text-center text-slate-400"
      >
        No hay facturas que coincidan con los filtros aplicados.
      </div>
    </div>

    <!-- Modal factura bolo -->
    <ModalFacturaBolo v-if="mostrarModalBolo" @close="cerrarModal" />
  </div>
</template>
