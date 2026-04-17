<script lang="ts" setup>
defineOptions({ name: `CustomMarker`})

defineProps({
  id: {
    type: String,
    required: true,
  },
  type: {
    type: String,
    required: true,
  },
  stroke: {
    type: String,
    required: false,
    default: '#b1b1b7',
  },
  fill: {
    type: String,
    required: false,
    default: '#b1b1b7',
  },
  strokeWidth: {
    type: Number,
    required: false,
    default: 1,
  },
  width: {
    type: Number,
    required: false,
    default: 12.5,
  },
  height: {
    type: Number,
    required: false,
    default: 12.5,
  },
})

</script>
<template>
  <svg class="vue-flow__marker vue-flow__container">
    <defs>
      <!-- zeroToMany => si null avec lien sans unicite -->
      <marker
      v-if="type === 'zeroToMany'"
      :id="id"
      class="vue-flow__arrowhead"
      viewBox="-15 -15 30 30"
      refX="0"
      refY="0"
      :markerWidth="width"
      :markerHeight="height"
      markerUnits="strokeWidth"
      orient="auto-start-reverse"
      >
      <circle cx="-10" cy="0" r="5" stroke="gray" fill="transparent" stroke-width="1"/>
      <line  x1="-6" y1="0" x2="20" y2="-15" stroke="gray" fill="transparent" stroke-width="1" />
      <line  x1="-6" y1="0" x2="20" y2="15" stroke="gray" fill="transparent" stroke-width="1" />
      <line  x1="-6" y1="0" x2="20" y2="0" stroke="gray" fill="transparent" stroke-width="1" />
      <line  x1="-6" y1="0" x2="-14" y2="0" stroke="#f3f4f6" fill="transparent" stroke-width="2" />
    </marker>
    <!-- oneToMany => si non null avec lien sans unicite -->
    <marker
    v-if="type === 'oneToMany'"
    :id="id"
    class="vue-flow__arrowhead"
    viewBox="-15 -15 30 30"
    refX="0"
    refY="0"
    :markerWidth="width"
    :markerHeight="height"
    markerUnits="strokeWidth"
    orient="auto-start-reverse"
    >
        <line  x1="-15" y1="15" x2="-15" y2="-15" stroke="gray" fill="transparent" stroke-width="1" />
        <line  x1="-15" y1="0" x2="20" y2="-15" stroke="gray" fill="transparent" stroke-width="1" />
        <line  x1="-15" y1="0" x2="20" y2="15" stroke="gray" fill="transparent" stroke-width="1" />
        <line  x1="-15" y1="0" x2="20" y2="0" stroke="gray" fill="transparent" stroke-width="1" />
        <line  x1="-15" y1="0" x2="-14" y2="0" stroke="#f3f4f6" fill="transparent" stroke-width="2" />
      </marker>
      <!-- OneToOne => si non null et lien d'unicite -->
      <marker
        v-if="type === 'oneOrOne'"
        :id="id"
        class="vue-flow__arrowhead"
        viewBox="-15 -15 30 30"
        refX="0"
        refY="0"
        :markerWidth="width"
        :markerHeight="height"
        markerUnits="strokeWidth"
        orient="auto-start-reverse"
      >
        <line  x1="-15" y1="15" x2="-15" y2="-15" stroke="gray" fill="transparent" stroke-width="1" />
        <line  x1="-6" y1="15" x2="-6" y2="-15" stroke="gray" fill="transparent" stroke-width="1" />
        <line  x1="-6" y1="0" x2="20" y2="0" stroke="gray" fill="transparent" stroke-width="1" />
      </marker>
      <!-- zeroToOne => si null et lien d'unicite -->
      <marker
      v-if="type === 'zeroOrOne'"
      :id="id"
      class="vue-flow__arrowhead"
      viewBox="-15 -15 30 30"
      refX="0"
      refY="0"
      :markerWidth="width"
      :markerHeight="height"
      markerUnits="strokeWidth"
      orient="auto-start-reverse"
      >
      <circle cx="-10" cy="0" r="5" stroke="gray" fill="transparent" stroke-width="1"/>
      <line  x1="0" y1="15" x2="0" y2="-15" stroke="gray" fill="transparent" stroke-width="1" />
      <line  x1="-6" y1="0" x2="20" y2="0" stroke="gray" fill="transparent" stroke-width="1" />
    </marker>
    <marker
      v-else
      :id="id"
      class="vue-flow__arrowhead"
      viewBox="-15 -15 30 30"
      refX="0"
      refY="0"
      :markerWidth="width"
      :markerHeight="height"
      markerUnits="strokeWidth"
      orient="auto-start-reverse"
      >
      <line  x1="-6" y1="0" x2="20" y2="0" stroke="gray" fill="transparent" stroke-width="1" />
    </marker>
    </defs>
  </svg>
</template>
