<script setup lang="ts">
import LayoutWrapper from "@/Layouts/LayoutWrapper.vue";
import { Badge } from '@/shadcn/ui/badge'
import { Button } from '@/shadcn/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shadcn/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/ui/table'
import { Avatar, AvatarImage, AvatarFallback } from "@/shadcn/ui/avatar"
import { Users, Clock, UserX, UserCheck, TrendingUp, TrendingDown } from 'lucide-vue-next'
import PresenceChart from '@/Components/PresenceChart.vue'
import { navigateLink } from "@/lib/utils"

const props = defineProps<{
  stats: {
    total_employee: number
    active_today: number
    active_yesterday: number
    late_today: number
    late_yesterday: number
    on_time_today: number
    no_presence_today: number
    no_presence_yesterday: number
    weekly_presence: { label: string; hadir: number; terlambat: number; alpha: number }[]
    monthly_stats: {
      current: { total_presence: number; avg_work_hours: number; total_salary: number; on_time_rate: number }
      previous: { total_presence: number; avg_work_hours: number; total_salary: number; on_time_rate: number }
    }
    recent_salary: {
      id: number
      employee_name: string
      employee_email: string
      start_date: string
      end_date: string
      total: number
      status: string
    }[]
    recent_presence: {
      id: number
      employee_name: string
      avatar: string | null
      location: string
      time_in: string
      note: string | null
      status_by_admin: string
    }[]
  }
}>()

defineOptions({
  layout: LayoutWrapper
})

const fmtRupiah = (v: number) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v)

const pctChange = (cur: number, prev: number) => {
  if (prev === 0) return cur > 0 ? '+100' : '0'
  const c = ((cur - prev) / prev) * 100
  return (c >= 0 ? '+' : '') + c.toFixed(1)
}

const deltaPct = (cur: number, prev: number) => {
  if (prev === 0) return 0
  return Math.round(((cur - prev) / prev) * 100)
}

const presenceStatus = (note: string | null) => {
  if (!note) return { label: 'Tepat Waktu', variant: 'success' as const }
  if (note.includes('Terlambat')) return { label: 'Terlambat', variant: 'destructive' as const }
  if (note.includes('radius')) return { label: 'Luar Radius', variant: 'warning' as const }
  if (note.includes('tanpa check out')) return { label: 'No Checkout', variant: 'secondary' as const }
  return { label: note, variant: 'outline' as const }
}

const salaryStatus = (s: string) => {
  if (s === 'approved') return { label: 'Approved', variant: 'success' as const }
  if (s === 'denied') return { label: 'Denied', variant: 'destructive' as const }
  return { label: 'Pending', variant: 'warning' as const }
}
</script>

<template>
  <main class="flex flex-1 flex-col gap-4 p-4 md:gap-8 md:p-8">
    <div class="grid gap-4 md:grid-cols-2 md:gap-8 lg:grid-cols-4">
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Total Karyawan</CardTitle>
          <Users class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.total_employee }}</div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Hadir Hari Ini</CardTitle>
          <UserCheck class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.active_today }}</div>
          <p class="text-xs text-muted-foreground">
            {{ pctChange(stats.active_today, stats.active_yesterday) }}% dari kemarin
          </p>
        </CardContent>
      </Card>
      <Card class="outline outline-destructive">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Terlambat</CardTitle>
          <Clock class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.late_today }}</div>
          <p class="text-xs text-muted-foreground">
            {{ pctChange(stats.late_today, stats.late_yesterday) }}% dari kemarin
          </p>
        </CardContent>
      </Card>
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Belum Absen</CardTitle>
          <UserX class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.no_presence_today }}</div>
          <p class="text-xs text-muted-foreground">
            {{ pctChange(stats.no_presence_today, stats.no_presence_yesterday) }}% dari kemarin
          </p>
        </CardContent>
      </Card>
    </div>

    <div class="grid gap-4 md:gap-8 lg:grid-cols-2 xl:grid-cols-3">
      <Card class="xl:col-span-2">
        <CardHeader>
          <CardTitle>Grafik Presensi 7 Hari</CardTitle>
          <CardDescription>Jumlah hadir, terlambat, dan alpha per hari</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="h-72">
            <PresenceChart :data="stats.weekly_presence" />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Bulan Ini vs Bulan Lalu</CardTitle>
        </CardHeader>
        <CardContent class="grid gap-4">
          <div v-for="(label, key) in { total_presence: 'Total Presensi', avg_work_hours: 'Rata-rata Jam', total_salary: 'Total Gaji', on_time_rate: 'Tepat Waktu' }" :key="key" class="flex items-center justify-between border-b pb-2 last:border-0 last:pb-0">
            <span class="text-sm text-muted-foreground">{{ label }}</span>
            <div class="flex items-center gap-2">
              <span class="text-sm font-medium">
                <template v-if="key === 'total_salary'">{{ fmtRupiah(stats.monthly_stats.current[key as keyof typeof stats.monthly_stats.current] as number) }}</template>
                <template v-else-if="key === 'on_time_rate'">{{ stats.monthly_stats.current.on_time_rate }}%</template>
                <template v-else>{{ stats.monthly_stats.current[key as keyof typeof stats.monthly_stats.current] }}</template>
              </span>
              <span v-if="key !== 'total_salary'" class="text-xs" :class="deltaPct(stats.monthly_stats.current[key as keyof typeof stats.monthly_stats.current] as number, stats.monthly_stats.previous[key as keyof typeof stats.monthly_stats.previous] as number) >= 0 ? 'text-green-600' : 'text-red-600'">
                <TrendingUp v-if="deltaPct(stats.monthly_stats.current[key as keyof typeof stats.monthly_stats.current] as number, stats.monthly_stats.previous[key as keyof typeof stats.monthly_stats.previous] as number) >= 0" class="inline h-3 w-3" />
                <TrendingDown v-else class="inline h-3 w-3" />
                {{ Math.abs(deltaPct(stats.monthly_stats.current[key as keyof typeof stats.monthly_stats.current] as number, stats.monthly_stats.previous[key as keyof typeof stats.monthly_stats.previous] as number)) }}%
              </span>
              <span v-else class="text-xs" :class="deltaPct(stats.monthly_stats.current.total_salary, stats.monthly_stats.previous.total_salary) >= 0 ? 'text-green-600' : 'text-red-600'">
                <TrendingUp v-if="deltaPct(stats.monthly_stats.current.total_salary, stats.monthly_stats.previous.total_salary) >= 0" class="inline h-3 w-3" />
                <TrendingDown v-else class="inline h-3 w-3" />
                {{ Math.abs(deltaPct(stats.monthly_stats.current.total_salary, stats.monthly_stats.previous.total_salary)) }}%
              </span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <div class="grid gap-4 md:gap-8 lg:grid-cols-2 xl:grid-cols-3">
      <Card class="xl:col-span-2">
        <CardHeader class="flex flex-row items-center">
          <div class="grid gap-2">
            <CardTitle>Transaksi Gaji Terbaru</CardTitle>
            <CardDescription>Slip gaji terakhir dari perusahaan</CardDescription>
          </div>
          <Button @click="navigateLink(route('company-receipt.index'))" size="sm" class="ml-auto gap-1">
            View All
            <TrendingUp class="h-4 w-4" />
          </Button>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Karyawan</TableHead>
                <TableHead class="hidden xl:table-column">Periode</TableHead>
                <TableHead class="hidden xl:table-column">Status</TableHead>
                <TableHead class="text-right">Total</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="s in stats.recent_salary" :key="s.id">
                <TableCell>
                  <div class="font-medium">{{ s.employee_name }}</div>
                  <div class="hidden text-sm text-muted-foreground md:inline">{{ s.employee_email }}</div>
                </TableCell>
                <TableCell class="hidden xl:table-cell text-sm">
                  {{ s.start_date }} — {{ s.end_date }}
                </TableCell>
                <TableCell class="hidden xl:table-cell">
                  <Badge :variant="salaryStatus(s.status).variant" class="text-xs">
                    {{ salaryStatus(s.status).label }}
                  </Badge>
                </TableCell>
                <TableCell class="text-right font-medium">
                  {{ fmtRupiah(s.total) }}
                </TableCell>
              </TableRow>
              <TableRow v-if="stats.recent_salary.length === 0">
                <TableCell colspan="4" class="text-center text-muted-foreground py-8">
                  Belum ada transaksi gaji
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Presensi Terbaru</CardTitle>
        </CardHeader>
        <CardContent class="grid gap-6">
          <div v-for="p in stats.recent_presence" :key="p.id" class="flex items-center gap-4">
            <Avatar class="h-9 w-9">
              <AvatarImage :src="p.avatar ?? undefined" />
              <AvatarFallback>{{ (p.employee_name?.[0] ?? '?').toUpperCase() }}</AvatarFallback>
            </Avatar>
            <div class="grid gap-1 flex-1 min-w-0">
              <p class="text-sm font-medium leading-none truncate">{{ p.employee_name }}</p>
              <p class="text-sm text-muted-foreground truncate">{{ p.location }}</p>
            </div>
            <div class="text-right shrink-0">
              <div class="text-sm font-medium">{{ p.time_in }}</div>
              <Badge :variant="presenceStatus(p.note).variant" class="text-xs">
                {{ presenceStatus(p.note).label }}
              </Badge>
            </div>
          </div>
          <div v-if="stats.recent_presence.length === 0" class="text-center text-muted-foreground py-8">
            Belum ada presensi hari ini
          </div>
        </CardContent>
      </Card>
    </div>
  </main>
</template>
