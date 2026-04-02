<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/api/axios";
import { storeToRefs } from "pinia";
import { useClienteStore } from "@/stores/cliente";

const clienteStore = useClienteStore();
const { clientes } = storeToRefs(clienteStore);

// Estado
const todasFacturas = ref([]);
const isLoading = ref(true);

// Stats derivadas
const totalClientes = computed(() => clientes.value.length);

const ahora = new Date();
const mesActual = ahora.getMonth();
const anioActual = ahora.getFullYear();

const facturasMesActual = computed(() => {
  return todasFacturas.value.filter((f) => {
    if (!f.fecha_emision) return false;
    const [y, m] = f.fecha_emision.split("-").map(Number);
    return y === anioActual && m - 1 === mesActual;
  });
});

const recaudadoMes = computed(() => {
  return facturasMesActual.value
    .filter((f) => f.estado === "pagada")
    .reduce((sum, f) => sum + parseFloat(f.monto || 0), 0);
});

const facturasPendientes = computed(() => {
  return todasFacturas.value.filter((f) => f.estado === "pendiente").length;
});

async function cargarStats() {
  isLoading.value = true;
  try {
    const [resFacturas] = await Promise.all([
      api.get("facturas"),
      clienteStore.cargarClientes(),
    ]);
    todasFacturas.value = resFacturas.data;
  } catch (error) {
    console.error("Error al cargar estadísticas:", error);
  } finally {
    isLoading.value = false;
  }
}

onMounted(cargarStats);
</script>

<template>
  <div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Tarjeta Clientes -->
      <div
        class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col justify-between"
      >
        <span
          class="text-slate-400 text-sm font-semibold uppercase tracking-wider"
          >Total Clientes</span
        >
        <div class="flex items-end justify-between mt-4">
          <h3 class="text-4xl font-bold text-slate-800">{{ totalClientes }}</h3>
          <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">👥</div>
        </div>
      </div>

      <!-- Tarjeta Recaudado -->
      <div
        class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col justify-between"
      >
        <span
          class="text-slate-400 text-sm font-semibold uppercase tracking-wider"
          >Recaudado (Mes)</span
        >
        <div class="flex items-end justify-between mt-4">
          <h3 class="text-4xl font-bold text-emerald-600">
            {{ recaudadoMes.toFixed(2) }}€
          </h3>
          <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">💰</div>
        </div>
      </div>

      <!-- Tarjeta Pendientes -->
      <div
        class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col justify-between"
      >
        <span
          class="text-slate-400 text-sm font-semibold uppercase tracking-wider"
          >Facturas Pendientes</span
        >
        <div class="flex items-end justify-between mt-4">
          <h3 class="text-4xl font-bold text-amber-600">
            {{ facturasPendientes }}
          </h3>
          <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">📄</div>
        </div>
      </div>
    </div>

    <div
      class="mt-12 rounded-3xl p-8 border border-principal relative overflow-hidden shadow-lg shadow-principal-200"
    >
      <div class="relative z-10 max-w-lg">
        <h2>Empieza a trabajar</h2>
        <p class="mb-6">
          Añade clientes a tu panel y genera todas las facturas del mes con un
          solo clic en la sección de facturas.
        </p>
        <div class="flex gap-4">
          <router-link
            :to="{ name: 'clientes' }"
            class="bg-white text-principal px-6 py-2 rounded-xl font-bold hover:bg-blue-50 transition"
            >Gestionar Clientes</router-link
          >
          <router-link
            :to="{ name: 'facturas' }"
            class="bg-principal text-white px-6 py-2 rounded-xl font-bold hover:bg-principal-hover transition"
            >Ver Facturación</router-link
          >
        </div>
      </div>
      <div
        class="absolute -right-20 -bottom-20 w-80 h-80 bg-principal-100 rounded-full opacity-50 blur-3xl"
      ></div>
    </div>
  </div>
</template>
