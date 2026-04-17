<template>
    <div
        v-if="modalStore.getShowModal"
        class="modal rounded-t-xl border-x border-t border-gray-300"
    >
        <div
            class="modal__header flex justify-between rounded-t-xl px-2 font-bold"
        >
            <h3 v-if="modalStore.getTitleModal">
                {{ modalStore.getTitleModal }}
            </h3>
            <font-awesome-icon
                class="h-4 self-center"
                icon="fa-solid fa-xmark"
                @click="modalStore.onClose()"
            />
        </div>
        <div class="modal__body">
            <div
                class="flex flex-col"
                v-if="callgraphStore.getDetailMetricChosen"
            >
                <!-- first part, parent function-->
                <div class="modal__body--header mb-2 flex w-full">
                    <h2 class="text-md w-[25%] p-2 text-center font-bold">
                        Fonction Parent
                    </h2>
                    <h2
                        class="text-md w-[50%] self-center p-2 text-center font-bold"
                    >
                        Données de la fonction
                    </h2>
                    <h2 class="text-md w-[25%] p-2 text-center font-bold">
                        Fonctions enfant
                    </h2>
                </div>

                <div class="modal__body--body flex w-full">
                    <div class="flex w-[25%] flex-col items-center">
                        <div
                            class="overflow-hidden rounded border text-sm text-ellipsis font-medium p-1 bg-orange-400"
                            v-for="(parentName, index) in callgraphStore
                                .getDetailMetricChosen.parentFunction"
                            :key="index"
                            @click="handleClick(parentName)"
                        >
                            <p>{{ parentName }}</p>
                        </div>
                    </div>

                    <!-- second part, metrics -->
                    <div class="flex w-[50%] flex-col">
                        <div
                            class="border-x border-gray-300 p-2 text-sm flex"
                            v-for="(key, index) in dataHeader"
                            :key="index"
                        >
                            <p class="font-bold w-[20%]">{{ key }}</p>
                            <div v-if="key !== 'ct'" class="w-[60%] modal__body__measure-wrapper">
                                <div class="modal__body__measure flex" :style="measureStyleComputed(callgraphStore.getDetailMetricChosen.globalMetrics[`${key}-total-percentage`])">
                                    <div class="modal__body__measure-incl" :style="measureStyleComputed(callgraphStore.getDetailMetricChosen.globalMetrics[`${key}-total-percentage`] - callgraphStore.getDetailMetricChosen.globalMetrics[`${key}-excl-percentage`])" />
                                    <div class="modal__body__measure-excl" :style="measureStyleComputed(callgraphStore.getDetailMetricChosen.globalMetrics[`${key}-total-percentage`])"/>
                                </div>
                            </div>
                            <div v-if="key === 'ct'">
                                <p>{{ callgraphStore.getDetailMetricChosen.globalMetrics[key] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- third part, child function -->
                    <div class="w-[25%] flex-col items-center gap-2 flex">
                        <div
                            class="w-[90%] overflow-hidden rounded border text-sm text-ellipsis font-medium p-1 bg-orange-400"
                            v-for="(childName, index) in callgraphStore
                                .getDetailMetricChosen.childFunction"
                            :key="index"
                            @click="handleClick(childName)"
                        >

                            <p>{{ childName }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else>
                <p>Pas de données disponible</p>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { useCallgraphStore } from '@/stores/UseCallgraphStore';
import { useModalStore } from '@/stores/useModalStore';
import { useXhprofReportStore } from '@/stores/UseXhprofReportStore';

defineOptions({ name: `WrapperModal` });

const modalStore = useModalStore();
const callgraphStore = useCallgraphStore();
const xhprofStore = useXhprofReportStore()

const dataHeader = ['ct', 'wt', 'cpu', 'mu', 'pmu']

const measureStyleComputed = (value: string | number) => {
    return { width: `${value}%` }
}

const handleClick = (nameFunctionChosen: string) => {
    const identity = xhprofStore.getCurrentReport[nameFunctionChosen]
    callgraphStore.setDetailMetricChosen(identity)
    modalStore.initModal(nameFunctionChosen)
}

</script>

<style lang="scss" scoped>
.modal {
    position: fixed;
    bottom: 0;
    left: 15%;
    background-color: white;
    width: 70%;
    height: auto;
    max-height: 70%;
    min-height: 25%;
    z-index: 10;

    &__header {
        color: white;
        background-color: rgba(26, 32, 44, 0.75);
    }

    &__body {
        overflow: auto;

        &--header {
            background-color: #e5e7eb;
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

        &__measure-wrapper {
            background-color: #e5e7eb;
            vertical-align: middle;
            display: inline-flex;
            height: 15px;
            border-radius: 5px;
        }
    }

}
</style>
