<template>
  <swiper 
  :modules="modules" 
    :autoplay="{ delay: 0.5, disableOnInteraction: false }" 
    :loop="true" 
    :free-mode="true" 
    :a11y="false"
    :speed="11000" 
    :breakpoints="{
      0: { /* when window >=0px - webflow mobile landscape/portriat */
       spaceBetween: 30,
       slidesPerView: 4
     },
     480: { /* when window >=0px - webflow mobile landscape/portriat */
       spaceBetween: 30,
       slidesPerView: 7
     },
      767: { /* when window >= 767px - webflow tablet */
       spaceBetween: 40,
       slidesPerView: 7
     },
     992: { /* when window >= 988px - webflow desktop */
       spaceBetween: 40,
       slidesPerView: 10
     }
    }" 
    :slides-per-view="4" 
    class="trusted-by-list">
    <swiper-slide class="trusted-by-list" v-for="(watch, index) in watches" :key="index">
      <div class="me-2 ms-2 text-center cursor-pointer head" @click="filterbrand(watch)">{{ watch[0].toUpperCase() +
        watch.slice(1) }}
      </div>
    </swiper-slide>
  </swiper>
</template>

<script>
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Autoplay } from 'swiper/modules';
import 'swiper/css';

export default {
  components: { Swiper, SwiperSlide },
  props: ['watches', 'brand'],
  emits: ['update:watches', 'update:brand'],
  data() {
    return {
      modules: [Autoplay],
    }
  },
  methods: {
    onChange() {
      this.$emit('update:watches', this.watches);
    },
    filterbrand(brand) {
      window.location.href = `/?brand=${brand}`;
    }
  }
};
</script>

<style></style>