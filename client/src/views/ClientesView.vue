<script setup>
import { ref, computed, onMounted } from "vue";
import { storeToRefs } from "pinia";
import { useClienteStore } from "@/stores/cliente";

import { confirmDialog, notifyError, toast } from "@/utils/swal";
import PrimaryButton from "@/components/buttons/PrimaryButton.vue";
import ModalCliente from "@/components/ui/ModalCliente.vue";
import ClienteComponent from "@/components/ui/ClienteComponent.vue";
import ScreenLoader from "@/components/ui/ScreenLoader.vue";

const clienteStore = useClienteStore();
const { clientes, isLoading } = storeToRefs(clienteStore);

const mostrarModal = ref(false);
const clienteSeleccionado = ref(null); // null = crear, objeto = editar
const filtroTipo = ref("todos"); // 'todos' | 'alumno' | 'bolo'

const clientesFiltrados = computed(() => {
  if (filtroTipo.value === "todos") return clientes.value;
  return clientes.value.filter((c) => c.tipo === filtroTipo.value);
});

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
  if (clientes.value.length === 0) {
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
        <h2>Gestión de Clientes</h2>
        <p class="text-slate-500">
          Administra tus alumnos de clases y tus clientes de bolos.
        </p>
      </div>
      <PrimaryButton @click="abrirCrear" class="w-full md:w-auto">
        <span class="text-xl">+</span> Nuevo Cliente
      </PrimaryButton>
    </div>

    <!-- Filtros tipo -->
    <div
      class="flex bg-white p-1 rounded-xl shadow-sm border border-slate-200 w-full md:w-fit justify-between"
    >
      <button
        @click="filtroTipo = 'todos'"
        :class="
          filtroTipo === 'todos'
            ? 'bg-slate-100 text-slate-900 shadow-sm'
            : 'text-slate-500 hover:text-slate-700'
        "
        class="px-4 py-2 rounded-lg text-sm font-bold transition"
      >
        Todos
      </button>
      <button
        @click="filtroTipo = 'alumno'"
        :class="
          filtroTipo === 'alumno'
            ? 'bg-principal-50 text-principal shadow-sm'
            : 'text-slate-500 hover:text-slate-700'
        "
        class="px-4 py-2 rounded-lg text-sm font-bold transition"
      >
        Clases
      </button>
      <button
        @click="filtroTipo = 'bolo'"
        :class="
          filtroTipo === 'bolo'
            ? 'bg-purple-100 text-purple-700 shadow-sm'
            : 'text-slate-500 hover:text-slate-700'
        "
        class="px-4 py-2 rounded-lg text-sm font-bold transition"
      >
        Bolos
      </button>
    </div>

    <!-- Lista de clientes -->
    <div
      class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden"
    >
      <!-- Cabecera visible solo en desktop -->
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
    </div>
  </div>
  <!-- Modal crear / editar -->
  <ModalCliente
    v-if="mostrarModal"
    :cliente="clienteSeleccionado"
    @close="cerrarModal"
  />
</template>
