<template>
  <div class="row-fluid d-flex m-0">
    <!-- Error messages -->
    <div v-if="error" class="bg-red-500 w-100 m-0 top-0 left-0 error-banner position-fixed">
      <h4>{{ error }}</h4>
    </div>
    <!-- <div class="col-1 border">
      <div class="fw-bold h5 d-flex justify-content-center mt-4">
        Filter
      </div>
    </div> -->
    <div class="col-12 ps-4 pe-4 d-flex flex-column justify-between">
      <div class=" d-flex p-4 m-4 justify-content-center">
        <input class="col-6 me-4" type="text" v-model="brand" placeholder="Merk" @blur="zoekAlternatieven" required />
        <input class="col-6 me-3" type="text" v-model="model" placeholder="Model" @blur="zoekAlternatieven" required />
        <button v-if="brand != '' && model != ''" class="h4" @click="clear">x</button>
      </div>
      <!-- Result -->
      <div class="row d-flex justify-content-center">
        <div v-if="loading" class="d-flex justify-content-center position-absolute top-50 left-0">
          <spinningwheel></spinningwheel>
        </div>
        <transition-group v-if="showWatches" name="watch-fade" tag="div" class="watch-grid">
          <div class="card watch-card" v-for="(watch, index) in watches" :key="index"
            :style="{ '--delay': index * 25 + 'ms' }">
            <img :src="watch.image_url" alt="Watch image" class="watch-card-image" />
            <button
              class="watch-card-overlay position-absolute top-0 start-0 w-100 h-100 d-flex flex-column text-white p-2"
              style="background: rgba(0, 0, 0, 0.4);" @click="zoekAlternatieven(watch)">
              <div class="d-flex justify-content-between justify-content-start ">
                <div class="fw-bold ps-4 pt-4 h4">{{ watch.brand[0].toUpperCase() + watch.brand.slice(1) }}</div>
              </div>
              <div class="position-absolute bottom-0 start-0 w-100">
                <div class="d-flex p-4 m-2">
                  <div class="">
                    {{ watch.model[0].toUpperCase() + watch.model.slice(1) }}
                  </div>
                </div>
              </div>
            </button>
            <button v-if="isSearching" class="btn text-secondary p-2 m-2 position-absolute top-0 end-0"
              :id="'watch-' + watch.id" @click="link(watch.id)"><i class="bi bi-heart-fill"></i></button>
          </div>
        </transition-group>

      </div>
    </div>

  </div>
</template>

<script>
import axios from 'axios';
import Slider from './Slider.vue';
export default {
  data() {
    return {
      step: 1,
      stepTitle: '',
      brand: '',
      model: '',
      watches: [],
      loading: false,
      original: {},
      error: '',
      isSearching: false,
      showWatches: false,
    };
  },
  props: ['randomwatches'],
  methods: {
    async zoekAlternatieven(watch) {

      if (watch != null) {
        console.log(watch);
        this.brand = watch.brand;
        this.model = watch.model;
      }

      if ((this.brand && this.brand.trim() !== '') && (this.model && this.model.trim() !== '')) {
        this.isSearching = true;
        this.watches = [];
        this.loading = true;
        this.showWatches = false;

        axios.get(`/search/?brand=${this.brand}&model=${this.model}`)
          .then(res => {
            this.watches = res.data.similar.map(watch => ({
              ...watch,
              selected: false,
            }));
            this.original = res.data.original;
          })
          .catch(err => {
            this.error = err.response.data.error;
            console.log(this.error);
          })
          .finally(_ => {
            this.loading = false;
          });
      }
      setTimeout(() => {
        this.showWatches = true;
      }, 150);
    },
    setStep(step, title) {
      this.step = step;
      this.stepTitle = title;
    },
    link(id) {
      this.loading = true;

      let request = {
        original: this.original.id,
        match: id
      };

      axios.post(`/link`, request)
        .then(res => {
          const el = document.getElementById('watch-' + id);
          el.classList.remove('text-secondary');
          el.classList.add('text-success');
        })
        .catch(err => {
          console.log('Something went wrong during linking watches: ' + JSON.stringify(err.response));
        })
        .finally(_ => this.loading = false);
    },
    clear() {
      this.isSearching = false;
      this.brand = '';
      this.model = '';
      this.watches = this.randomwatches;
      this.showWatches = false;
      setTimeout(() => {
        this.showWatches = true;
      }, 300);
    }
  },
  computed: {
    hasOriginal() {
      return this.original && Object.keys(this.original).length > 0;
    },
    selectedAlternatives() {
      return this.watches.filter(a => a.selected);
    },
    getAlternativesIds() {
      this.watches.filter(a => a.selected).map(b => b.id);
    }
  },
  created() {
    this.watches = this.randomwatches;
    setTimeout(() => {
      this.showWatches = true;
    }, 150);
  }
};
</script>

<style scoped>
/* Plak hier je bestaande CSS als je wilt */
</style>