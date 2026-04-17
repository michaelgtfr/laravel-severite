<template>
  <div class="p-2 overflow-auto w-full h-16/18">
    <CallgraphSelect class="pb-4" />
  </div>
</template>

<script lang="ts" setup>
import CallgraphSelect from '@/components/CallgraphSelect.vue'

defineOptions({ name: 'metricSideBar' })

</script>
