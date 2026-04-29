import './bootstrap';
import { createApp } from 'vue';
import QuestionForm from './components/QuestionForm.vue';
import QuestionList from './components/QuestionList.vue';
import QuestionStats from './components/QuestionStats.vue';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const app = createApp({});
app.component('question-form', QuestionForm);
app.component('question-list', QuestionList);
app.component('question-stats', QuestionStats);
app.mount('#app');
