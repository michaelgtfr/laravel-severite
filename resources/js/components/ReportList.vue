<template>
    <div>
        <h1 class="font-bold text-3xl mb-8">Listes des rapports</h1>
        <div class="bg-white p-2 m-2 flex hover:shadow-xl justify-between"
            v-for="report, index in xhprofStore.getReportList"
            :key="index" @click="handleClick(report.title)"
            @mousemove="handleHover( index)"
            @mouseleave="cleanHover()"
        >
            <div class="flex text-left w-[70%] justify-between">
                <div class="flex gap-5">
                    <p class="text-lg font-semibold self-end">{{ report.title }}</p>
                    <p class="italic self-end">{{ report.tag }}</p>
                </div>
                <p class="italic text-xs self-end">2026-04-01 13:18:00</p>
            </div>
            <div class="flex gap-5 w-[30%] justify-end">
                <p class="p-1 text-white font-semibold bg-[#ffa200] rounded">wt: {{ converter('wt', report.wall_time) }}</p>
                <p class="p-1 text-white font-semibold  bg-[#92af77] rounded">cpu: {{ converter('cpu', report.central_processing_unit) }}</p>
                <p class="p-1 text-white font-semibold  bg-[#A2BFFE] rounded">mu: {{ converter('mu', report.memory_usage) }}</p>
                <p class="p-1 text-white font-semibold  bg-[#D7707E] rounded">pmu: {{ converter('pmu', report.peak_memory_usage) }}</p>
            </div>
            <div class="flex" v-if="index === getHoverComputed">
                <font-awesome-icon @click.stop="deleteReport(report.id)" class="p-2 ml-5 rounded-full bg-red-500 text-white" icon="fa-solid fa-trash-can" />
            </div>
        </div>
    </div>
</template>
<script setup lang="ts" scoped>
import { computed, ref } from 'vue';
import { useConverter } from '@/composables/converter';
import { usePageStore } from '@/stores/UsePageStore';
import { useXhprofReportStore } from '@/stores/UseXhprofReportStore';
import { useXhprofStore } from '@/stores/UseXhprofStore';

defineOptions({ name: `ReportList` })

const xhprofStore = useXhprofStore()
const { converter } = useConverter()
const xhprofReportStore = useXhprofReportStore();
const pageStore = usePageStore()

const hoverActivatedIndex = ref<number | null>(null)

const getHoverComputed = computed(() => hoverActivatedIndex.value ?? null)

const handleClick = (report) => {
    xhprofReportStore.handleReportName(report)
    pageStore.setPage('callgraph')
}

const handleHover = (index: number) => {
    hoverActivatedIndex.value = index
}

const cleanHover = () => {
    hoverActivatedIndex.value = null
}

const deleteReport = (reportId: string) => {
    xhprofReportStore.deleteReport(reportId)
}

</script>
