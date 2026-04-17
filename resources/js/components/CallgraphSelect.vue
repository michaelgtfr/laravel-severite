<template>
<div>
  <label for="price" class="block text-sm/6 font-semibold text-gray-900">Méthode de commencement</label>
  <select class="w-full rounded-sm p-1 outline outline-gray-300 text-sm/6" name="method" v-model="identityCartStore.methodChosen">
    <option class="text-sm/6" v-for="value, index in identityCartStore.getListMethodOfReport" :key="index">{{ value }}</option>
  </select>
  <CallgraphList />
</div>
</template>

<script lang="ts" setup>
import { useCallgraphStore } from '@/stores/UseCallgraphStore';
import CallgraphList from './CallgraphList.vue';

defineOptions({name: `CallgraphSelect`})

const identityCartStore = useCallgraphStore()
</script>
