<template>
  <swiper :slides-per-view="4" :modules="modules" :autoplay="{ autoplay: 10, disableOnInteraction: false }"
    class="mySwiper" :breakpoints="{
      320: { slidesPerView: 3 },
      768: { slidesPerView: 7 },
      1000: { slidesPerView: 10 }
    }"
    
    :loop="true">
    <swiper-slide v-for="(watch, index) in watches" :key="index">
      <div class="text-white me-2 ms-2 text-center" @click="brand = watch">{{ watch[0].toUpperCase() + watch.slice(1) }}
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
      this.$emit('update:brand', this.brand);
    }
  }
};
</script>

<style></style>