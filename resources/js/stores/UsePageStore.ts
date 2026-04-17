import { defineStore } from "pinia"
import { computed, ref } from "vue"

export const usePageStore = defineStore('pageStore', () => {
    const pageAsked = ref('reportList')

    const setPage = (page: string) => {
        pageAsked.value = page
    }

    const getPage = computed(() => pageAsked.value)

    return {
        getPage,
        setPage
    }
})
