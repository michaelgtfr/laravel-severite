<template>
  <div class="spinner justify-center">
    <div class="square square--solid "></div>
    <div class="square square--regular"></div>
    <p class="spinner__text">Loading...</p>
  </div>
</template>

<script lang="ts" setup>

defineOptions({
  name: `Spinner`
})

</script>

<style lang="scss" scoped>

.spinner {
  position: absolute;
  top: 50%;
  left: 50%;

  &__text {
    color: #b145b1;
    font-weight: bold;
    font-size: 16px;
    position: relative;
    top: -50px
  }

  .square {
      width: 80px;
      height: 80px;     /* Cercle */

    &--solid {
      animation: spin-solid 2s linear infinite;
      background-color: #b145b1;
    }
    &--regular {
      animation: spin-regular 2s linear infinite;
      border: 4px solid #b145b1;               /* Couleur de base */
      position: relative;
      top: -80px
    }
  }
}

@keyframes spin-solid {
  0%   { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes spin-regular {
  0%   { transform: rotate(0deg); }
  100% { transform: rotate(-360deg); }
}
</style>
