import './bootstrap';

import '../sass/app.scss'
import { createApp } from 'vue';
import WatchWizardComponent from './components/WatchWizard.vue';
import SliderComponent from './components/Slider.vue';

const app = createApp({});
app.component('watchwizard', WatchWizardComponent);
app.component('slider', SliderComponent);
app.mount('#app');