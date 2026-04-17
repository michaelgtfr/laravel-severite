<template>
  <!-- <GeneralBarCallgraph  :totalMetric="xhprofReport.getMainFunction" :metricDisplayed="'wt'"/> -->
  <div class="callgraph-wrapper" id="callgraph">
    <VueFlow
    v-if="isLoading"
    :nodes="nodes"
    :edges="edges"
    :node-types="nodeTypes"
    class="basic-flow"
    >
      <Background pattern="dots" gap="20" />
    </VueFlow>
    <Spinner v-else />
  </div>
</template>
<script setup lang="ts">
import { Background } from '@vue-flow/background'
import type { Node, Edge } from '@vue-flow/core'
import { VueFlow, MarkerType } from '@vue-flow/core'
import { computed, onMounted, ref, watch } from 'vue';
import Spinner from '@/components/Spinner.vue'
import { useSortFunction } from '@/composables/sortFunction';
import { useCallgraphStore } from '@/stores/UseCallgraphStore';
import { useXhprofReportStore } from '@/stores/UseXhprofReportStore';
import type { IdentityCard } from '@/types/callgraph';
import CallgraphCard from './CallgraphCard.vue';

defineOptions({
  name: `Callgraph`
})

const isLoading = ref(false)

const xhprofReport = useXhprofReportStore()
const sortFunction = useSortFunction()
const identityCards = useCallgraphStore()

const listFunction = computed(() => xhprofReport.getCurrentReport)

const identityCardsFiltered = ref<IdentityCard[]>([])

const metricFollow = computed(() => xhprofReport.getMetricFollow ?? 'wt')

const grid = ref(null)

const nodeTypes = {
  custom: CallgraphCard,
}

// these are our nodes
const nodes = ref<Node[]>([])

// Espacement entre les cartes (en pixels). Les cartes font 160×72 (voir CallgraphCard.vue).
const NODE_W = 160
const NODE_H = 72
const H_GAP  = 60
const V_GAP  = 90

const nodeFunction = () => {
  const icByKey = new Map<string, IdentityCard>(
    identityCardsFiltered.value.map((ic: IdentityCard) => [ic.globalMetrics.key, ic])
  )

  for (const y in grid.value.grid) {
    const keysInLayer: string[] = grid.value.grid[y]

    keysInLayer.forEach((key) => {
      if (!key) {
        return
      }

      const identityCard = icByKey.get(key)

      if (!identityCard) {
        return
      }

      const xposition = identityCard.callgraph.xPosition * (NODE_W + H_GAP)
      const yposition = identityCard.callgraph.yPosition * (NODE_H + V_GAP)

      nodes.value.push({
        id: key,
        type: 'custom',
        position: { x: xposition, y: yposition },
        data: { identityCard },
      })
    })
  }
}

// these are our edges
const edges = ref<Edge[]>([])

const edgesFunction = () => {
    //on boucle on regarde si il a des parent si il en as pas on fait rien
    identityCardsFiltered.value.forEach(identityCard => {
        if(!identityCard.parentFunction) {
 return
}

        identityCard.parentFunction.forEach(parent => {
          //sinon on creer du parent (source) un lien juste la fonction courant (target, currentfunction)
          //pas de label pour la moment
          //et on envoie
          edges.value.push({
            id: `${parent}-${identityCard.globalMetrics.key}`,
            source: parent,
            target: identityCard.globalMetrics.key,
            markerEnd: MarkerType.ArrowClosed,
            label: `×${identityCard.globalMetrics['ct']}`,
            labelBgStyle: { fill: '#1a202c', fillOpacity: 0.75, rx: 4, ry: 4 },
            labelStyle: { fontWeight: 700, fontSize: '10px', fill: '#fff' },
            style: { stroke: '#718096', strokeWidth: 1.5 },
          })
        });
    });
}


const initCallgraph = (startFunction: string | null = null) => {
      isLoading.value = false

    //* recuperer un filtre des identityCard à 1% min

    //todo:  filtre par nom du startfunction
    const report = ref(null)

    if (startFunction && xhprofReport.getMainFunction?.key !== startFunction) {
        report.value = sortFunction.filterByParentChildByFunction(xhprofReport.getCurrentReport, startFunction)
    } else {
        report.value = xhprofReport.getCurrentReport
    }

    identityCardsFiltered.value = sortFunction.filterIdentityCardsByPercentage(report.value, metricFollow.value, 1)

      identityCards.setIdentityCardCallgraph(identityCardsFiltered.value)

      //gerer les positions
      grid.value = identityCards.hydrateIdentityCardWithPosition(identityCardsFiltered.value)

      edges.value = []
      nodes.value = []
      nodeFunction()
      edgesFunction()

      isLoading.value = true
}

onMounted(() => {
  if(!xhprofReport.getMainFunction) {
    return
  }

  initCallgraph()
})

watch(() => metricFollow.value, () => {
  initCallgraph()
})

watch(() => identityCards.getMethodChosen, () => {
  initCallgraph( identityCards.getMethodChosen)
})
</script>

<style lang="scss" scoped>
.flex {
  display: flex;
  justify-content: space-around;
}

.grid {
  width: 500px;
  height: 200px;
  padding: 5px;
  overflow: hidden;
  word-wrap: break-word;
  align-content: center;
  justify-content: center;
}

.basic-flow ,.callgraph-wrapper {
    height:100vh;
}
</style>
