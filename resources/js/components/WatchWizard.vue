<template>
  <div class="row-fluid d-flex m-0">
    <!-- Error messages -->
    <div v-if="error" class="bg-red-500 w-100 m-0 top-0 left-0 error-banner position-fixed">
      <h4>{{ error }}</h4>
    </div>
    <div v-if="filterOpen" class="col-1">
      <div class="d-flex justify-content-center flex-column mt-4">
        <button v-if="filterOpen" class="fw-bold h1 mb-4"><i class="bi bi-x"
            @click="toggleFilterMenu(false)"></i></button>

        <button class="d-flex justify-content-center align-items-center mt-4 mb-4" @click="sortByPrice">
          <i :class="['h4', 'bi', sortPrice ? 'bi-sort-numeric-down-alt' : 'bi-sort-numeric-up', 'me-2']"></i>
        </button>

        <button class="d-flex justify-content-center align-items-center" @click="sortByBrand">
          <i :class="['h4', 'bi', sortBrand ? 'bi-sort-alpha-up-alt' : 'bi-sort-alpha-down', 'me-2']"></i>
        </button>
      </div>
    </div>
    <div :class="[filterOpen ? 'col-11' : 'col-12', 'ps-4', 'pe-4', 'm-0', 'd-flex', 'flex-column', 'justify-between']">
      <div class=" d-flex pt-4 pb-4 justify-content-between">
        <div class="col d-flex">
          <button v-if="!filterOpen" class="h1 me-3" @click="toggleFilterMenu(true)"><i
              class="bi bi-filter-left"></i></button>
          <input class="me-4" type="text" v-model="brand" placeholder="Merk" @blur="zoekAlternatieven(null)" required />
        </div>
        <input class="col" type="text" v-model="model" placeholder="Model" @blur="zoekAlternatieven(null)" required />
        <button v-if="brand && model" class="h4 ms-2 mt-1" @click="clear"><i class="bi bi-x"></i></button>
      </div>
      <!-- Result -->
      <div class="row d-flex justify-content-center">
        <div v-if="loading" class="d-flex justify-content-center position-absolute top-50 left-0">
          <spinningwheel></spinningwheel>
        </div>
        <transition-group v-if="showWatches" name="watch-fade" tag="div" class="watch-grid">
          <div class="card watch-card" v-for="(watch, index) in watches" :key="index"
            :style="{ '--delay': index * 25 + 'ms' }" :id="'watch-' + watch.id">
            <img :src="watch.image_url" alt="Watch image" class="watch-card-image" />
            <button
              class="watch-card-overlay position-absolute top-0 start-0 w-100 h-100 d-flex flex-column text-white p-2"
              style="background: rgba(0, 0, 0, 0.4);" @click="focusToWatch(watch.id)">
              <div class="d-flex justify-content-between justify-content-start ">
                <div class="fw-bold ps-3 pt-2 h4 text-start">{{ watch.brand[0].toUpperCase() + watch.brand.slice(1) }}
                </div>
              </div>
              <div class="position-absolute bottom-0 start-0 w-100">
                <div class="d-flex justify-content-start ps-4 pb-4 pe-1">
                  {{ watch.model[0].toUpperCase() + watch.model.slice(1) }}
                </div>
              </div>
            </button>
            <button v-if="isSearching" class="btn text-secondary p-2 m-2 position-absolute top-0 end-0"
              :id="'watch-btn-' + watch.id" @click="link(watch.id)"><i class="bi h4 bi-heart-fill"></i></button>
            <div v-if="isSearching" class="progress" style="height: 10px;">
              <div class="progress-bar bg-success" role="progressbar"
                :style="{ width: Math.min((watch.pivot.link_strength / 2) * 100, 100) + '%' }"
                :aria-valuenow="Math.min((watch.pivot.link_strength / 2) * 100, 100)" aria-valuemin="0"
                aria-valuemax="100">
              </div>
              <!-- {{ Math.min((watch.pivot.link_strength / 2) * 100, 100).toFixed(0) }}% -->
            </div>
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
      filterOpen: false,
      sortPrice: false,
      sortBrand: false,
    };
  },
  props: ['randomwatches'],
  methods: {
    async zoekAlternatieven(watch) {
      if (watch != null) {
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
      let request = {
        original: this.original.id,
        match: id
      };

      axios.post(`/link`, request)
        .then(res => {
          const el = document.getElementById('watch-btn-' + id);
          el.classList.remove('text-secondary');
          // el.classList.add('text-success');
          el.style.color = 'pink';
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
    },
    focusToWatch(id) {
      let curWatch = null;

      this.watches.forEach(watch => {

        if (watch.id !== id) {
          const el = document.getElementById('watch-' + watch.id);
          if (!el) return;
          el.classList.add('hidden');
          el.classList.remove('visible');
        } else {
          curWatch = watch;
        }
      });
      setTimeout(() => {
        let curEl = document.getElementById('watch-' + id);
        curEl.classList.add('hidden');
        curEl.classList.remove('visible');
        setTimeout(() => {
          this.zoekAlternatieven(curWatch);
        }, 1000);
      }, 700);
    },
    toggleFilterMenu(state) {
      this.filterOpen = state;
    },
    toggleWatchesVisibilty(toggle) {
      this.watches.forEach(watch => {
        const el = document.getElementById('watch-' + watch.id);
        if (!el) return;
        if (toggle) {
          el.classList.remove('visible');
          el.classList.add('hidden');
        } else {
          el.classList.remove('hidden');
          el.classList.add('visible');
        }
      });
    },
    sortByBrand() {
      this.sortBrand = !this.sortBrand;
      this.toggleWatchesVisibilty(true);
      setTimeout(() => {
        if (this.sortBrand) {
          this.watches.sort((a, b) => a.brand.localeCompare(b.brand));
        } else {
          this.watches.sort((a, b) => b.brand.localeCompare(a.brand));
        }
        this.toggleWatchesVisibilty(false);
      }, 1000);
    },
    sortByPrice() {
      this.sortPrice = !this.sortPrice;
      this.toggleWatchesVisibilty(true);
      setTimeout(() => {
        if (this.sortPrice) {
          this.watches.sort((a, b) => a.price.localeCompare(b.price ?? 0));
        } else {
          this.watches.sort((a, b) => b.price.localeCompare(a.price ?? 0));
        }
        this.toggleWatchesVisibilty(false);
      }, 1000);
    },
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