<!-- CallgraphCard.vue -->
<template>
  <div :class="['callgraph-card', { 'callgraph-card--selected': selected }]"
       :style="cardStyle"
       @click="handleClick">

    <div class="callgraph-card__header">
      <span class="callgraph-card__title" :title="fullName">{{ shortName }}</span>
    </div>

    <div class="callgraph-card__body">
      <span class="callgraph-card__value">{{ metricValue }}</span>
    </div>

    <div class="callgraph-card__footer">
      <div class="callgraph-card__bar-track">
        <div class="callgraph-card__bar-fill" :style="barFillStyle"></div>
      </div>
      <span class="callgraph-card__pct">{{ percentage }}%</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useConverter } from '@/composables/converter';
import { METRICS } from '@/constants/metricConstant';
import { useCallgraphStore } from '@/stores/UseCallgraphStore';
import { useModalStore } from '@/stores/useModalStore';
import { useXhprofReportStore } from '@/stores/UseXhprofReportStore';

const props = defineProps({
  id: {
    type: String,
    required: true
  },
  data: {
    type: Object,
    required: true
  },
  selected: {
    type: Boolean,
    default: false
  },
});

const identityCardStore = useCallgraphStore()
const modalStore = useModalStore()
const xhprofReport = useXhprofReportStore()
const { converter } = useConverter()

const metricFollow = computed(() => xhprofReport.getMetricFollow ?? 'wt')

const fullName = computed(() => props.data.identityCard.globalMetrics.key)
const shortName = computed(() => fullName.value.split('\\').at(-1) ?? fullName.value)

const percentage = computed(() =>
  (props.data.identityCard.globalMetrics[`${metricFollow.value}-total-percentage`] ?? 0).toFixed(1)
)

const metricValue = computed(() => {
  const raw = props.data.identityCard.globalMetrics[metricFollow.value]
  const metricDef = METRICS[metricFollow.value as keyof typeof METRICS]

  if (raw == null || !metricDef) {
return '—'
}

  return converter(metricFollow.value, raw)
})

/**
 * Interpole entre vert → jaune → orange → rouge selon le pourcentage.
 * Mimique le rendu "chaleur" de backfire / KCachegrind.
 */
const heatColor = (pct: number): string => {
  const p = Math.min(100, Math.max(0, pct))

  const stops = [
    { at: 0,   r: 72,  g: 187, b: 120 }, // vert
    { at: 33,  r: 236, g: 201, b: 75  }, // jaune
    { at: 66,  r: 237, g: 137, b: 54  }, // orange
    { at: 100, r: 229, g: 62,  b: 62  }, // rouge
  ]

  let low = stops[0]
  let high = stops[stops.length - 1]

  for (let i = 0; i < stops.length - 1; i++) {
    if (p >= stops[i].at && p <= stops[i + 1].at) {
      low = stops[i]
      high = stops[i + 1]
      break
    }
  }

  const t = high.at === low.at ? 0 : (p - low.at) / (high.at - low.at)
  const r = Math.round(low.r + t * (high.r - low.r))
  const g = Math.round(low.g + t * (high.g - low.g))
  const b = Math.round(low.b + t * (high.b - low.b))

  return `rgb(${r},${g},${b})`
}

const cardStyle = computed(() => {
  const pct = parseFloat(percentage.value)
  const bg = heatColor(pct)
  const isHot = pct > 50

  return {
    backgroundColor: bg,
    borderColor: heatColor(Math.min(100, pct + 20)),
    color: isHot ? '#fff' : '#1a202c',
  }
})

const barFillStyle = computed(() => ({
  width: `${Math.min(100, parseFloat(percentage.value))}%`,
  backgroundColor: parseFloat(percentage.value) > 50 ? 'rgba(255,255,255,0.5)' : 'rgba(0,0,0,0.2)',
}))

const handleClick = () => {
    identityCardStore.setDetailMetricChosen(props.data.identityCard)
    modalStore.initModal(props.data.identityCard.globalMetrics.key)
}
</script>

<style lang="scss" scoped>
.callgraph-card {
  border-radius: 6px;
  border-width: 2px;
  border-style: solid;
  width: 160px;
  height: 72px;
  display: flex;
  flex-direction: column;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
  transition: box-shadow 0.15s ease, transform 0.1s ease;
  overflow: hidden;
  z-index: 10;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    transform: translateY(-1px);
  }

  &--selected {
    box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.8);
  }

  &__header {
    padding: 4px 8px 2px;
    flex-shrink: 0;
  }

  &__title {
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
    line-height: 1.2;
  }

  &__body {
    padding: 0 8px;
    flex: 1;
    display: flex;
    align-items: center;
  }

  &__value {
    font-size: 13px;
    font-weight: 800;
    letter-spacing: -0.3px;
  }

  &__footer {
    padding: 2px 8px 4px;
    display: flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
  }

  &__bar-track {
    flex: 1;
    height: 4px;
    background: rgba(0, 0, 0, 0.15);
    border-radius: 2px;
    overflow: hidden;
  }

  &__bar-fill {
    height: 100%;
    border-radius: 2px;
    transition: width 0.3s ease;
  }

  &__pct {
    font-size: 9px;
    font-weight: 600;
    opacity: 0.85;
    min-width: 30px;
    text-align: right;
  }
}
</style>
