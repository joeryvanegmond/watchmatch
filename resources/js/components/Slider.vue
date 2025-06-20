<template>
    <swiper
      :slides-per-view="1"
      :navigation="true"
      :pagination="{ clickable: true}"
      :modules="modules"
      class="mySwiper"
    >
      <swiper-slide v-for="(watch, index) in watches" :key="index">
        <div class="p-4">
            <div class="d-flex justify-content-center">
                <h3>{{ watch.brand[0].toUpperCase() + watch.brand.slice(1) }} - {{ watch.model }}</h3>
            </div>
            <div class="d-flex justify-center">
                <img :src="watch.image_url" alt="Horloge afbeelding" width="250"/>
            </div>
          <label>
            <input type="checkbox" v-model="watch.selected" @change="onChange"/>
            Selecteer deze
          </label>
        </div>
      </swiper-slide>
    </swiper>
  </template>
  
  <script>
  import { Swiper, SwiperSlide } from 'swiper/vue';
  import { Navigation, Pagination } from 'swiper/modules';
  import 'swiper/css';
  import 'swiper/css/navigation';
  import 'swiper/css/pagination';
  
  export default {
    components: { Swiper, SwiperSlide },
    props: ['watches'],
    emits: ['update:watches'],
    data(){
        return {
            modules: [Navigation, Pagination],
        }
    },
    methods: {
        onChange() {
            this.$emit('update:watches', this.watches);
        }
    }
    };
  </script>
  
  <style>

  </style>
  