<script setup>
import { reactive, computed } from "vue";
import { useFacturaStore } from "@/stores/factura";
import { useClienteStore } from "@/stores/cliente";
import { storeToRefs } from "pinia";
import { notifyError, toast } from "@/utils/swal";

const emit = defineEmits(["close"]);

const facturaStore = useFacturaStore();
const clienteStore = useClienteStore();
const { clientes } = storeToRefs(clienteStore);

const formulario = reactive({
  cliente_id: "",
  subtotal: 0,
  iva_porcentaje: 10,
  irpf_porcentaje: 15,
  concepto: "",
  fecha_evento: "",
  fecha_emision: new Date().toISOString().split("T")[0],
});

const totalBolo = computed(() => {
  const base = parseFloat(formulario.subtotal) || 0;
  const iva = (base * formulario.iva_porcentaje) / 100;
  const irpf = (base * formulario.irpf_porcentaje) / 100;
  return (base + iva - irpf).toFixed(2);
});

const clientesBolo = computed(() =>
  clientes.value.filter((e) => e.tipo === "bolo"),
);

function limpiarFormulario() {
  formulario.cliente_id = "";
  formulario.subtotal = 0;
  formulario.concepto = "";
  formulario.fecha_evento = "";
  formulario.fecha_emision = new Date().toISOString().split("T")[0];
}

async function guardar() {
  try {
    await facturaStore.guardarBolo(formulario);
    toast("Factura B generada con éxito");
    limpiarFormulario();
    emit("close");
  } catch {
    notifyError("Error", "Error al guardar factura de bolo.");
  }
}

function cerrar() {
  limpiarFormulario();
  emit("close");
}
</script>

<template>
  <div
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4 z-50"
  >
    <div
      class="bg-white rounded-t-2xl sm:rounded-2xl w-full sm:w-[90%] lg:w-[55%] shadow-2xl animate-in fade-in slide-in-from-bottom-4 sm:zoom-in duration-200 max-h-[95dvh] flex flex-col"
    >
      <!-- Cabecera -->
      <div
        class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl shrink-0"
      >
        <h3 class="text-xl font-bold text-slate-800">
          Nueva Factura de Bolo (Serie B)
        </h3>
        <button
          @click="cerrar"
          class="text-slate-400 hover:text-slate-600 text-xl leading-none"
          aria-label="Cerrar modal"
        >
          ✕
        </button>
      </div>

      <!-- Formulario con scroll interno si hay poco espacio -->
      <div class="overflow-y-auto flex-1">
        <form @submit.prevent="guardar" novalidate class="p-6 space-y-4">
          <div class="grid grid-cols-12 gap-4">
            <!-- Cliente -->
            <div class="col-span-12">
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Cliente (Entidad/Organizador)
              </label>
              <select
                v-model="formulario.cliente_id"
                required
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-purple-500"
              >
                <option value="" disabled>Selecciona un cliente...</option>
                <option
                  v-for="cliente in clientesBolo"
                  :key="cliente.id"
                  :value="cliente.id"
                >
                  {{ cliente.nombre }}
                  <span v-if="cliente.nif_cif">({{ cliente.nif_cif }})</span>
                </option>
              </select>
              <p
                v-if="!clientesBolo.length"
                class="text-xs text-amber-600 mt-1 italic"
              >
                * Primero debes crear un cliente de tipo "Bolo" en la sección
                Clientes.
              </p>
            </div>

            <!-- Concepto -->
            <div class="col-span-12">
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Concepto del Concierto
              </label>
              <textarea
                v-model="formulario.concepto"
                required
                rows="2"
                placeholder="Ej: Concierto de Los Commodoros en La Puerta de Cimadevilla..."
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-purple-500 resize-none"
              />
            </div>

            <!-- Fecha evento -->
            <div class="col-span-12 lg:col-span-6">
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Fecha del Concierto
              </label>
              <input
                v-model="formulario.fecha_evento"
                type="date"
                required
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-purple-500"
              />
            </div>

            <!-- Fecha emisión -->
            <div class="col-span-12 lg:col-span-6">
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Fecha Emisión
              </label>
              <input
                v-model="formulario.fecha_emision"
                type="date"
                required
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-purple-500"
              />
            </div>

            <!-- Base imponible -->
            <div class="col-span-12 lg:col-span-6">
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Base Imponible (€)
              </label>
              <input
                v-model="formulario.subtotal"
                type="number"
                step="0.01"
                required
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-purple-500 font-mono font-bold"
              />
            </div>

            <!-- Cálculo fiscal -->
            <div class="col-span-12 lg:col-span-6">
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Cálculo Fiscal
              </label>
              <div
                class="text-xs text-slate-500 bg-slate-50 p-2 rounded border border-slate-100 h-10 flex items-center"
              >
                IVA: {{ formulario.iva_porcentaje }}% | IRPF:
                {{ formulario.irpf_porcentaje }}%
              </div>
            </div>
          </div>

          <!-- Total -->
          <div
            class="bg-purple-50 p-4 rounded-xl border border-purple-100 flex justify-between items-center"
          >
            <div class="text-purple-700 font-medium">Líquido a Percibir:</div>
            <div class="text-2xl font-black text-purple-900">
              {{ totalBolo }} €
            </div>
          </div>

          <!-- Acciones -->
          <div class="pt-2 flex flex-col md:flex-row gap-3">
            <button
              type="button"
              @click="cerrar"
              class="flex-1 px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 transition"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-bold shadow-md shadow-purple-100 transition"
            >
              Emitir Factura Serie B
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
