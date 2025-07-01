<template>
  <div class="row-fluid d-flex m-0">
    <!-- Error messages -->

    <div :class="['col-12', 'ps-4', 'pe-4', 'm-0', 'd-flex', 'flex-column', 'justify-between']">

      <div class="col-md-12 d-flex pt-4 pb-4 justify-content-around flex-column flex-sm-row">
        <div class="col col-lg-4 pe-1">
          <!-- <button v-if="!filterOpen" class="h1 me-3" @click="toggleFilterMenu(true)"><i
                class="bi bi-filter-left text-white"></i></button> -->
          <input class="me-sm-4 text-white" type="text" v-model="brand" placeholder="Merk"
            @blur="zoekAlternatieven(null)" required />
        </div>

        <div class="col col-lg-4 ps-md-3">
          <div class="input-group mt-3 mt-sm-0">
            <input class="col mt-sm-0 text-white" type="text" v-model="model" placeholder="Model"
              @blur="zoekAlternatieven(null)" required />
            <button @click="zoekAlternatieven(null)" class="border bg-white mt-sm-0"
              style="border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important;"> <i
                class="bi h5 bi-search text-black p-2"></i></button>
            <button v-if="brand && model" class="h4 ms-2 mt-1 text-white" @click="clear"><i
                class="bi bi-x"></i></button>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-center flex-row mt-2 mb-4">

        <div class="d-flex col text-white justify-content-center">
          <button class="d-flex justify-content-center align-items-center text-white" @click="sortByPrice">
            <i :class="['h2', 'bi', sortPrice ? 'bi-sort-numeric-down-alt' : 'bi-sort-numeric-up', 'me-2']"></i>
            <div>Filter op prijs</div>
          </button>
        </div>

        <div class="d-flex col text-white justify-content-center">
          <button class="d-flex justify-content-center align-items-center text-white" @click="sortByBrand">
            <i :class="['h2', 'bi', sortBrand ? 'bi-sort-alpha-up-alt' : 'bi-sort-alpha-down', 'me-2']"></i>
            <div>Filter op merk</div>
          </button>
        </div>
      </div>
      <!-- Brands -->
      <div class="row d-flex flex-row">
        <slider :watches="getBrands" :brand="brand"></slider>
        <!-- <div class="col" v-for="(brand, index) in getBrands">
            <div class="text-white">{{ brand }}</div>
          </div> -->
      </div>

      <!-- Result -->
      <div class="row d-flex justify-content-center">
        <div v-if="loading" class="d-flex justify-content-center position-absolute top-50 left-0">
          <spinningwheel></spinningwheel>
        </div>
        <transition-group v-if="showWatches" name="watch-fade" tag="div" class="watch-grid">
          <div class="card watch-card" v-for="(watch, index) in watches" :key="index"
            :style="{ '--delay': calculateDelay(index) + 'ms' }" :id="'watch-' + watch.id">
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
                :style="{ width: Math.min((watch.pivot ? watch.pivot.link_strength : 0 / 2) * 100, 100) + '%' }"
                :aria-valuenow="Math.min((watch.pivot ? watch.pivot.link_strength : 0 / 2) * 100, 100)"
                aria-valuemin="0" aria-valuemax="100">
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
      dupWatches: [],
      loading: false,
      original: {},
      error: '',
      isSearching: false,
      showWatches: false,
      filterOpen: false,
      sortPrice: false,
      sortBrand: false,
      info: '',
      hasMore: true,
      page: 1,
      lastScrollTop: 0,
    };
  },
  props: ['randomwatches', 'extrainfo'],
  mounted() {
    window.addEventListener('scroll', this.handleScroll);
    this.loadWatches();
  },
  beforeDestroy() {
    window.removeEventListener('scroll', this.handleScroll);
  },
  methods: {
    async loadWatches() {
      if (this.loading || !this.hasMore) return;
      this.loading = true;
      axios.get(`/watches?page=${this.page}`)
        .then(res => {
          this.watches.push(...res.data.data);
          this.dupWatches = this.watches;
          this.hasMore = res.data.current_page < res.data.last_page;
          this.page++;
        }).catch(err => {
          console.error('Error loading watches:', err);
        }).finally(_ => {
          this.loading = false;
          this.showWatches = true;
        });
    },
    handleScroll() {
      if (!this.isSearching) {
        const scrollTop = window.scrollY;
        const scrollHeight = document.documentElement.offsetHeight;
        const clientHeight = window.innerHeight;
        const threshold = 150;

        if (scrollTop > this.lastScrollTop) {
          if (scrollTop + clientHeight >= scrollHeight - threshold) {
            this.loadWatches();
          }
        }

        this.lastScrollTop = scrollTop;
      }
    },
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
            this.info = res.data.info;
          })
          .catch(err => {
            console.log(this.err);
            this.error = err.response.data.error;
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
          el.style.color = 'pink';
          let curWatch = this.watches.find(watch => watch.id == id);
          if (curWatch && curWatch.pivot) {
            curWatch.pivot.link_strength = (parseFloat(curWatch.pivot.link_strength) + 0.1).toString();
          }
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
      this.watches = this.dupWatches;
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
    calculateDelay(index) {
      const rows = 5;
      const cols = 10;

      // Calculate row and column based on index
      const row = index % rows;
      const col = Math.floor(index / rows);

      // Calculate delay based on row and column
      return (row * cols + col) * 25;
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
    },
    getBrands() {
      return [
        ...new Set(this.watches.map(watch => watch.brand))
      ];
    }
  },
  created() {
    // this.watches = this.randomwatches;
    this.info = this.extrainfo;
  }
};
</script>

<style scoped>
/* Plak hier je bestaande CSS als je wilt */
</style>