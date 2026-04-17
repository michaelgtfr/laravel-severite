<template>
    <Transition name="slide-fade">
        <div
            v-if="notificationModal.showComputed"
            class="notification-modal absolute right-0 bottom-[50%] h-auto min-h-40 w-80 bg-white rounded-l-2xl text-center content-center border border-gray-200"
        >
            <p>{{ notificationModal.messageComputed }}</p>
        </div>
        </Transition>
</template>
<script lang="ts" setup>
import { useNotificationModalStore } from '@/stores/UseNotificationModal.Store';

defineOptions({ name: `NotificationModal` })

const notificationModal = useNotificationModalStore()

</script>

<style lang="scss" scoped>
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.8s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateX(20px);
  opacity: 0;
}
</style>
