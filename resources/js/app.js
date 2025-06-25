import './bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';


import '../sass/app.scss'
import { createApp } from 'vue';
import WatchWizardComponent from './components/WatchWizard.vue';
import SliderComponent from './components/Slider.vue';
import SpinningWheel from './components/SpinningWheel.vue';

const app = createApp({});
app.component('watchwizard', WatchWizardComponent);
app.component('slider', SliderComponent);
app.component('spinningwheel', SpinningWheel);
app.mount('#app');