<script setup>
defineProps({
  to: { type: Object, required: true },
  activeClass: {
    type: String,
    default: "bg-principal !text-white shadow-sm shadow-principal-200",
  },
});
</script>

<template>
  <router-link
    :to="to"
    class="flex items-center px-4 py-3 rounded-lg text-slate-600 hover:bg-principal-100 hover:text-white transition"
    :active-class="activeClass"
  >
    <span class="font-medium"><slot /></span>
  </router-link>
</template>
