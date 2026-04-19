<script setup>
import { reactive, computed, watch } from "vue";
import {
  useAgregarCliente,
  useActualizarCliente,
} from "@/composables/useClientes";
import { notifyError, toast } from "@/utils/swal";
import PrimaryButton from "@/components/buttons/PrimaryButton.vue";

const props = defineProps({
  cliente: {
    type: Object,
    default: null, // null = modo creación, objeto = modo edición
  },
  tipoDefault: {
    type: String,
    default: "alumno", // pre-rellena el tipo según la sección desde la que se abre
  },
});

const emit = defineEmits(["close"]);

const { mutateAsync: agregar } = useAgregarCliente();
const { mutateAsync: actualizar } = useActualizarCliente();

const formulario = reactive({
  id: null,
  nombre: "",
  nif_cif: "",
  email: "",
  telefono: "",
  direccion: "",
  codigo_postal: "",
  localidad: "",
  provincia: "",
  curso: "",
  cuota_mensual: 0,
  tipo: "alumno",
  activo: true,
});

const editando = computed(() => !!props.cliente);

// Rellena el formulario cuando se abre en modo edición
watch(
  () => props.cliente,
  (cliente) => {
    if (cliente) {
      Object.assign(formulario, cliente);
    } else {
      resetFormulario();
    }
  },
  { immediate: true },
);

function resetFormulario() {
  formulario.id = null;
  formulario.nombre = "";
  formulario.nif_cif = "";
  formulario.email = "";
  formulario.telefono = "";
  formulario.direccion = "";
  formulario.codigo_postal = "";
  formulario.localidad = "";
  formulario.provincia = "";
  formulario.curso = "";
  formulario.cuota_mensual = 0;
  formulario.tipo = props.tipoDefault;
  formulario.activo = true;
}

async function guardar() {
  try {
    if (editando.value) {
      await actualizar({ id: formulario.id, datos: formulario });
      toast("Cliente actualizado");
    } else {
      await agregar(formulario);
      toast("Cliente creado");
    }
    emit("close");
  } catch (error) {
    const status = error.response?.status;
    const msg =
      status === 422 ? "Revisa los campos del formulario" :
      status === 409 ? "Este cliente ya existe" :
      status >= 500 ? "Error del servidor. Intenta más tarde" :
      "No se pudo guardar el cliente";
    notifyError("Error al guardar", msg);
  }
}

function cerrar() {
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
        class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl shrink-0"
      >
        <h3 class="text-xl font-bold text-slate-800">
          {{ editando ? "Editar Cliente" : "Nuevo Cliente" }}
        </h3>
        <button
          @click="cerrar"
          class="text-slate-400 hover:text-slate-600 w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-200 transition"
          aria-label="Cerrar modal"
        >
          ✕
        </button>
      </div>

      <!-- Formulario con scroll interno -->
      <div class="overflow-y-auto flex-1">
        <form @submit.prevent="guardar" novalidate class="p-5 space-y-4">
          <div class="grid grid-cols-12 gap-2">
            <!-- Tipo -->
            <div class="col-span-12">
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Tipo de Cliente
              </label>
              <select
                v-model="formulario.tipo"
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-principal-200"
              >
                <option value="alumno">Alumno (Clases)</option>
                <option value="bolo">Bolos (Conciertos)</option>
              </select>
            </div>

            <!-- Nombre -->
            <div class="col-span-12">
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Nombre Completo / Razón Social
              </label>
              <input
                v-model="formulario.nombre"
                type="text"
                required
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-principal-200"
              />
            </div>

            <!-- NIF / Cuota -->
            <div
              :class="formulario.tipo === 'bolo' ? 'col-span-12' : 'col-span-12 lg:col-span-6'"
            >
              <label class="block text-sm font-medium text-slate-700 mb-1">
                NIF / CIF
              </label>
              <input
                v-model="formulario.nif_cif"
                type="text"
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-principal-200 uppercase"
              />
            </div>

            <div
              v-if="formulario.tipo === 'alumno'"
              class="col-span-12 lg:col-span-6 animate-in slide-in-from-top-2"
            >
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Cuota Mensual (€)
              </label>
              <input
                v-model="formulario.cuota_mensual"
                type="number"
                step="0.01"
                required
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-principal-200 font-mono"
              />
            </div>

            <!-- Curso (solo alumnos) -->
            <div
              v-if="formulario.tipo === 'alumno'"
              class="col-span-12 animate-in slide-in-from-top-2"
            >
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Curso / Grupo
              </label>
              <input
                v-model="formulario.curso"
                type="text"
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-principal-200"
              />
            </div>

            <!-- Activo (solo alumnos) -->
            <div
              v-if="formulario.tipo === 'alumno'"
              class="col-span-12 animate-in slide-in-from-top-2"
            >
              <label class="flex items-center gap-3 cursor-pointer select-none">
                <div class="relative inline-flex items-center shrink-0">
                  <input
                    type="checkbox"
                    v-model="formulario.activo"
                    class="sr-only peer"
                  />
                  <div
                    class="w-10 h-6 bg-slate-200 peer-checked:bg-principal rounded-full transition-colors peer-focus:ring-2 peer-focus:ring-principal-200"
                  ></div>
                  <span
                    class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"
                  ></span>
                </div>
                <div>
                  <span class="block text-sm font-medium text-slate-700">
                    Alumno activo
                  </span>
                  <span class="block text-xs text-slate-400">
                    Solo los alumnos activos se incluyen en la facturación
                    mensual masiva
                  </span>
                </div>
              </label>
            </div>

            <!-- Dirección -->
            <div class="col-span-12">
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Dirección Fiscal / Postal
              </label>
              <input
                v-model="formulario.direccion"
                type="text"
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-principal-200"
              />
            </div>

            <!-- CP / Localidad / Provincia -->
            <div class="col-span-12 lg:col-span-4">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >C.P.</label
              >
              <input
                v-model="formulario.codigo_postal"
                type="text"
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-principal-200"
              />
            </div>

            <div class="col-span-12 lg:col-span-4">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Localidad</label
              >
              <input
                v-model="formulario.localidad"
                type="text"
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-principal-200"
              />
            </div>

            <div class="col-span-12 lg:col-span-4">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Provincia</label
              >
              <input
                v-model="formulario.provincia"
                type="text"
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-principal-200"
              />
            </div>

            <!-- Email / Teléfono -->
            <div class="col-span-12 lg:col-span-6">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Email</label
              >
              <input
                v-model="formulario.email"
                type="email"
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-principal-200"
              />
            </div>

            <div class="col-span-12 lg:col-span-6">
              <label class="block text-sm font-medium text-slate-700 mb-1"
                >Teléfono</label
              >
              <input
                v-model="formulario.telefono"
                type="text"
                class="w-full px-4 py-2 rounded-lg border border-slate-200 outline-none focus:ring-2 focus:ring-principal-200"
              />
            </div>
          </div>

          <!-- Botones -->
          <div class="pt-2 flex flex-col sm:flex-row gap-3 pb-1">
            <button
              type="button"
              @click="cerrar"
              class="flex-1 px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 transition"
            >
              Cancelar
            </button>
            <PrimaryButton type="submit" class="flex-1">Guardar</PrimaryButton>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
