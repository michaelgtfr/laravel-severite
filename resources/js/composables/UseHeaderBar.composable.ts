import { computed } from "vue"
import { useXhprofReportStore } from "@/stores/UseXhprofReportStore"

export function useHeaderBar() {
  const xhprofReportStore = useXhprofReportStore()

  //* State
  const totalMetric = computed(() => xhprofReportStore.getMainFunction['globalMetrics'])

  //* Functions
  const handleMetric = (key: string) => {
    if(xhprofReportStore.getMetricFollow === key) {
      xhprofReportStore.setMetricFollow(null)

      return
    }

    xhprofReportStore.setMetricFollow(key)
  }

  return {
    totalMetric,
    handleMetric
  }
}
