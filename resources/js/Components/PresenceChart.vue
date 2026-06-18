<script setup lang="ts">
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

const props = defineProps<{
  data: {
    label: string
    hadir: number
    terlambat: number
    alpha: number
  }[]
}>()

const chartData = {
  labels: props.data.map(d => d.label),
  datasets: [
    {
      label: 'Hadir',
      data: props.data.map(d => d.hadir),
      borderColor: '#22c55e',
      backgroundColor: 'rgba(34, 197, 94, 0.1)',
      fill: true,
      tension: 0.3,
    },
    {
      label: 'Terlambat',
      data: props.data.map(d => d.terlambat),
      borderColor: '#ef4444',
      backgroundColor: 'rgba(239, 68, 68, 0.1)',
      fill: true,
      tension: 0.3,
    },
    {
      label: 'Alpha',
      data: props.data.map(d => d.alpha),
      borderColor: '#6b7280',
      backgroundColor: 'rgba(107, 114, 128, 0.1)',
      fill: true,
      tension: 0.3,
    },
  ],
}

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
    },
    tooltip: {
      mode: 'index' as const,
      intersect: false,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1,
      },
    },
  },
}
</script>

<template>
  <div class="h-full w-full">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>
