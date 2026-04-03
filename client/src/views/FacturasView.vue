<script setup>
import { ref, onMounted, reactive, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useFacturaStore } from "@/stores/factura";
import { storeToRefs } from "pinia";
import { useClienteStore } from "@/stores/cliente";

import { confirmDialog, notifyError, toast } from "@/utils/swal";
import ScreenLoader from "@/components/ui/ScreenLoader.vue";

const authStore = useAuthStore();
const clienteStore = useClienteStore();
const facturaStore = useFacturaStore();
const backendUrl = import.meta.env.VITE_APP_BACKEND_URL;

const { clientes } = storeToRefs(clienteStore);
const {
  isLoading,
  generando,
  filtroMes,
  filtroEstado,
  mesesDisponibles,
  facturasFiltradas,
} = storeToRefs(facturaStore);

const mostrarModalBolo = ref(false);

const formularioBolo = reactive({
  cliente_id: "",
  subtotal: 0,
  iva_porcentaje: 10,
  irpf_porcentaje: 15,
  concepto: "",
  fecha_evento: "",
  fecha_emision: new Date().toISOString().split("T")[0],
});

const totalBolo = computed(() => {
  const base = parseFloat(formularioBolo.subtotal) || 0;
  const iva = (base * formularioBolo.iva_porcentaje) / 100;
  const irpf = (base * formularioBolo.irpf_porcentaje) / 100;
  return (base + iva - irpf).toFixed(2);
});

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
  } catch (error) {
    notifyError("Error", "No se pudieron generar las facturas.");
  }
}

async function guardarBolo() {
  try {
    await facturaStore.guardarBolo(formularioBolo);
    toast("Factura B generada con éxito");
    mostrarModalBolo.value = false;
    // Limpiar formulario
    formularioBolo.cliente_id = "";
    formularioBolo.subtotal = 0;
    formularioBolo.concepto = "";
    formularioBolo.fecha_evento = "";
  } catch (error) {
    notifyError("Error", "Error al guardar factura de bolo.");
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
  } catch (e) {
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
  } catch (error) {
    notifyError("Error", "No se pudo cambiar el estado.");
  }
}

function formatearFecha(fecha) {
  return new Date(fecha).toLocaleDateString("es-ES", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}

onMounted(cargarDatos);
</script>

<template>
  <div v-if="isLoading">
    <ScreenLoader />
  </div>

  <div v-else class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h2>Control de Facturación</h2>
        <p class="text-sm text-slate-500">
          Gestiona tus ingresos de clases (C) y bolos (B).
        </p>
      </div>
      <div class="flex gap-3">
        <button
          @click="mostrarModalBolo = true"
          class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-lg font-bold transition shadow-lg shadow-purple-100 flex items-center gap-2"
        >
          🎸 Nueva Factura Bolo
        </button>
        <button
          @click="generarMasiva"
          :disabled="generando"
          class="bg-principal hover:bg-principal-hover text-white px-5 py-2 rounded-lg font-bold transition shadow-lg shadow-principal-100 disabled:opacity-50 flex items-center gap-2"
        >
          <span v-if="!generando">⚡ Generar Facturas Clases (Mes)</span>
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
          >Filtrar por Mes</label
        >

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
          >Estado de Pago</label
        >
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

    <!-- Lista de Facturas -->
    <div
      class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden"
    >
      <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50 border-b border-slate-100">
          <tr>
            <th class="px-6 py-4 font-semibold text-slate-700">№ Factura</th>
            <th class="px-6 py-4 font-semibold text-slate-700">
              Cliente / Alumno
            </th>
            <th class="px-6 py-4 font-semibold text-slate-700">Fecha</th>
            <th class="px-6 py-4 font-semibold text-slate-700 text-right">
              Total
            </th>
            <th class="px-6 py-4 font-semibold text-slate-700 text-center">
              Estado
            </th>
            <th class="px-6 py-4 font-semibold text-slate-700 text-center">
              Acciones
            </th>
          </tr>
        </thead>
        <tbody v-if="facturasFiltradas.length">
          <tr
            v-for="factura in facturasFiltradas"
            :key="factura.id"
            class="border-b border-slate-50 hover:bg-slate-50/50 transition"
          >
            <td class="px-6 py-4">
              <span class="font-mono font-bold text-slate-900">{{
                factura.codigo
              }}</span>
            </td>
            <td class="px-6 py-4">
              <div class="font-medium text-slate-800">
                {{ factura.cliente?.nombre }}
              </div>
              <div class="text-xs text-slate-400 truncate max-w-60">
                {{ factura.concepto }}
              </div>
            </td>
            <td class="px-6 py-4 text-slate-600">
              {{ formatearFecha(factura.fecha_emision) }}
            </td>
            <td class="px-6 py-4 text-right">
              <div class="font-bold text-slate-900">{{ factura.monto }}€</div>
              <div
                v-if="factura.iva_monto > 0"
                class="text-[10px] text-slate-400"
              >
                IVA incl.
              </div>
            </td>
            <td class="px-6 py-4 text-center">
              <span
                @click="toggleEstado(factura)"
                :class="
                  factura.estado === 'pendiente'
                    ? 'bg-amber-100 text-amber-700'
                    : 'bg-emerald-100 text-emerald-700'
                "
                class="px-3 py-1 rounded-full text-xs font-bold cursor-pointer hover:opacity-80 transition"
              >
                {{ factura.estado.toUpperCase() }}
              </span>
            </td>
            <td class="px-6 py-4 text-center">
              <div class="flex justify-center items-center gap-2">
                <a
                  :href="`${backendUrl}/facturas/${factura.id}/pdf?token=${authStore.token()}`"
                  target="_blank"
                  class="text-blue-600 hover:bg-blue-50 px-2 py-1 rounded border border-blue-200 text-sm font-bold transition"
                >
                  PDF
                </a>
                <button
                  @click="eliminarFactura(factura.id)"
                  class="text-red-400 hover:text-principal text-xs"
                >
                  <svg
                    width="30px"
                    height="30px"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    stroke="#dc2626"
                  >
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g
                      id="SVGRepo_tracerCarrier"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    ></g>
                    <g id="SVGRepo_iconCarrier">
                      <path
                        d="M4 6H20M16 6L15.7294 5.18807C15.4671 4.40125 15.3359 4.00784 15.0927 3.71698C14.8779 3.46013 14.6021 3.26132 14.2905 3.13878C13.9376 3 13.523 3 12.6936 3H11.3064C10.477 3 10.0624 3 9.70951 3.13878C9.39792 3.26132 9.12208 3.46013 8.90729 3.71698C8.66405 4.00784 8.53292 4.40125 8.27064 5.18807L8 6M18 6V16.2C18 17.8802 18 18.7202 17.673 19.362C17.3854 19.9265 16.9265 20.3854 16.362 20.673C15.7202 21 14.8802 21 13.2 21H10.8C9.11984 21 8.27976 21 7.63803 20.673C7.07354 20.3854 6.6146 19.9265 6.32698 19.362C6 18.7202 6 17.8802 6 16.2V6M14 10V17M10 10V17"
                        stroke="#dc2626"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      ></path>
                    </g>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
        <tbody v-else>
          <tr>
            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
              No hay facturas en el historial.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Factura Bolo -->
    <div
      v-if="mostrarModalBolo"
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50"
    >
      <div
        class="bg-white rounded-2xl w-full max-w-xl shadow-2xl animate-in zoom-in duration-200"
      >
        <div
          class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl"
        >
          <h3 class="text-xl font-bold text-slate-800">
            Nueva Factura de Bolo (Serie B)
          </h3>
          <button
            @click="mostrarModalBolo = false"
            class="text-slate-400 hover:text-slate-600 text-xl"
          >
            ✕
          </button>
        </div>

        <form @submit.prevent="guardarBolo" novalidate class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Cliente (Entidad/Organizador)</label
              >
              <select
                v-model="formularioBolo.cliente_id"
                required
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-purple-500"
              >
                <option value="" disabled>Selecciona un cliente...</option>
                <option
                  v-for="c in clientes.filter((e) => e.tipo === 'bolo')"
                  :key="c.id"
                  :value="c.id"
                >
                  {{ c.nombre }} ({{ c.nif_cif }})
                </option>
              </select>
              <p
                v-if="!clientes.some((e) => e.tipo === 'bolo')"
                class="text-xs text-amber-600 mt-1 italic"
              >
                * Primero debes crear un cliente de tipo "Bolo" en la sección
                Clientes.
              </p>
            </div>

            <div class="col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Concepto del Concierto</label
              >
              <textarea
                v-model="formularioBolo.concepto"
                required
                rows="2"
                placeholder="Ej: Concierto de Los Commodoros en La Puerta de Cimadevilla..."
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-purple-500"
              ></textarea>
            </div>

            <div class="col-span-1">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Fecha del Concierto</label
              >
              <input
                v-model="formularioBolo.fecha_evento"
                type="date"
                required
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-purple-500"
              />
            </div>

            <div class="col-span-1">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Fecha Emisión</label
              >
              <input
                v-model="formularioBolo.fecha_emision"
                type="date"
                required
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-purple-500"
              />
            </div>

            <div class="col-span-1">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Base Imponible (€)</label
              >
              <input
                v-model="formularioBolo.subtotal"
                type="number"
                step="0.01"
                required
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-purple-500 font-mono font-bold"
              />
            </div>

            <div class="col-span-1">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Cálculo Fiscal</label
              >
              <div
                class="text-xs text-slate-500 bg-slate-50 p-2 rounded border border-slate-100"
              >
                IVA: 10% | IRPF: 15%
              </div>
            </div>
          </div>

          <div
            class="bg-purple-50 p-4 rounded-xl border border-purple-100 flex justify-between items-center"
          >
            <div class="text-purple-700 font-medium">Líquido a Percibir:</div>
            <div class="text-2xl font-black text-purple-900">
              {{ totalBolo }} €
            </div>
          </div>

          <div class="pt-4 flex gap-3">
            <button
              type="button"
              @click="mostrarModalBolo = false"
              class="flex-1 px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-bold shadow-md shadow-purple-100"
            >
              Emitir Factura Serie B
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
