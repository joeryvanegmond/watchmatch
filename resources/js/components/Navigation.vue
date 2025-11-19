<template>
    <div class="col-md-12 d-flex pt-4 pb-2 justify-content-around flex-column flex-sm-row">
        <div class="col col-lg-4 position-relative">
            <input class="me-sm-4"
                :style="suggestions.length ? { 'border-bottom-left-radius': '0', 'border-bottom-right-radius': '0' } : {}"
                type="text" v-model="query" placeholder="Find watch" @keyup="findWatch()" @blur="onBlur()" required />
            <ul v-if="suggestions.length" class="dropdown">
                <li v-for="suggestion in suggestions" @click="showMatch(suggestion.id)">
                    {{ suggestion.brand }} {{ suggestion.model }}
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    components: {},
    props: [],
    data() {
        return {
            query: '',
            suggestions: [],
        }
    },
    methods: {
        findWatch() {
            axios.get(`/search?q=${this.query}`)
                .then(response => {
                    this.suggestions = response.data;
                }).catch(error => {
                    console.log(error);
                });
        },
        showMatch(id) {
            window.location.href = `/watch/${id}`;
        },
        onBlur() {
            setTimeout(() => {
                this.suggestions = []
            }, 200)
        }
    }
};
</script>