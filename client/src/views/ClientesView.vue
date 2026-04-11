<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { storeToRefs } from "pinia";
import { useClienteStore } from "@/stores/cliente";

import { confirmDialog, notifyError, toast } from "@/utils/swal";
import PrimaryButton from "@/components/buttons/PrimaryButton.vue";
import ModalCliente from "@/components/ui/ModalCliente.vue";
import ClienteComponent from "@/components/ui/ClienteComponent.vue";
import ScreenLoader from "@/components/ui/ScreenLoader.vue";

const route = useRoute();
const clienteStore = useClienteStore();
const { alumnos, bolos, isLoading } = storeToRefs(clienteStore);

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

const mostrarModal = ref(false);
const clienteSeleccionado = ref(null);

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
      await clienteStore.eliminarCliente(id);
      toast("Cliente eliminado", "success");
    } catch {
      notifyError("Error", "No se pudo eliminar el cliente");
    }
  }
}

onMounted(() => {
  if (clienteStore.clientes.length === 0) {
    clienteStore.cargarClientes();
  }
});
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
        <div class="col-span-4">Nombre / Cliente</div>
        <div class="col-span-2">Tipo</div>
        <div class="col-span-2">Info / Curso</div>
        <div class="col-span-2 text-center">Cuota/Base</div>
        <div class="col-span-2 text-right">Acciones</div>
      </div>
      <ClienteComponent
        v-for="cliente in clientesFiltrados"
        :key="cliente.id"
        :cliente="cliente"
        @eliminar="eliminarCliente"
        @editar="abrirEditar"
      />
      <div
        v-if="!clientesFiltrados.length"
        class="px-6 py-12 text-center text-slate-400"
      >
        No hay {{ tipo === "alumno" ? "alumnos" : "clientes de bolos" }} registrados todavía.
      </div>
    </div>
  </div>

  <!-- Modal crear / editar -->
  <ModalCliente
    v-if="mostrarModal"
    :cliente="clienteSeleccionado"
    :tipo-default="tipo"
    @close="cerrarModal"
  />
</template>
