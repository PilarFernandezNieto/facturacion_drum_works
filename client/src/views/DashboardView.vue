<script setup>
import { ref, onMounted } from "vue";
import api from "@/api/axios";

const totalAlumnos = ref(0);
const recaudadoMes = ref(0);
const facturasPendientes = ref(0);
const cargando = ref(true);

async function cargarStats() {
  cargando.value = true;
  try {
    // Alumnos
    const resAlumnos = await api.get("estudiantes");
    totalAlumnos.value = resAlumnos.data.length;

    // Facturas para el mes actual
    const resFacturas = await api.get("facturas");
    const ahora = new Date();
    const facturasMesActual = resFacturas.data.filter((f) => {
      const fecha = new Date(f.fecha_emision);
      return (
        fecha.getMonth() === ahora.getMonth() &&
        fecha.getFullYear() === ahora.getFullYear()
      );
    });

    recaudadoMes.value = facturasMesActual
      .filter((f) => f.estado === "pagada")
      .reduce((sum, f) => sum + parseFloat(f.monto), 0);

    facturasPendientes.value = facturasMesActual.filter(
      (f) => f.estado === "pendiente",
    ).length;
  } catch (error) {
    console.error("Error al cargar estadísticas:", error);
  } finally {
    cargando.value = false;
  }
}

onMounted(cargarStats);
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Tarjeta Alumnos -->
    <div
      class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col justify-between"
    >
      <span
        class="text-slate-400 text-sm font-semibold uppercase tracking-wider"
        >Total Alumnos</span
      >
      <div class="flex items-end justify-between mt-4">
        <h3 class="text-4xl font-bold text-slate-800">{{ totalAlumnos }}</h3>
        <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">👨‍🎓</div>
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
    class="mt-12 bg-blue-600 rounded-3xl p-8 text-white relative overflow-hidden shadow-2xl shadow-blue-200"
  >
    <div class="relative z-10 max-w-lg">
      <h2 class="text-2xl font-bold mb-4">Empieza a trabajar</h2>
      <p class="text-blue-100 mb-6">
        Añade alumnos a tu academia y genera todas las facturas del mes con un
        solo clic en la sección de facturas.
      </p>
      <div class="flex gap-4">
        <router-link
          :to="{ name: 'estudiantes' }"
          class="bg-white text-blue-600 px-6 py-2 rounded-xl font-bold hover:bg-blue-50 transition"
          >Gestionar Alumnos</router-link
        >
        <router-link
          :to="{ name: 'facturas' }"
          class="bg-blue-500 text-white px-6 py-2 rounded-xl font-bold hover:bg-blue-400 transition"
          >Ver Facturación</router-link
        >
      </div>
    </div>
    <div
      class="absolute -right-20 -bottom-20 w-80 h-80 bg-blue-500 rounded-full opacity-50 blur-3xl"
    ></div>
  </div>
</template>
