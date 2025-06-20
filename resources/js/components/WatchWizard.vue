<template>
  <div class="row d-flex justify-content-center">

    <div class="col-md-2 p-4 border">
      <div v-if="hasOriginal">
            <h4>{{  original.brand[0].toUpperCase() + original.brand.slice(1) }} {{ original.model.toUpperCase() }}</h4>
            <img :src="original.image_url" alt="afbeelding" width="150" />
          </div>
    </div>

    <div class="col-md-10 p-4 border d-flex justify-around">
      
      <div class="card card-rounded border-light p-4 col-md-6 border-5">
        <div class="d-flex justify-content-center">
          <h2>{{ stepTitle }}</h2>
        </div>
        <div class="progress">
          <div class="progress-bar" role="progressbar" :style="{ width: step * 33.33 + '%' }" aria-valuenow=""
            aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="card-body">
          <div class="" v-if="step === 1">
            <div class="d-flex flex-column">
              <input type="text" v-model="brand" placeholder="Merk" />
              <input type="text" v-model="model" placeholder="Model" />
            </div>
            <div>
              <button class="btn btn-primary" @click="zoekAlternatieven">Volgende</button>
            </div>
          </div>
  
          <div v-else-if="step === 2">
            <div v-if="loading">Resultaten ophalen...</div>
            <div v-else>
  
              <slider v-model:watches="watches"/>
  
              <div class="d-flex justify-content-between">
                <button class="btn btn-secondary" @click="setStep(1, 'Voer merk en model in')">Terug</button>
                <button class="btn btn-primary" @click="setStep(3, 'Samenvatting')">Volgende</button>
              </div>
            </div>
          </div>
  
          <div v-else-if="step === 3">
            <div v-for="alt in selectedAlternatives" :key="alt.id">
              <strong>{{ alt.brand }} {{ alt.model }}</strong><br />
              <img :src="alt.image_url" alt="afbeelding" width="150" />
              <a :href="alt.url" target="_blank">Bekijk</a>
            </div>
            <button class="btn btn-secondary" @click="setStep(2, 'Matches')">Terug</button>
            <!-- Later kun je hier een kaartcomponent toevoegen -->
          </div>
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
      loading: false,
      original: {},
    };
  },
  methods: {
    async zoekAlternatieven() {
      this.loading = true;
      this.setStep(2, "Matches");
      axios.get(`/search/?brand=${this.brand}&model=${this.model}`)
        .then(res => {
          this.watches = res.data.similar.map(watch => ({
            ...watch,
            selected: false,
          }));
          this.original = res.data.original;
        })
        .catch(err => {
          console.log('Something went wrong during search for watches: ' + JSON.stringify(err.response.data.error));
        })
        .finally(_ => {
          this.loading = false;
        });
    },
    setStep(step, title){
      this.step = step;
      this.stepTitle = title;
    },
  },
  computed: {
    hasOriginal() {
      return this.original && Object.keys(this.original).length > 0;
    },
    selectedAlternatives() {
      return this.watches.filter(a => a.selected);
    },
  },
  created() {
        this.stepTitle = "Voer merk en model in"
  }
};
</script>

<style scoped>
/* Plak hier je bestaande CSS als je wilt */
</style>