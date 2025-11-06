<template>
    <div class="col-md-12 d-flex pt-4 pb-2 justify-content-around flex-column flex-sm-row">
        <div class="col col-lg-4 pe-1">
            <input class="me-sm-4" type="text" v-model="brand" placeholder="Merk" @blur="findWatch()" required />
        </div>

        <div class="col col-lg-4 ps-md-3">
            <div class="input-group mt-3 mt-sm-0">
                <input class="col mt-sm-0" type="text" v-model="model" placeholder="Model"
                    @blur="findWatch()" v-on:keyup.enter="findWatch" required />
                <button @click="findWatch()" class="border border-black bg-white mt-sm-0"
                    style="border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important;"> <i
                        class="bi h5 bi-search text-black p-2"></i></button>
                <button v-if="brand && model" class="h4 ms-2 mt-1" @click="clear"><i
                        class="bi bi-x"></i></button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    components: {},
    props: [],
    data() {
        return {
            brand: '',
            model: '',
        }
    },
    methods: {
        findWatch() {
            if ((this.brand && this.brand.trim() !== '') && (this.model && this.model.trim() !== '')) {
                window.location.href = `/watch/find/?brand=${this.brand}&model=${this.model}`
            }
        },
    }
};
</script>