<template>
    <div class="grid grid-cols-12">
        <div class="col-span-2 flex h-20 items-center p-7 text-xl font-bold">
            <h1>Laravel Sévérité</h1>
        </div>
        <div
            v-if="xhprofReportStore.getMainFunction && totalMetric"
            class="col-span-8 flex items-center justify-center gap-4"
        >
            <div
                v-for="(key, index) in Object.keys(PRINCIPAL_METRICS)"
                :key="index"
            >
                <Card
                    :name="PRINCIPAL_METRICS[key].fullName"
                    :value="converter(key, totalMetric[key])"
                    :img="PRINCIPAL_METRICS[key].icon"
                    :backgroundColor="PRINCIPAL_METRICS[key].backgroundColor"
                    :acronym="key"
                    @click="handleMetric(key)"
                />
            </div>
        </div>
        <div
            class="col-span-2 col-start-11 flex items-center p-7 text-xl font-semibold"
        >
            <select
                class="w-full rounded-sm p-1 text-sm/6 outline outline-gray-300"
                name="method"
                v-model="xhprofReportStore.reportName"
            >
                <option
                    class="text-sm/6"
                    v-for="(value, index) in xhprofStore.getReportListForSelect"
                    :key="index"
                    @click="xhprofReportStore.handleReportName"
                >
                    {{ value }}
                </option>
            </select>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useConverter } from '@/composables/converter';
import { useHeaderBar } from '@/composables/UseHeaderBar.composable';
import { PRINCIPAL_METRICS } from '@/constants/metricConstant';
import { useXhprofReportStore } from '@/stores/UseXhprofReportStore';
import { useXhprofStore } from '@/stores/UseXhprofStore';
import Card from './Card.vue';

defineOptions({
    name: 'HeaderBar',
});

const xhprofStore = useXhprofStore();
const xhprofReportStore = useXhprofReportStore();

const { converter } = useConverter();

const { totalMetric, handleMetric } = useHeaderBar();
</script>
