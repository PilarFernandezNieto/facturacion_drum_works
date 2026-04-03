<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/api/axios";
import { storeToRefs } from "pinia";
import { useClienteStore } from "@/stores/cliente";
import TarjetaDashboard from "@/components/ui/TarjetaDashboard.vue";

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
      <TarjetaDashboard label="Tarjeta Clientes" :value="totalClientes">
        <template #icon> 👥 </template>
      </TarjetaDashboard>

      <!-- Tarjeta Recaudado -->
      <TarjetaDashboard
        label="Recadudado Mes"
        :value="recaudadoMes.toFixed(2)"
        value-class="text-emerald-600"
      >
        <template #icon> 💰 </template>
      </TarjetaDashboard>

      <!-- Tarjeta Pendientes -->
      <TarjetaDashboard label="Facturas Pendientes" :value="facturasPendientes">
        <template #icon> 📄 </template>
      </TarjetaDashboard>
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
