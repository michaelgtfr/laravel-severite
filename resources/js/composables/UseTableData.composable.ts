import { computed, onMounted, ref, watch } from "vue"
import { useXhprofReportStore } from "@/stores/UseXhprofReportStore"
import { usePagination } from "./pagination"
import { useSortFunction } from "./sortFunction"

export function useTableData() {
  const {page, paginate, totalPageCalculated, clickOnNext, clickOnPrev} = usePagination()
  const xhprofReport = useXhprofReportStore()

  //* States
  const currentPaginatedReport = computed(() => paginate(sortReportByComputed(xhprofReport.getCurrentReport ?? {})))

  const numberPage = computed(() => Math.ceil(totalPageCalculated(Object.keys(xhprofReport.getCurrentReport).length ?? 1)) ?? 1)

  const isLoading = ref(false)

  const sortBy = ref({
    key: 'wt',
    order: 'asc'
  })

  //* Functions

  const {sortByChoice} = useSortFunction()

  const clickOnSort = (key) => {
    if(key !== sortBy.value.key) {
      sortBy.value = {
        key: key,
        order: 'asc'
      }

      return
    }

    switch(sortBy.value.order) {
      case 'asc':
        sortBy.value.order = 'desc'
        break
      case 'desc':
        sortBy.value.order = null
        sortBy.value.key = null
        break
      default:
        sortBy.value.order = 'asc'
    }
  }

  const sortReportByComputed = (data) => {
    if(!sortBy.value?.key) {
      return data ?? {}
    }

    const entries = Object.entries(data)
    const sort = entries.sort((a, b) => sortByChoice(sortBy.value?.order, sortBy.value.key, a[1]['globalMetrics'], b[1]['globalMetrics']))

    return Object.fromEntries(sort)
  }

  const handleNext = () => {
    clickOnNext(numberPage.value)
  }

  const handlePrev = () => {
    clickOnPrev(numberPage.value)
  }

  // TODO: FIIR LA COMPUTED
  const headerClassComputed = (key: string) => {
    return {
      'border-b border-gray-200 p-4 pb-3 pl-8 text-left font-medium hover:text-black hover:bg-gray-100': true,
      'text-gray-600': key !== xhprofReport.getMetricFollow,
      [`bg-${key} text-black`]: key === xhprofReport.getMetricFollow
    }
  }

  const cellClassComputed = (key: string) => {
  return {
    'border-b border-gray-100 p-4 pl-8': true,
    'text-gray-500': key !== xhprofReport.getMetricFollow,
    [`bg-${key} text-black`]: key === xhprofReport.getMetricFollow
  }
  }

  onMounted(() => {
    if(Object.keys(currentPaginatedReport.value).length) {
      isLoading.value = true
    }
  })

  watch(currentPaginatedReport, () => {
    isLoading.value = true
  })

  return {
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
  }
}
