<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import debounce from 'lodash/debounce'

const questions = ref([])
const meta = ref({})
const loading = ref(false)
const search = ref('')
const subjectId = ref('')
const classId = ref('')
const page = ref(1)

const fetchQuestions = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/questions', {
      params: {
        page: page.value,
        search: search.value,
        subject_id: subjectId.value,
        class_id: classId.value
      }
    })
    questions.value = response.data.data
    meta.value = response.data.meta
  } catch (error) {
    console.error('Gagal mengambil data soal:', error)
  } finally {
    loading.value = false
  }
}

const debouncedFetch = debounce(() => {
  page.value = 1
  fetchQuestions()
}, 500)

watch([search, subjectId, classId], () => {
  debouncedFetch()
})

watch(page, () => {
  fetchQuestions()
})

onMounted(() => {
  fetchQuestions()
})

const deleteQuestion = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus soal ini?')) return
  
  try {
    await axios.delete(`/api/questions/${id}`)
    fetchQuestions()
  } catch (error) {
    alert('Gagal menghapus soal.')
  }
}

const importFile = ref(null)
const importing = ref(false)

const triggerImport = () => {
  importFile.value.click()
}

const handleImport = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('file', file)

  importing.value = true
  try {
    const response = await axios.post('/api/questions/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    alert(response.data.message)
    if (response.data.errors.length > 0) {
      console.warn('Import errors:', response.data.errors)
    }
    fetchQuestions()
  } catch (error) {
    alert('Gagal mengimpor soal. Pastikan format CSV benar.')
  } finally {
    importing.value = false
    event.target.value = ''
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-wrap gap-4 items-center">
      <div class="relative flex-1 min-w-[200px]">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </span>
        <input 
          v-model="search"
          type="text" 
          placeholder="Cari konten soal..." 
          class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
        />
      </div>
      
      <select v-model="subjectId" class="block w-48 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
        <option value="">Semua Mata Pelajaran</option>
        <!-- Options should be fetched from API in real scenario -->
      </select>

      <select v-model="classId" class="block w-48 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
        <option value="">Semua Kelas</option>
      </select>

      <div class="flex gap-2">
        <input type="file" ref="importFile" @change="handleImport" class="hidden" accept=".csv" />
        <button 
          @click="triggerImport" 
          :disabled="importing"
          class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          {{ importing ? 'Mengimpor...' : 'Impor CSV' }}
        </button>
        <a href="/ujian/bank-soal/create" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
          Tambah Soal
        </a>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Konten Soal</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kesulitan</th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="loading" v-for="i in 5" :key="i" class="animate-pulse">
              <td class="px-6 py-4 whitespace-nowrap"><div class="h-4 bg-gray-200 rounded w-3/4"></div></td>
              <td class="px-6 py-4 whitespace-nowrap"><div class="h-4 bg-gray-200 rounded w-1/2"></div></td>
              <td class="px-6 py-4 whitespace-nowrap"><div class="h-4 bg-gray-200 rounded w-1/4"></div></td>
              <td class="px-6 py-4 whitespace-nowrap text-right"><div class="h-4 bg-gray-200 rounded w-10 ml-auto"></div></td>
            </tr>
            <tr v-else v-for="q in questions" :key="q.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900 line-clamp-2" v-html="q.content?.question_text || ''"></div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                  {{ q.type === 'mcq' ? 'Pilihan Ganda' : 'Essay' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <span :class="{
                  'text-green-600': q.difficulty === 'easy',
                  'text-yellow-600': q.difficulty === 'medium',
                  'text-red-600': q.difficulty === 'hard'
                }" class="capitalize font-medium">{{ q.difficulty }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex justify-end space-x-2">
                  <button @click="deleteQuestion(q.id)" class="text-red-600 hover:text-red-900">Hapus</button>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && questions.length === 0">
              <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                Tidak ada soal ditemukan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t border-gray-200">
        <div class="flex-1 flex justify-between sm:hidden">
          <button @click="page--" :disabled="page === 1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Previous
          </button>
          <button @click="page++" :disabled="page === meta.last_page" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Next
          </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Menampilkan <span class="font-medium">{{ meta.from }}</span> sampai <span class="font-medium">{{ meta.to }}</span> dari <span class="font-medium">{{ meta.total }}</span> hasil
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
              <button @click="page--" :disabled="page === 1" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                <span class="sr-only">Previous</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                </svg>
              </button>
              <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                Halaman {{ page }} dari {{ meta.last_page }}
              </span>
              <button @click="page++" :disabled="page === meta.last_page" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                <span class="sr-only">Next</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                </svg>
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
