import { ref } from 'vue'

export function useSortFunction() {
  const listFunctionFiltered = ref([])
  const listFunctionSorted = ref([])
  const followListSorted = ref([])
  /**
   * trie toutes les donnees du plus grand au plus petit, cle wt
   */
  const sortFunctionnality = (listeOfFunction) => {
    const listFunctionFiltered = []

    for (const cle in listeOfFunction) {
      listeOfFunction[cle].key = cle
      listFunctionFiltered.push(listeOfFunction[cle])
    }

    return listFunctionFiltered.sort((a, b) => sortByAsc(a.wt, b.wt))
  }

  /**
   *  Filtre les element en commençant par le nom de fonction données en filtrant par parent enfant
   * @returns
   */
  const filterByParentChildByFunction = (listFunctionFilter, startFunctionnality = null) => {
    listFunctionFiltered.value = listFunctionFilter

    sortParentChildrenRecursive(startFunctionnality)

    return listFunctionSorted.value
  }

    const sortParentChildrenRecursive = (functionalityCurentKey) => {
        if (
            !followListSorted.value[functionalityCurentKey]
        ) {
            const element = listFunctionFiltered.value[functionalityCurentKey]
            listFunctionSorted.value[functionalityCurentKey] =element
            followListSorted.value.push(functionalityCurentKey)

            element.childFunction.forEach(child => {
                sortParentChildrenRecursive(child)
            })
        }
    }

  const filterIdentityCardsByPercentage = (
    currentReport,
    keyOfData: string,
    percentage: number,
  ) => {
    const listFunctionFiltered = []

    for (const cle in currentReport) {
      if (currentReport[cle].globalMetrics[`${keyOfData}-total-percentage`] > percentage) {
        currentReport[cle].globalMetrics.key = cle
        listFunctionFiltered.push(currentReport[cle])
      }
    }

    return listFunctionFiltered
  }

  const sortByAsc = (a, b) => {
    if (a < b) {
      return 1
    }

    if (a > b) {
      return -1
    }

    return 0
  }

  const sortByDesc = (a, b) => {
    if (a > b) {
      return 1
    }

    if (a < b) {
      return -1
    }

    return 0
  }

  const sortByChoice = (order, keyChosen, a, b) => {
    return order === 'desc'
      ? sortByDesc(a[keyChosen], b[keyChosen])
      : sortByAsc(a[keyChosen], b[keyChosen])
  }

  return {
    sortFunctionnality,
    filterByParentChildByFunction,
    sortByAsc,
    sortByChoice,
    listFunctionFiltered,
    listFunctionSorted,
    filterIdentityCardsByPercentage,
  }
}
