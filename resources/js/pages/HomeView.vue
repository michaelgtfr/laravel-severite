<template>
    <div>
        <div class="grid grid-cols-16">
            <div
                class="fixed z-10 h-20 w-full border-b border-solid border-gray-200 bg-white text-xl"
            >
                <HeaderBar />
            </div>

            <header
                class="fixed top-20 h-17/18 w-2/16 border-r border-solid border-gray-200 bg-white text-xl"
            >
                <div>
                    <nav class="p-4 text-xl font-semibold">
                        <p :class="menuClassComputed('reportList')" @click="pageStore.setPage('reportList')"><font-awesome-icon class="pr-2" icon="fa-solid fa-table" />Liste des rapports</p>
                        <p v-if="xhprofReportStore.getCurrentReport" :class="menuClassComputed('reportDetail')" @click="pageStore.setPage('reportDetail')"><font-awesome-icon class="pr-2" icon="fa-solid fa-table" />Les données</p>
                        <p v-if="xhprofReportStore.getCurrentReport" :class="menuClassComputed('callgraph')" @click="pageStore.setPage('callgraph')"><font-awesome-icon class="pr-2" icon="fa-solid fa-bezier-curve" />Callgraph</p>
                    </nav>
                </div>
                <hr v-if="pageStore.getPage === 'callgraph'" class="text-gray-200">
                <MetricSideBar v-if="pageStore.getPage === 'callgraph'" class="p-2"/>
            </header>

            <div class="relative top-20 col-start-3 col-end-17">
                <ReportList v-if="pageStore.getPage === 'reportList'" class="m-8" />
                <TableData v-if="pageStore.getPage === 'reportDetail'" class="m-8" />
                <Callgraph v-if="pageStore.getPage === 'callgraph'" />
            </div>
        </div>
        <WrapperModal />
        <NotificationModal></NotificationModal>
    </div>
</template>

<script setup lang="ts">
import Callgraph from '@/components/Callgraph.vue'
import HeaderBar from '@/components/HeaderBar.vue';
import MetricSideBar from '@/components/MetricSideBar.vue'
import NotificationModal from '@/components/NotificationModal.vue';
import ReportList from '@/components/ReportList.vue';
import TableData from '@/components/TableData.vue';
import WrapperModal from '@/components/WrapperModal.vue'
import { usePageStore } from '@/stores/UsePageStore';
import { useXhprofReportStore } from '@/stores/UseXhprofReportStore';
import { useXhprofStore } from '@/stores/UseXhprofStore';

const props = defineProps<{
    reportList: any[];
    baseUrl: string;
}>();

const pageStore = usePageStore()

const xhprofStore = useXhprofStore();
const xhprofReportStore = useXhprofReportStore();

xhprofStore.setReportList(props.reportList);
xhprofStore.setBaseUrl(props.baseUrl);

const menuClassComputed = (name: string) => {
    return {
        'p-2 rounded-lg': true,
        'bg-gray-200': name === pageStore.getPage
    }
}

</script>
