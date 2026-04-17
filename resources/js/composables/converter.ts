import { METRICS } from '@/constants/metricConstant'

export function useConverter() {
  const converter = (key, value) => {
    return `${value / METRICS[key].divided} ${METRICS[key].unit}`
  }

  return {
    converter,
  }
}
