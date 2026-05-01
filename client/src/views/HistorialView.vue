<script setup>
import { ref, computed, watch } from "vue";
import { useHistorial, formatearMes, formatearEuros } from "@/composables/useHistorial";

const { historial, isLoading } = useHistorial();

const años = computed(() => {
  const set = new Set((historial.value ?? []).map((f) => f.mes.slice(0, 4)));
  return [...set].sort((a, b) => b - a);
});

const añoSeleccionado = ref(null);

watch(años, (lista) => {
  if (lista.length && !añoSeleccionado.value) {
    añoSeleccionado.value = lista[0];
  }
}, { immediate: true });

const filasFiltradas = computed(() =>
  (historial.value ?? []).filter((f) => f.mes.startsWith(añoSeleccionado.value))
);
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Historial de facturación</h1>
        <p class="text-slate-400 text-sm mt-1">Resumen mensual de clases, bolos y recaudación</p>
      </div>

      <!-- Selector de año -->
      <div v-if="años.length" class="flex gap-2">
        <button
          v-for="año in años"
          :key="año"
          @click="añoSeleccionado = año"
          :class="[
            'px-4 py-2 rounded-xl text-sm font-semibold transition',
            añoSeleccionado === año
              ? 'bg-principal text-white shadow-sm'
              : 'bg-white border border-slate-200 text-slate-600 hover:border-principal hover:text-principal',
          ]"
        >
          {{ año }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="flex justify-center py-20">
      <div class="w-8 h-8 border-4 border-principal border-t-transparent rounded-full animate-spin" />
    </div>

    <!-- Sin datos -->
    <div
      v-else-if="!historial?.length"
      class="bg-white rounded-2xl border border-principal-100 p-12 text-center"
    >
      <p class="text-slate-400 text-lg">No hay facturas registradas todavía.</p>
    </div>

    <!-- Tabla -->
    <div v-else class="bg-white rounded-2xl border border-principal-100 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-100 text-slate-400 uppercase text-xs tracking-wider">
              <th class="text-left px-6 py-4 font-semibold">Mes</th>
              <th class="text-right px-6 py-4 font-semibold">Alumnos</th>
              <th class="text-right px-6 py-4 font-semibold">Facturas clases</th>
              <th class="text-right px-6 py-4 font-semibold">Total clases</th>
              <th class="text-right px-6 py-4 font-semibold">Bolos</th>
              <th class="text-right px-6 py-4 font-semibold">Total bolos</th>
              <th class="text-right px-6 py-4 font-semibold">Facturado</th>
              <th class="text-right px-6 py-4 font-semibold">Cobrado</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="fila in filasFiltradas"
              :key="fila.mes"
              class="border-b border-slate-50 hover:bg-slate-50 transition"
            >
              <td class="px-6 py-4 font-semibold text-slate-700">
                {{ formatearMes(fila.mes) }}
              </td>
              <td class="px-6 py-4 text-right text-slate-600">
                {{ fila.alumnos ?? 0 }}
              </td>
              <td class="px-6 py-4 text-right text-slate-600">
                {{ fila.facturas_clases ?? 0 }}
              </td>
              <td class="px-6 py-4 text-right text-slate-600">
                {{ formatearEuros(fila.total_clases) }}
              </td>
              <td class="px-6 py-4 text-right text-slate-600">
                {{ fila.bolos ?? 0 }}
              </td>
              <td class="px-6 py-4 text-right text-slate-600">
                {{ formatearEuros(fila.total_bolos) }}
              </td>
              <td class="px-6 py-4 text-right font-semibold text-slate-700">
                {{ formatearEuros(fila.total_facturado) }}
              </td>
              <td class="px-6 py-4 text-right font-bold text-emerald-600">
                {{ formatearEuros(fila.total_cobrado) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
