<script lang="ts" setup>
import { BaseEdge, getSmoothStepPath, Position, useVueFlow } from '@vue-flow/core'
import { useNodesInitialized } from '@vue-flow/core'
import { computed, nextTick, ref, watch } from 'vue'
import CustomMarker from './CustomMarker.vue'

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
  sourceX: {
    type: Number,
    required: true,
  },
  sourceY: {
    type: Number,
    required: true,
  },
  targetX: {
    type: Number,
    required: true,
  },
  targetY: {
    type: Number,
    required: true,
  },
  sourcePosition: {
    type: String,
    required: true,
  },
  targetPosition: {
    type: String,
    required: true,
  },
  source: {
    type: String,
    required: true,
  },
  target: {
    type: String,
    required: true,
  },
  data: {
    type: Object,
    required: true,
  },
  targetNode: {
    type: Object,
    required: true
  },
  sourceNode: {
    type: Object,
    required: true
  },
})

defineOptions({ name: `CustomEdge`})
const { onNodeDrag, getViewport, project } = useVueFlow()

//todo: ajuster la position , on peut utiliser l'id qui contien le nom de l'attribut utiliser ou cree dans le edge un attriut si ces possible

const targetX = ref(props.targetX)

const targetY = ref(props.targetY)

const targetPosition = Position.Right

const sourceX = ref(props.sourceX)

const sourceY = ref(props.sourceY)

const sourcePosition = Position.Left

const path = computed(() => getSmoothStepPath({
  ...props,
  targetX: targetX.value,
  targetY: targetY.value,
  targetPosition,
  sourceX: sourceX.value,
  sourceY: sourceY.value,
  sourcePosition
}))

const markerId = computed(() => `${props.id}-marker`)

const markerColor = '#4a5568'

const nodesInitialized = useNodesInitialized()

const markerType = computed(() => {
  //si la source peux etre nullable et que la target n'est pas unique
  const sourceData = props.sourceNode.data.value.find((source) => source.column_name === props.data.sourceName)
  const targetData = props.targetNode.data.value.find((target) => target.column_name === props.data.targetName)

  if(sourceData.is_nullable === 'YES' && (targetData.is_unique === 'NO')) {
    return 'zeroToMany'
  }

  if(sourceData.is_nullable === 'NO' && (targetData.is_unique === 'NO')) {
    return 'oneToMany'

  }

  if(sourceData.is_nullable === 'NO' && (targetData.is_unique === 'YES')) {
    return 'oneOrOne'

  }

  if(sourceData.is_nullable === 'YES' && (targetData.is_unique === 'YES')) {
    return 'zeroOrOne'

  }

  return 'unknow'
})

const positionComputed = () => {
 nextTick(() => {
    // Récupérer le conteneur du flow pour calculer l'offset
    const flowContainer = document.querySelector('.vue-flow') // ou votre sélecteur
    const containerRect = flowContainer?.getBoundingClientRect()

    const el = document.getElementById(`${props.target}-${props.data.targetName}`)

    if (el && containerRect) {
      const rects = el.getBoundingClientRect()
      //todo: le 5px est lies au marker a voi si je peux l'ameliorer pour le rendre plus dynamique
      const center = {
        // Soustraire l'offset du conteneur
        x: rects.left - containerRect.left + rects.width + 5,
        y: rects.top - containerRect.top + rects.height / 2,
      }
      const viewTarget = project(center)
      targetX.value = viewTarget.x
      targetY.value = viewTarget.y
    }

    const elSource = document.getElementById(`${props.source}-${props.data.sourceName}`)

    if (elSource && containerRect) {
      const rectsSource = elSource.getBoundingClientRect()
      const center = {
        x: rectsSource.left - containerRect.left - 5,
        y: rectsSource.top - containerRect.top + rectsSource.height / 2,
      }
      const viewSource = project(center)
      sourceX.value = viewSource.x
      sourceY.value = viewSource.y
    }
  })
}

onNodeDrag(() => {
  nextTick()
  positionComputed()
})

// todo: renomme les sourceName et targetName je pense
watch(nodesInitialized, (ready) => {
  if (ready) {
    // Ici tes nodes sont montées dans le DOM
    nextTick()
    positionComputed()
  }
})
</script>

<script lang="ts">
export default {
  inheritAttrs: false,
}
</script>

<template>
  <BaseEdge
    :id="id"
    :path="path[0]"
    :marker-end="`url(#${markerId})`"
    :marker-start="`url(#${markerId})`"
    :label-x="path[1]"
    :label-y="path[2]"
  />

  <CustomMarker
    :id="markerId"
    :type="markerType"
    :stroke="markerColor"
    :stroke-width="2"
    :width="20"
    :height="20"
    />
</template>
