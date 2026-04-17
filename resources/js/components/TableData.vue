<template>
  <div v-if="isLoading" class="not-prose">
    <div class="flex flex-col gap-1 rounded-xl bg-gray-950/5 p-1 inset-ring inset-ring-gray-950/5">
      <div class="not-prose overflow-auto rounded-lg bg-white outline outline-white/5">
        <div>
          <table class="w-full table-auto border-collapse text-sm">
            <thead>
              <tr>
                <th class="border-b border-gray-200 p-4 pb-3 pl-8 text-left font-medium text-gray-600 w-170 max-w-170 hover:text-black hover:bg-gray-100">
                  Name
                </th>
                  <th
                  v-for="key in Object.keys(METRICS)"
                  :key=key
                  :class="headerClassComputed(key)"
                  @click="clickOnSort(key)">
                    <div class="flex items-center">
                      <p>{{ METRICS[key].fullName }}</p>
                      <font-awesome-icon v-if="sortBy.key === key" class="size-6 shrink-0 text-gray-500" :icon="iconComputed" />
                    </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(line, index) in currentPaginatedReport" :key=index>
                <td class="border-b border-gray-100 p-4 pl-8 text-gray-500 min-w-170 w-170 max-w-170 truncate">{{ index }}</td>
                <td
                 v-for="key in Object.keys(METRICS)" :key=key
                 :class="cellClassComputed(key)"
                >
                    {{ converter(key, line.globalMetrics[key]) }}
                </td>
              </tr>
            </tbody>
          </table>
          <div class="flex justify-end text-xl p-5 gap-2">
            <btn class="border-2 border-gray-200 p-2 text-gray-500 rounded-xl hover:text-black hover:bg-gray-100" @click="handlePrev">Prev</btn>
            <p class="min-w-14 text-center border-2 border-gray-200 p-2 text-gray-500 rounded-xl">{{ page }}/{{ numberPage }}</p>
            <btn class="border-2 border-solid border-gray-200 p-2 text-gray-500 rounded-xl hover:text-black hover:bg-gray-100" @click="handleNext">Next</btn>
          </div>
        </div>
      </div>
    </div>
  </div>
  <Spinner v-else />
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import Spinner from '@/components/Spinner.vue'
import { useConverter } from '@/composables/converter'
import { useTableData } from '@/composables/UseTableData.composable'
import { METRICS } from '@/constants/metricConstant'

defineOptions({
  name: 'TableData'
})

const { converter } = useConverter()

const {
    page,
    currentPaginatedReport,
    numberPage,
    isLoading,
    sortBy,
    clickOnSort,
    handleNext,
    handlePrev,
    headerClassComputed,
    cellClassComputed
} = useTableData()

const iconComputed = computed(() => sortBy.value.order === 'asc' ? 'fa-solid fa-arrow-up' : 'fa-solid fa-arrow-down')
</script>
