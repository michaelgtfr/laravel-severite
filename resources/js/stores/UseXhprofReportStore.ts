import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { FunctionOfReport } from '@/interfaces/report'
import { useXhprofRepository } from '@/repositories/UseXprofRepository'
import { MetricService } from '@/services/MetricService'
import { useCallgraphStore } from './UseCallgraphStore'
import { useXhprofStore } from './UseXhprofStore'

export const useXhprofReportStore = defineStore('xhprofReport', () => {
  const xhprofStore = useXhprofStore()
  const xhprofRepository = useXhprofRepository()
  const callgraphStore = useCallgraphStore()

  //* State
  const currentReport = ref<Record<string, FunctionOfReport> | null>(null)

  const mainFunction = ref<FunctionOfReport | null>(null)

  const reportName = ref<string | null>(null)

  const currentReportManaged = ref<Record<string, FunctionOfReport> | null>(null)

  const metricFollow = ref<string | null>(null)

  //* Getters
  const getMainFunction = computed(() => mainFunction.value)

  const getCurrentReport = computed(() => currentReport.value)

  const getCurrentReportManaged = computed(() => currentReportManaged.value)

  const getMetricFollow = computed(() => metricFollow.value)

  //* setters
  const setCurrentReport = (report: Record<string, FunctionOfReport>) => {
    currentReport.value = report
  }

  const setMainFunction = (metric: FunctionOfReport) => {
    mainFunction.value = metric
  }

  const setMetricFollow = (value: string | null) => {
    metricFollow.value = value
  }
  const setReportName = (reportNameChosen: string) => {
    reportName.value = reportNameChosen
  }

    const handleReportName = async (event) => {
        let value = null

        if (typeof event == 'string') {
          value = event
        } else {
            value = event.target.value
        }

        setReportName(value)
        initReport(value)
    }

  const initReport = async (name: string) => {
    const reportId = xhprofStore.getReportList?.find(report => report.title === name).id

    const response = await xhprofRepository.fetchReport(reportId)

    const report = response

    const firstMetric = MetricService.theHighestInObject(report, 'mu')

    setCurrentReport(report)

    setMainFunction({ ...report[firstMetric], key: firstMetric })

    callgraphStore.setMethodChosen(mainFunction.value.key)
  }

    const deleteReport = async (reportId: string) => {
        const response = await xhprofRepository.deleteReport(reportId)
        //todo: ajouter un message de notification et ré-actualiser les données
    }

  return {
    //state
    currentReport,
    mainFunction,
    reportName,
    currentReportManaged,
    metricFollow,

    //getters
    getMainFunction,
    getCurrentReport,
    getCurrentReportManaged,
    getMetricFollow,

    //setter
    setCurrentReport,
    setMainFunction,
    setMetricFollow,

    //action
    handleReportName,
    deleteReport
  }
})
