<template>
  <svg
    viewBox="0 0 28 28"
    fill="none"
    stroke="currentColor"
    stroke-width="1.5"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <path v-for="(path, index) in paths" :key="index" :d="path" />
  </svg>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({ categoryId: { type: [Number, String], default: 1 } })
const drawings = {
  1: ['M11 3c0 6-2 8-7 9 5 1 7 3 7 9 1-6 3-8 8-9-5-1-7-3-8-9Z', 'M22 3v6m-3-3h6M21 18v7m-3-3.5h6'],
  13: [
    'M14 2c-1 5-3 7-5 9 0 3 2 5 5 5s5-2 5-5c-2-2-4-4-5-9Z',
    'M6 18v7m-3-3.5h6M22 19v5m-2.5-2.5h5',
  ],
  2: ['M11 3h6v7h2l2 12c0 2-2 3-7 3s-7-1-7-3l2-12h2V3Zm0 4h6', 'M13 12v6l-2 2 3 2 3-2-2-2v-6'],
  3: ['M2 15c6-7 17-7 24 0-7 5-18 5-24 0Z', 'M10 12c-3 7 11 7 8 0M3 12c6-5 16-5 22 0'],
  4: ['M3 12c6 7 16 7 22 0M5 15l-2 3m6-1-1 3m6-2v3m5-4 1 3m3-5 2 3'],
  9: ['M9 3h10l1 5-4 3v14h-4V11L8 8l1-5Zm2 3h6'],
  16: ['M11 3c7-2 10 3 6 8l-2 3v6l-5 3-2-3 3-5V9C8 7 8 5 11 3Z', 'M14 5h2M12 23l-2 3'],
  5: ['M3 23c5-3-1-20 11-20s6 17 11 20l-4 2c-5-3 1-17-7-17S12 22 7 25l-4-2Z'],
  7: ['M3 9h22v15H3V9Zm7 0V6h8v3M3 14c7 4 15 4 22 0', 'M12 14h4v5h-4v-5Z'],
  8: ['M10 8c-3-6 11-6 8 0M7 9h14l3 6-3 2-1 8H8l-1-8-3-2 3-6Zm4 0V6h6v3M9 12h2m6 0h2'],
  17: [
    'M5 15 3 12c-1-2 1-4 3-1l2 2-2-6c-1-2 2-3 3-1l2 5-1-7c0-2 3-2 3 0l1 7 1-7c0-2 3-2 3 0v8l2-5c1-2 4-1 3 1l-2 9-7 7-9-9Z',
  ],
  6: ['M10 3c3-2 5-2 8 0l-1 8-2 2v12h-3V13l-2-2V3Zm0 6h7M13 3v3m2-4v4'],
  10: [
    'M14 3c7 0 11 5 11 11s-5 11-11 11S3 20 3 14 7 3 14 3Z',
    'M14 6c5 0 8 3 8 8s-3 8-8 8-8-3-8-8 3-8 8-8Z',
  ],
  11: ['M12 3h4v5l6 5v9c0 3-16 3-16 0v-9l6-5V3Zm0 5h4M10 15h8v7h-8v-7Zm2-3h4m-3 6h2'],
}
const paths = computed(() => drawings[props.categoryId] || drawings[1])
</script>
