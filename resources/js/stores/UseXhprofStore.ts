import { defineStore } from "pinia"
import { computed, ref } from "vue"
import { useXhprofRepository } from "@/repositories/UseXprofRepository"

export const useXhprofStore = defineStore('xhprof', () => {
  const xhprofRepository = useXhprofRepository()
  const baseUrl = ref<string>('')

  //* State
  const reportList = ref<object[] | null>(null)

  //* Getter
  const getReportList = computed(() => reportList.value)

  const getReportListForSelect = computed(() => reportList.value?.map(report => report.title))

  //* Setter
  const setReportList = (value: object[]) => {
    reportList.value = value
  }

    const setBaseUrl = (value: string) => {
        baseUrl.value = value
    }

  //* Actions
  const fetchReportList = async () => {
    const response = await xhprofRepository.fetchReportList()
    setReportList(response)
  }


  return {
      getReportList,
      baseUrl,
    getReportListForSelect,
    fetchReportList,
    setReportList,
    setBaseUrl
  }
})
