<template>
    <div :class="cardClassComputed">
        <font-awesome-icon class="size-4 shrink-0" :icon="iconComputed" />
        <div>
            <p class="text-xl text-gray-500">{{ props.value }}</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, defineOptions, defineProps } from 'vue';
import { useXhprofReportStore } from '@/stores/UseXhprofReportStore';

//TODO: renommer en bouton
defineOptions({
    name: `Card`,
});

const props = defineProps({
    name: {
        type: String,
        required: true,
    },
    value: {
        type: String,
        required: true,
    },
    img: {
        type: String,
        required: true,
    },
    backgroundColor: {
        type: String,
    },
    acronym: {
        type: String,
        default: null,
    },
});

const xhprofReport = useXhprofReportStore();

const cardClassComputed = computed(() => {
    return {
        'mx-auto w-3xs flex items-center gap-x-4 rounded-xl p-1 outline outline-gray-200 justify-center': true,
        [`bg-${props.acronym}`]: xhprofReport.getMetricFollow === props.acronym,
    };
});

const iconComputed = `fa-solid ${props.img}`;
</script>
