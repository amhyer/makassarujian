<template>
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div v-for="i in 4" :key="i" class="bg-white p-6 rounded-xl shadow-sm animate-pulse h-24"></div>
    </div>
    
    <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <!-- Total Card -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
            <p class="text-sm font-medium text-slate-500">Total Soal</p>
            <h3 class="text-2xl font-bold text-slate-900">{{ stats.total }}</h3>
        </div>

        <!-- Difficulty Stats -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 md:col-span-3">
            <p class="text-sm font-medium text-slate-500 mb-3">Distribusi Kesulitan</p>
            <div class="flex gap-4">
                <div v-for="diff in stats.by_difficulty" :key="diff.difficulty" class="flex-1">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="capitalize">{{ diff.difficulty }}</span>
                        <span>{{ diff.total }}</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                        <div 
                            class="h-1.5 rounded-full" 
                            :class="getDiffClass(diff.difficulty)"
                            :style="{ width: (diff.total / stats.total * 100) + '%' }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Stats -->
    <div v-if="!loading" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
            <h4 class="text-sm font-bold text-slate-900 mb-4 uppercase tracking-wider">Per Mata Pelajaran</h4>
            <div class="space-y-3">
                <div v-for="subject in stats.by_subject" :key="subject.name" class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">{{ subject.name }}</span>
                    <span class="text-sm font-semibold bg-slate-50 px-2 py-1 rounded text-slate-700">{{ subject.total }}</span>
                </div>
                <div v-if="!stats.by_subject.length" class="text-sm text-slate-400 italic">Belum ada data.</div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
            <h4 class="text-sm font-bold text-slate-900 mb-4 uppercase tracking-wider">Per Kelas</h4>
            <div class="space-y-3">
                <div v-for="cls in stats.by_class" :key="cls.name" class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Kelas {{ cls.name }}</span>
                    <span class="text-sm font-semibold bg-slate-50 px-2 py-1 rounded text-slate-700">{{ cls.total }}</span>
                </div>
                <div v-if="!stats.by_class.length" class="text-sm text-slate-400 italic">Belum ada data.</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const stats = ref({
    total: 0,
    by_subject: [],
    by_difficulty: [],
    by_class: []
});
const loading = ref(true);

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/questions/stats');
        stats.value = response.data;
    } catch (error) {
        console.error('Failed to fetch stats:', error);
    } finally {
        loading.value = false;
    }
};

const getDiffClass = (difficulty) => {
    switch (difficulty) {
        case 'easy': return 'bg-emerald-500';
        case 'medium': return 'bg-amber-500';
        case 'hard': return 'bg-rose-500';
        default: return 'bg-slate-500';
    }
};

onMounted(fetchStats);
</script>
