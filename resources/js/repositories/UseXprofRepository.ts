import { useNotificationModalStore } from "@/stores/UseNotificationModal.Store"
import { useXhprofStore } from "@/stores/UseXhprofStore"

export function useXhprofRepository() {
    const xhprofStore = useXhprofStore()

    const fetchReportList = async () => {

    const response = await fetch(`${xhprofStore.baseUrl}/`)

    if(!response.ok) {
      throw new Error(`probleme with report request: ${response.status}`)
    }

    return await response.json()
  }

  const fetchReport = async (reportId: string) => {
    const response = await fetch(`${xhprofStore.baseUrl}/${reportId}`)

    if(!response.ok) {
      throw new Error(`probleme with report request: ${response.status}`)
    }

    return await response.json()
  }

    const deleteReport = async (reportId: string) => {
        const response = await fetch(`${xhprofStore.baseUrl}/${reportId}`, {
            method: 'DELETE'
        })

        if(!response.ok) {
            throw new Error(`probleme with report request: ${response.status}`)
        }

        useNotificationModalStore().displayNotification('Suppression effectué')
    }

  return {
    fetchReportList,
    fetchReport,
    deleteReport
  }
}
