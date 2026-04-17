import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export const useModalStore = defineStore('modalStore', () => {
  // *  States
  const showModal = ref(false)

  const titleModal = ref<null | string>(null)

  // * Getters
  const getShowModal = computed(() => showModal.value)

  const getTitleModal = computed(() => titleModal.value)

  //* Actions
  const initModal = (title: string) => {
    titleModal.value = title
    showModal.value = true
  }

  const onClose = () => {
    showModal.value = false
    titleModal.value = null
  }

  return {
    getShowModal,
    getTitleModal,
    initModal,
    onClose
  }
})
