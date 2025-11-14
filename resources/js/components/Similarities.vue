<template>
    <div class="row justify-content-center">
        <div class="row d-flex flex-row" id="slider-brands">
            <slider :watches="getBrands"></slider>
        </div>
        <div class="row">
            <div v-if="loading" class="d-flex justify-content-center mt-4">
                <spinningwheel></spinningwheel>
            </div>
            <div v-if="similarities.length == 0 && !loading" class=" mt-4">
                <span>Niks gevonden..</span>
            </div>
            <div name="watch-fade" tag="div" class="watch-grid">
                <div class="card d-flex flex-row" v-for="(watch, index) in similarities" :key="index" :style="{}"
                    :id="'watch-' + watch.id">
                    <div class="col-12">
                        <img :src="watch.image_url" alt="Watch image" class="watch-card-image" />
                        <button
                            class="watch-card-overlay position-absolute top-0 start-0 w-100 h-100 d-flex flex-column  p-2"
                            style="background: rgba(0, 0, 0, 0.4);" @click="findWatch(watch)">
                            <div class="d-flex justify-content-between justify-content-start ">
                                <div class="fw-bold ps-3 pt-2 h4 text-start">{{ watch.brand[0].toUpperCase() +
                                    watch.brand.slice(1) }}
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
    </div>
</template>

<script>
import Slider from './Slider.vue';
export default {
    props: ['original', 'similarities'],
    data() {
        return {
            loading: false,
            watches: [],
        }
    },
    async created() {
    },
    mounted() {
        this.sliderPlacement();
        this.$nextTick(() => {
            this.waitForImages().then(this.setHeight);
        });

    },
    methods: {
        setHeight() {
            const rowHeight = 10;
            const items = document.querySelectorAll(".card");
            items.forEach((item, index) => {
                const img = item.querySelector("img");
                if ((index + 1) % 6 === 0) item.style.gridColumn = 'span 2';
                const contentHeight = img.getBoundingClientRect().height;
                const rowSpan = Math.ceil(contentHeight / rowHeight);
                item.style.setProperty('--row-height', rowSpan);
                if ((index + 1) % 11 === 0) item.style.gridRow = 'span ' + parseInt(rowSpan);
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
        findWatch(watch) {
            window.location.href = `/watch/${watch.id}`;
        },
        sliderPlacement() {
            const slider = document.getElementById('slider-brands');
            const details = document.getElementById('details-brands');
            if (slider && details) {
                details.appendChild(slider);
            }
        },
    },
    computed: {
        getBrands() {
            return [
                ...new Set(this.similarities.map(watch => watch.brand))
            ];
        },
    }
};
</script>