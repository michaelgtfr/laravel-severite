<template>
    <div class="callgrapth_list pt-3 mt-3">
        <div class="flex callgrapth_list__menu gap-1 text-xs p-1">
                <p class="w-[30%]">Name</p>
                <p class="w-[60%]">Incl%/ Excl%</p>
                <p class="w-[10%]">Calls</p>
        </div>
        <div class="flex gap-1 text-xs p-1" :style="backgroundColorComputed(index)" v-for="identity, index in identityCardSorted" :key="index" @click="handleClick(identity)">
            <div class="w-[30%] flex overflow-hidden text-ellipsis">
                <p>{{identity.globalMetrics.key}}</p>
            </div>
            <div class="w-[60%]">
                <div class="callgrapth_list__measure flex" :style="measureStyleComputed(identity.globalMetrics['wt-total-percentage'])">
                    <div class="callgrapth_list__measure-incl" :style="measureStyleComputed(identity.globalMetrics['wt-total-percentage'] - identity.globalMetrics['wt-excl-percentage'])" />
                    <div class="callgrapth_list__measure-excl" :style="measureStyleComputed(identity.globalMetrics['wt-excl-percentage'])"/>
                </div>
            </div>
            <div class="w-[10%] flex justify-center">
                <p>{{identity.globalMetrics.ct}}</p>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue';
import { useCallgraphStore } from '@/stores/UseCallgraphStore';
import { useModalStore } from '@/stores/useModalStore';

defineOptions({ name: 'CallgraphList' })

const callgraphStore = useCallgraphStore()
const modalStore = useModalStore()

const measureStyleComputed = (value: string | number) => {
    return { width: `${value}%` }
}

const backgroundColorComputed = (index) => {
    const color = index % 2 === 0 ? 'width' : '#e5e7eb'

    return { "background-color":  color}
}

const sortByTotalPercentage = (a, b) => {
    if (a.globalMetrics['wt-total-percentage'] > b.globalMetrics['wt-total-percentage']) {
        return - 1
    }

    return 1
}

const identityCardSorted = computed(() => {
    if (callgraphStore.getIdentityCardCallgraph.length) {
        return callgraphStore.getIdentityCardCallgraph.sort(sortByTotalPercentage)
    }

    return []
})

const handleClick = (identity) => {
    callgraphStore.setDetailMetricChosen(identity)
    modalStore.initModal(identity.globalMetrics.key)
}

</script>

<style lang="scss" scoped>
.callgrapth_list {
    font-weight: 600;

    &__menu {
        color: white;
        background-color: rgba(26, 32, 44, 0.75);
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
    }

    &__measure {
        vertical-align: middle;
        display: inline-flex;
        height: 15px;
        background-color: rgb(239, 214, 214);
        border-radius: 5px;
    }

    &__measure-incl {
        background-color: red;
        height: 100%;
        border-top-left-radius: 5px;
        border-bottom-left-radius: 5px;
    }

    &__measure-excl {
        height: 100%;
        border-top-left-radius: 5px;
        border-bottom-left-radius: 5px;
    }
}

</style>
