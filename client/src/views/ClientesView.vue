<script setup>
import { ref, onMounted, reactive, computed } from "vue";
import { storeToRefs } from "pinia";
import { useClienteStore } from "@/stores/cliente";

import { confirmDialog, notifyError, toast } from "@/utils/swal";
import PrimaryButton from "@/components/buttons/PrimaryButton.vue";
import { TrashIcon } from "@heroicons/vue/16/solid";

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
        <h2>Gestión de Clientes</h2>
        <p class="text-slate-500">
          Administra tus alumnos de clases y tus clientes de bolos.
        </p>
      </div>
      <PrimaryButton @click="abrirModal()"
        ><span class="text-xl">+</span> Nuevo Cliente</PrimaryButton
      >
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
                    ? 'bg-principal-50 text-principal'
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
                class="text-indigo-500 hover:bg-indigo-50 p-2 rounded-lg transition"
                title="Editar"
              >
                <svg
                  width="30px"
                  height="30px"
                  viewBox="0 0 24 24"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                  <g
                    id="SVGRepo_tracerCarrier"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  ></g>
                  <g id="SVGRepo_iconCarrier">
                    <path
                      d="M21.2799 6.40005L11.7399 15.94C10.7899 16.89 7.96987 17.33 7.33987 16.7C6.70987 16.07 7.13987 13.25 8.08987 12.3L17.6399 2.75002C17.8754 2.49308 18.1605 2.28654 18.4781 2.14284C18.7956 1.99914 19.139 1.92124 19.4875 1.9139C19.8359 1.90657 20.1823 1.96991 20.5056 2.10012C20.8289 2.23033 21.1225 2.42473 21.3686 2.67153C21.6147 2.91833 21.8083 3.21243 21.9376 3.53609C22.0669 3.85976 22.1294 4.20626 22.1211 4.55471C22.1128 4.90316 22.0339 5.24635 21.8894 5.5635C21.7448 5.88065 21.5375 6.16524 21.2799 6.40005V6.40005Z"
                      stroke="oklch(39.8% 0.195 277.366)"
                      stroke-width="1.5"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    ></path>
                    <path
                      d="M11 4H6C4.93913 4 3.92178 4.42142 3.17163 5.17157C2.42149 5.92172 2 6.93913 2 8V18C2 19.0609 2.42149 20.0783 3.17163 20.8284C3.92178 21.5786 4.93913 22 6 22H17C19.21 22 20 20.2 20 18V13"
                      stroke="oklch(39.8% 0.195 277.366)"
                      stroke-width="1.5"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    ></path>
                  </g>
                </svg>
              </button>
              <button
                @click="eliminarCliente(cliente.id)"
                class="text-red-500 hover:bg-red-50 p-2 rounded-lg transition text-2xl"
                title="Eliminar"
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
