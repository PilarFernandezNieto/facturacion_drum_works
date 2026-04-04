<script setup>
defineProps({
  cliente: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["editar", "eliminar"]);
</script>

<template>
  <div
    class="p-4 border-b border-slate-100 hover:bg-slate-50/50 transition flex flex-col gap-3 md:grid md:grid-cols-12 md:gap-2 md:items-center"
  >
    <!-- Fila superior en móvil: nombre + badges de tipo -->
    <div class="flex items-start justify-between gap-2 md:contents">
      <!-- Nombre / NIF -->
      <div class="md:col-span-4 font-medium text-slate-800 min-w-0">
        <span class="block truncate">{{ cliente.nombre }}</span>
        <span
          v-if="cliente.nif_cif"
          class="uppercase block text-slate-400 text-xs mt-0.5"
        >
          {{ cliente.nif_cif }}
        </span>
      </div>

      <!-- Tipo (badge) — en móvil aparece arriba a la derecha, en desktop en su columna -->
      <div class="md:col-span-2 shrink-0">
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
      </div>
    </div>

    <!-- Fila inferior en móvil: info + cuota + acciones -->
    <div class="flex items-center justify-between gap-2 md:contents">
      <!-- Curso / Info -->
      <div class="md:col-span-2 text-sm text-slate-500 min-w-0">
        <span v-if="cliente.tipo === 'alumno'">{{ cliente.curso || "—" }}</span>
        <span v-else class="text-xs italic">Cliente bolos</span>
      </div>

      <!-- Cuota -->
      <div class="md:col-span-2 md:text-center">
        <span class="font-bold text-slate-900"
          >{{ cliente.cuota_mensual }}€</span
        >
      </div>

      <!-- Acciones -->
      <div class="md:col-span-2 flex items-center justify-end gap-1 shrink-0">
        <button
          @click="emit('editar', { ...cliente })"
          class="text-indigo-500 hover:bg-indigo-50 p-2 rounded-lg transition"
          title="Editar"
        >
          <svg
            width="22"
            height="22"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M21.2799 6.40005L11.7399 15.94C10.7899 16.89 7.96987 17.33 7.33987 16.7C6.70987 16.07 7.13987 13.25 8.08987 12.3L17.6399 2.75002C17.8754 2.49308 18.1605 2.28654 18.4781 2.14284C18.7956 1.99914 19.139 1.92124 19.4875 1.9139C19.8359 1.90657 20.1823 1.96991 20.5056 2.10012C20.8289 2.23033 21.1225 2.42473 21.3686 2.67153C21.6147 2.91833 21.8083 3.21243 21.9376 3.53609C22.0669 3.85976 22.1294 4.20626 22.1211 4.55471C22.1128 4.90316 22.0339 5.24635 21.8894 5.5635C21.7448 5.88065 21.5375 6.16524 21.2799 6.40005Z"
              stroke="oklch(39.8% 0.195 277.366)"
              stroke-width="1.5"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
            <path
              d="M11 4H6C4.93913 4 3.92178 4.42142 3.17163 5.17157C2.42149 5.92172 2 6.93913 2 8V18C2 19.0609 2.42149 20.0783 3.17163 20.8284C3.92178 21.5786 4.93913 22 6 22H17C19.21 22 20 20.2 20 18V13"
              stroke="oklch(39.8% 0.195 277.366)"
              stroke-width="1.5"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </button>

        <button
          @click="emit('eliminar', cliente.id)"
          class="text-red-500 hover:bg-red-50 p-2 rounded-lg transition"
          title="Eliminar"
        >
          <svg
            width="22"
            height="22"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M4 6H20M16 6L15.7294 5.18807C15.4671 4.40125 15.3359 4.00784 15.0927 3.71698C14.8779 3.46013 14.6021 3.26132 14.2905 3.13878C13.9376 3 13.523 3 12.6936 3H11.3064C10.477 3 10.0624 3 9.70951 3.13878C9.39792 3.26132 9.12208 3.46013 8.90729 3.71698C8.66405 4.00784 8.53292 4.40125 8.27064 5.18807L8 6M18 6V16.2C18 17.8802 18 18.7202 17.673 19.362C17.3854 19.9265 16.9265 20.3854 16.362 20.673C15.7202 21 14.8802 21 13.2 21H10.8C9.11984 21 8.27976 21 7.63803 20.673C7.07354 20.3854 6.6146 19.9265 6.32698 19.362C6 18.7202 6 17.8802 6 16.2V6M14 10V17M10 10V17"
              stroke="#dc2626"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>
