<template>
  <div class="row-fluid m-0">

    <!-- Brands -->
    <div class="row d-flex flex-row">
      <slider :watches="getBrands" :brand="brand"></slider>
    </div>

    <!-- Result -->
    <div class="row d-flex">
      <div v-if="loading" class="d-flex justify-content-center mt-4">
        <spinningwheel></spinningwheel>
      </div>
      <div tag="div" class="watch-grid" :style="{ '--viewport-width': viewportWidthMinus30 + 'px' }">
        <div class="card" v-for="(watch, index) in watches" :key="index" :id="'watch-' + watch.id">
          <img :src="watch.image_url" alt="Watch image" class="watch-card-image" />
          <button
            class="watch-card-overlay position-absolute top-0 start-0 w-100 h-100 d-flex flex-column text-white p-2"
            style="background: rgba(0, 0, 0, 0.4);" @click="zoekAlternatieven(watch)">
            <div class="d-flex justify-content-between justify-content-start">
              <div class="fw-bold ps-3 pt-2 h4 text-start">{{ watch.brand[0].toUpperCase() + watch.brand.slice(1) }}
              </div>
            </div>
            <div class="position-absolute bottom-0 start-0 w-100">
              <div class="d-flex justify-content-start ps-4 pb-4 pe-1">
                {{ watch.model[0].toUpperCase() + watch.model.slice(1) }}
              </div>
            </div>
          </button>
        </div>
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
      page: 2,
      lastScrollTop: 0,
      viewportWidth: window.innerWidth,
    };
  },
  props: ['randomwatches'],
  mounted() {
    window.addEventListener('resize', this.onResize);
    window.addEventListener('scroll', this.handleScroll);
    this.$nextTick(() => {
      this.waitForImages().then(this.setHeight);
      this.loading = false;
    });

  },
  beforeUnmount() {
    window.removeEventListener('resize', this.onResize);
  },
  beforeDestroy() {
    window.removeEventListener('scroll', this.handleScroll);
  },
  methods: {
    loadWatches() {
      if (this.loading || !this.hasMore) return;
      this.loading = true;
      axios.get(`/watches?page=${this.page}`)
        .then(res => {
          this.watches.push(...res.data.data);
          this.dupWatches = this.watches;
          this.hasMore = res.data.current_page < res.data.last_page;
          this.page++;
          this.loading = false;
          this.showWatches = true;

          // wacht tot de DOM is bijgewerkt
          this.$nextTick(() => {
            this.setHeight();
            const items = document.querySelectorAll('.watch-grid .card');
            items.forEach(item => {
              const rowHeight = parseInt(getComputedStyle(item).getPropertyValue('--row-height'), 10);
              if (rowHeight === 0) {
                item.remove();
              }
            });
          });
        }).catch(err => {
          console.error('Error loading watches:', err);
        });
    },
    setHeight() {
      const rowHeight = 10;
      const items = document.querySelectorAll(".card");
      items.forEach(item => {
        const img = item.querySelector("img");
        const contentHeight = img.getBoundingClientRect().height;
        const rowSpan = Math.ceil(contentHeight / rowHeight);
        item.style.setProperty('--row-height', rowSpan);
      });
    },
    waitForImages() {
      const images = Array.from(document.querySelectorAll(".card img"));
      const promises = images.map(img => {
        return new Promise(resolve => {
          if (img.complete) {
            resolve();
          } else {
            img.onload = () => resolve();
            img.onerror = () => resolve(); // fail-safe
          }
        });
      });
      return Promise.all(promises);
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
    zoekAlternatieven(watch) {
      if (watch != null) {
        this.brand = watch.brand;
        this.model = watch.model;
      }
      window.location.href = `/watch/${watch.id}`;
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
      window.location.href = '/';
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
      if (this.sortBrand) {
        this.watches.sort((a, b) => a.brand.localeCompare(b.brand));
      } else {
        this.watches.sort((a, b) => b.brand.localeCompare(a.brand));
      }
      this.toggleWatchesVisibilty(false);
    },
    sortByPrice() {
      this.sortPrice = !this.sortPrice;
      if (this.sortPrice) {
        this.watches.sort((a, b) => a.price.localeCompare(b.price ?? 0));
      } else {
        this.watches.sort((a, b) => b.price.localeCompare(a.price ?? 0));
      }
      this.toggleWatchesVisibilty(false);
    },
    onResize() {
      this.viewportWidth = window.innerWidth;
    }
  },
  computed: {
    getBrands() {
      return [
        ...new Set(this.watches.map(watch => watch.brand))
      ];
    },
    viewportWidthMinus30() {
      return this.viewportWidth - 72;
    }
  },
  created() {
    this.watches = this.randomwatches.data;
    this.loading = true;
  }
};
</script>