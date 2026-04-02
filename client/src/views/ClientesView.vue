<script setup>
import { ref, onMounted, reactive, computed } from "vue";
import { storeToRefs } from "pinia";
import { useClienteStore } from "@/stores/cliente";

import { confirmDialog, notifyError, toast } from "@/utils/swal";

const clienteStore = useClienteStore();
const { clientes, isLoading } = storeToRefs(clienteStore);

const mostrarModal = ref(false);
const editando = ref(false);
const filtroTipo = ref("todos"); // 'todos', 'alumno', 'bolo'

const formulario = reactive({
  id: null,
  nombre: "",
  nif_cif: "",
  email: "",
  telefono: "",
  direccion: "",
  curso: "",
  cuota_mensual: 0,
  tipo: "alumno",
});

const clientesFiltrados = computed(() => {
  if (filtroTipo.value === "todos") return clientes.value;
  return clientes.value.filter((c) => c.tipo === filtroTipo.value);
});

function abrirModal(cliente = null) {
  if (cliente) {
    editando.value = true;
    Object.assign(formulario, cliente);
  } else {
    editando.value = false;
    formulario.id = null;
    formulario.nombre = "";
    formulario.nif_cif = "";
    formulario.email = "";
    formulario.telefono = "";
    formulario.direccion = "";
    formulario.curso = "";
    formulario.cuota_mensual = 0;
    formulario.tipo = "alumno";
  }
  mostrarModal.value = true;
}

async function guardarCliente() {
  try {
    if (editando.value) {
      await clienteStore.actualizarCliente(formulario.id, formulario);
      toast("Cliente actualizado");
    } else {
      await clienteStore.agregarCliente(formulario);
      toast("Cliente creado");
    }
    mostrarModal.value = false;
  } catch (error) {
    notifyError(
      "Error al guardar",
      error.response?.data?.error || "Revisa los campos",
    );
  }
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
    } catch (error) {
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
  <div class="space-y-6">
    <div
      class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
    >
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Gestión de Clientes</h2>
        <p class="text-slate-500">
          Administra tus alumnos de clases y tus clientes de bolos.
        </p>
      </div>
      <button
        @click="abrirModal()"
        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-medium transition shadow-sm flex items-center gap-2"
      >
        <span class="text-xl">+</span> Nuevo Cliente
      </button>
    </div>

    <!-- Filtros -->
    <div
      class="flex bg-white p-1 rounded-xl shadow-sm border border-slate-200 w-fit"
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
            ? 'bg-blue-100 text-blue-700 shadow-sm'
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

    <!-- Tabla -->
    <div
      class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden"
    >
      <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50 border-b border-slate-100">
          <tr>
            <th class="px-6 py-4 font-semibold text-slate-700">
              Nombre / Cliente
            </th>
            <th class="px-6 py-4 font-semibold text-slate-700">Tipo</th>
            <th class="px-6 py-4 font-semibold text-slate-700">Info / Curso</th>
            <th class="px-6 py-4 font-semibold text-slate-700 text-right">
              Cuota/Base
            </th>
            <th class="px-6 py-4 font-semibold text-slate-700 text-center">
              Acciones
            </th>
          </tr>
        </thead>
        <tbody v-if="!isLoading && clientesFiltrados.length">
          <tr
            v-for="cliente in clientesFiltrados"
            :key="cliente.id"
            class="border-b border-slate-50 hover:bg-slate-50/50 transition"
          >
            <td class="px-6 py-4">
              <div class="font-medium text-slate-800">{{ cliente.nombre }}</div>
              <div class="text-xs text-slate-400">
                NIF: {{ cliente.nif_cif || "Sin NIF" }}
              </div>
            </td>
            <td class="px-6 py-4">
              <span
                :class="
                  cliente.tipo === 'alumno'
                    ? 'bg-blue-100 text-blue-700'
                    : 'bg-purple-100 text-purple-700'
                "
                class="px-2 py-1 rounded text-xs font-bold uppercase"
              >
                {{ cliente.tipo }}
              </span>
            </td>
            <td class="px-6 py-4 text-slate-600">
              <div v-if="cliente.tipo === 'alumno'">
                {{ cliente.curso || "-" }}
              </div>
              <div v-else class="text-xs italic">Cliente para bolos</div>
            </td>
            <td class="px-6 py-4 text-right font-mono font-bold text-slate-800">
              {{ cliente.cuota_mensual }}€
            </td>
            <td class="px-6 py-4 text-center space-x-2">
              <button
                @click="abrirModal(cliente)"
                class="text-blue-500 hover:bg-blue-50 p-2 rounded-lg transition"
                title="Editar"
              >
                ✎
              </button>
              <button
                @click="eliminarCliente(cliente.id)"
                class="text-red-500 hover:bg-red-50 p-2 rounded-lg transition"
                title="Eliminar"
              >
                🗑
              </button>
            </td>
          </tr>
        </tbody>
        <tbody v-else>
          <tr>
            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
              {{
                isLoading
                  ? "isLoading datos..."
                  : "No hay registros que coincidan con el filtro."
              }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Formulario -->
    <div
      v-if="mostrarModal"
      class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50"
    >
      <div
        class="bg-white rounded-2xl w-full max-w-lg shadow-2xl animate-in fade-in zoom-in duration-200"
      >
        <div
          class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl"
        >
          <h3 class="text-xl font-bold text-slate-800">
            {{ editando ? "Editar Cliente" : "Nuevo Cliente" }}
          </h3>
          <button
            @click="mostrarModal = false"
            class="text-slate-400 hover:text-slate-600"
          >
            ✕
          </button>
        </div>

        <form @submit.prevent="guardarCliente" novalidate class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Tipo de Cliente</label
              >
              <select
                v-model="formulario.tipo"
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="alumno">Alumno (Clases)</option>
                <option value="bolo">Bolos (Conciertos)</option>
              </select>
            </div>

            <div class="col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Nombre Completo / Razón Social</label
              >
              <input
                v-model="formulario.nombre"
                type="text"
                required
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div class="col-span-1">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >NIF / CIF</label
              >
              <input
                v-model="formulario.nif_cif"
                type="text"
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500 uppercase"
              />
            </div>

            <div class="col-span-1">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >{{
                  formulario.tipo === "alumno"
                    ? "Cuota Mensual"
                    : "Precio Base Bolo"
                }}
                (€)</label
              >
              <input
                v-model="formulario.cuota_mensual"
                type="number"
                step="0.01"
                required
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500 font-mono"
              />
            </div>

            <div
              v-if="formulario.tipo === 'alumno'"
              class="col-span-2 animate-in slide-in-from-top-2"
            >
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Curso / Grupo</label
              >
              <input
                v-model="formulario.curso"
                type="text"
                placeholder="Ej: Inglés B2"
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div class="col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Dirección Fiscal / Postal</label
              >
              <input
                v-model="formulario.direccion"
                type="text"
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div class="col-span-1">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Email</label
              >
              <input
                v-model="formulario.email"
                type="email"
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div class="col-span-1">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Teléfono</label
              >
              <input
                v-model="formulario.telefono"
                type="text"
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div class="pt-4 flex gap-3">
            <button
              type="button"
              @click="mostrarModal = false"
              class="flex-1 px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-md shadow-blue-100"
            >
              Guardar Cliente
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
