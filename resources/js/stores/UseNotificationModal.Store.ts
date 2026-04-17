import { defineStore } from "pinia";
import { computed, ref } from "vue";

export const useNotificationModalStore = defineStore('notificationStore', () => {
    //* State
    const show = ref<boolean>(false)

    const message = ref<string | null>(null)


    //* Getter
    const showComputed = computed(() => show.value)

    const messageComputed = computed(() => message.value)

    //* Action
    const displayNotification = (mes: string) => {
        console.log(mes)
        message.value = mes
        show.value = true

        setTimeout(() => {
            console.log('falsez')
            show.value = false
        }, 1000)
    }

    return {
        showComputed,
        messageComputed,
        displayNotification
    }
})
