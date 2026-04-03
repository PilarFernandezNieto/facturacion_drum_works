<script setup>
defineProps({
  label: String,
  value: [String, Number],
  valueClass: {
    type: String,
    default: "text-slate-800",
  },
  iconBg: {
    type: String,
    default: "bg-blue-50 text-blue-600",
  },
});
</script>

<template>
  <div
    class="bg-white p-6 rounded-2xl shadow-sm border border-principal-100 flex flex-col justify-between"
  >
    <span class="text-slate-400 text-sm font-semibold uppercase tracking-wider">
      {{ label }}
    </span>
    <div class="flex items-end justify-between mt-4">
      <h3 class="text-4xl font-bold" :class="valueClass">{{ value }}</h3>
      <div class="p-3 rounded-xl" :class="iconBg">
        <slot name="icon" />
      </div>
    </div>
  </div>
</template>
