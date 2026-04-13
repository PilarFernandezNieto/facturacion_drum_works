<script setup>
import { ref, computed } from "vue";
import { useRoute } from "vue-router";

import { useClientes, useEliminarCliente } from "@/composables/useClientes";
import { usePaginacion } from "@/composables/usePaginacion";

import { confirmDialog, notifyError, toast } from "@/utils/swal";
import PrimaryButton from "@/components/buttons/PrimaryButton.vue";
import ModalCliente from "@/components/ui/ModalCliente.vue";
import ModalHistorialCliente from "@/components/ui/ModalHistorialCliente.vue";
import ClienteComponent from "@/components/ui/ClienteComponent.vue";
import PaginacionComponent from "@/components/ui/PaginacionComponent.vue";
import ScreenLoader from "@/components/ui/ScreenLoader.vue";

const route = useRoute();
const { alumnos, bolos, isLoading } = useClientes();
const { mutateAsync: eliminar } = useEliminarCliente();

const tipo = computed(() => route.meta.tipo);
const clientesFiltrados = computed(() =>
  tipo.value === "alumno" ? alumnos.value : bolos.value,
);
const titulo = computed(() =>
  tipo.value === "alumno" ? "Alumnos de Clases" : "Clientes de Bolos",
);
const subtitulo = computed(() =>
  tipo.value === "alumno"
    ? "Administra tus alumnos y sus cuotas mensuales."
    : "Administra tus clientes de conciertos y bolos.",
);

const { paginado, pagina, totalPaginas, paginas, desde, hasta, total, irA } =
  usePaginacion(clientesFiltrados, 10);

const mostrarModal = ref(false);
const clienteSeleccionado = ref(null);
const clienteHistorial = ref(null);

function abrirCrear() {
  clienteSeleccionado.value = null;
  mostrarModal.value = true;
}

function abrirEditar(cliente) {
  clienteSeleccionado.value = { ...cliente };
  mostrarModal.value = true;
}

function cerrarModal() {
  mostrarModal.value = false;
  clienteSeleccionado.value = null;
}

async function eliminarCliente(id) {
  const result = await confirmDialog(
    "¿Eliminar cliente?",
    "¿Estás seguro de eliminar a este registro? Se borrarán también sus facturas.",
  );

  if (result.isConfirmed) {
    try {
      await eliminar(id);
      toast("Cliente eliminado", "success");
    } catch {
      notifyError("Error", "No se pudo eliminar el cliente");
    }
  }
}
</script>

<template>
  <div v-if="isLoading">
    <ScreenLoader />
  </div>
  <div v-else class="space-y-6">
    <!-- Cabecera -->
    <div
      class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
    >
      <div>
        <h2>{{ titulo }}</h2>
        <p class="text-slate-500">{{ subtitulo }}</p>
      </div>
      <PrimaryButton @click="abrirCrear" class="w-full md:w-auto">
        <span class="text-xl">+</span> Nuevo
      </PrimaryButton>
    </div>

    <!-- Lista de clientes -->
    <div
      class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden"
    >
      <div
        class="hidden md:grid md:grid-cols-12 md:gap-2 px-4 py-3 bg-slate-50 border-b border-slate-100 text-sm font-semibold text-slate-700"
      >
        <div class="col-span-3">Nombre / Cliente</div>
        <div class="col-span-2">Tipo</div>
        <div class="col-span-2">Info / Curso</div>
        <div class="col-span-1 text-center">Activo</div>
        <div class="col-span-2 text-center">Cuota/Base</div>
        <div class="col-span-2 text-right">Acciones</div>
      </div>
      <ClienteComponent
        v-for="cliente in paginado"
        :key="cliente.id"
        :cliente="cliente"
        @eliminar="eliminarCliente"
        @editar="abrirEditar"
        @ver-historial="clienteHistorial = $event"
      />
      <div v-if="!total" class="px-6 py-12 text-center text-slate-400">
        No hay
        {{ tipo === "alumno" ? "alumnos" : "clientes de bolos" }} registrados
        todavía.
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
  </div>

  <!-- Modal crear / editar -->
  <ModalCliente
    v-if="mostrarModal"
    :cliente="clienteSeleccionado"
    :tipo-default="tipo"
    @close="cerrarModal"
  />

  <!-- Modal historial de facturas -->
  <ModalHistorialCliente
    v-if="clienteHistorial"
    :cliente="clienteHistorial"
    @close="clienteHistorial = null"
  />
</template>
