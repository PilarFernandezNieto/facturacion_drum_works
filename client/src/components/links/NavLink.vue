<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps({
  to: {
    type: [String, Object],
    required: true,
  },
  active: {
    type: Boolean,
  },
})

const classes = computed(() =>
  props.active
    ? 'inline-flex items-center px-1 pt-1 border-b-2 border-green-800  text-lg font-medium leading-5 text-gray-900 focus:outline-none focus:border-green-900 transition duration-150 ease-in-out'
    : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-lg font-medium leading-5 text-gray-500 hover:text-green-800 hover:border-green-800 focus:outline-none focus:text-green-800 focus:border-green-800 transition duration-150 ease-in-out',
)
</script>

<template>
  <RouterLink :to="to" :class="classes">
    <slot />
  </RouterLink>
</template>

