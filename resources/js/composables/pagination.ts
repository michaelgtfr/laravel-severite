import { ref } from 'vue'

export function usePagination() {
  const page = ref<number>(1)

  const NUMBER_BY_PAGE = 15

  const paginate = (objectPaginated: object) => {
    const entries = Object.entries(objectPaginated)

    const offset = (page.value - 1) * NUMBER_BY_PAGE
    const limit = page.value * NUMBER_BY_PAGE

    const slice = entries.slice(offset, limit)

    return Object.fromEntries(slice)
  }

  const totalPageCalculated = (numberElement: number) => {
    return numberElement / NUMBER_BY_PAGE
  }

  const clickOnNext = (numberPage) => {
    if (page.value >= numberPage) {
      return
    }

    page.value = page.value + 1
  }
  const clickOnPrev = (numberPage) => {
    if (1 === numberPage || page.value === 1) {
      return
    }

    page.value = page.value - 1
  }

  return {
    page,
    paginate,
    totalPageCalculated,
    clickOnNext,
    clickOnPrev,
  }
}
