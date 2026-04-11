<script setup>
defineProps({
  pagina: { type: Number, required: true },
  totalPaginas: { type: Number, required: true },
  paginas: { type: Array, required: true },
  desde: { type: Number, required: true },
  hasta: { type: Number, required: true },
  total: { type: Number, required: true },
});

defineEmits(["ir"]);
</script>

<template>
  <div
    v-if="totalPaginas > 1"
    class="flex flex-col items-center justify-between gap-3 px-4 py-3 border-t border-slate-100 bg-slate-50/60"
  >
    <!-- Info -->
    <p class="text-sm text-slate-500 order-2">
      Mostrando
      <span class="font-semibold text-slate-700">{{ desde }}–{{ hasta }}</span>
      de
      <span class="font-semibold text-slate-700">{{ total }}</span>
    </p>

    <!-- Controles -->
    <div class="flex items-center gap-1 order-1">
      <!-- Anterior -->
      <button
        @click="$emit('ir', pagina - 1)"
        :disabled="pagina === 1"
        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 disabled:opacity-30 disabled:cursor-not-allowed transition"
      >
        ‹
      </button>

      <!-- Páginas -->
      <template v-for="p in paginas" :key="p">
        <span
          v-if="p === '...'"
          class="w-8 h-8 flex items-center justify-center text-slate-400 text-sm select-none"
        >
          ···
        </span>
        <button
          v-else
          @click="$emit('ir', p)"
          :class="[
            'w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium transition',
            p === pagina
              ? 'bg-principal text-white shadow-sm'
              : 'text-slate-600 hover:bg-slate-200',
          ]"
        >
          {{ p }}
        </button>
      </template>

      <!-- Siguiente -->
      <button
        @click="$emit('ir', pagina + 1)"
        :disabled="pagina === totalPaginas"
        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 disabled:opacity-30 disabled:cursor-not-allowed transition"
      >
        ›
      </button>
    </div>
  </div>
</template>
