<script setup>
import { ref, reactive, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import axios from 'axios'
import katex from 'katex'
import 'katex/dist/katex.min.css'

const props = defineProps({
  subjectId: String,
  classId: String
})

// Question State
const form = reactive({
  subject_id: props.subjectId || '',
  class_id: props.classId || '',
  type: 'mcq',
  content: '',
  explanation: '',
  difficulty: 'medium',
  options: [
    { content: '', is_correct: false },
    { content: '', is_correct: false }
  ]
})

const loading = ref(false)
const uploading = ref(false)
const errors = ref({})
const fileInput = ref(null)

// TipTap Editor
const editor = useEditor({
  content: '',
  extensions: [
    StarterKit,
    Image.configure({
      HTMLAttributes: {
        class: 'max-w-full h-auto rounded-lg shadow-sm my-4',
      },
    }),
  ],
  onUpdate: ({ editor }) => {
    form.content = editor.getHTML()
  },
  editorProps: {
    attributes: {
      class: 'prose prose-sm sm:prose lg:prose-lg xl:prose-2xl focus:outline-none min-h-[150px] p-4 bg-white border border-gray-200 rounded-lg',
    },
  },
})

onBeforeUnmount(() => {
  editor.value.destroy()
})

// Methods
const triggerImageUpload = () => {
  fileInput.value.click()
}

const handleImageUpload = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  // Validation
  if (!['image/jpeg', 'image/png'].includes(file.type)) {
    alert('Hanya diperbolehkan file JPG atau PNG.')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    alert('Ukuran file maksimal 2MB.')
    return
  }

  const formData = new FormData()
  formData.append('image', file)

  uploading.value = true
  try {
    const response = await axios.post('/api/questions/upload-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    // Insert into editor
    editor.value.chain().focus().setImage({ src: response.data.url }).run()
  } catch (err) {
    alert('Gagal mengupload gambar.')
  } finally {
    uploading.value = false
    event.target.value = '' // Reset input
  }
}

const addOption = () => {
  form.options.push({ content: '', is_correct: false })
}

const removeOption = (index) => {
  if (form.options.length > 2) {
    form.options.splice(index, 1)
  }
}

const setCorrect = (index) => {
  form.options.forEach((opt, i) => {
    opt.is_correct = i === index
  })
}

const renderLaTeX = (text) => {
  try {
    return katex.renderToString(text, { throwOnError: false })
  } catch (e) {
    return text
  }
}

const submitForm = async () => {
  loading.value = true
  errors.value = {}
  
  try {
    const response = await axios.post('/api/questions', form)
    alert('Soal berhasil disimpan!')
    // Reset form or redirect
  } catch (err) {
    if (err.response && err.response.status === 422) {
      errors.value = err.response.data.errors
    } else {
      alert('Terjadi kesalahan saat menyimpan soal.')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-4xl mx-auto p-6 bg-gray-50 rounded-xl shadow-sm border border-gray-100">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Buat Soal Baru</h2>

    <div class="space-y-6">
      <!-- Content Editor -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Konten Soal (HTML/Rich Text)</label>
        <div class="border rounded-lg overflow-hidden bg-white">
          <div class="flex items-center space-x-2 p-2 bg-gray-50 border-b">
            <button @click="editor.chain().focus().toggleBold().run()" class="p-1 hover:bg-gray-200 rounded" :class="{ 'bg-gray-200': editor?.isActive('bold') }"><b>B</b></button>
            <button @click="editor.chain().focus().toggleItalic().run()" class="p-1 hover:bg-gray-200 rounded" :class="{ 'bg-gray-200': editor?.isActive('italic') }"><i>I</i></button>
            <button @click="editor.chain().focus().toggleCodeBlock().run()" class="p-1 hover:bg-gray-200 rounded">Code</button>
            
            <div class="h-4 w-px bg-gray-300 mx-1"></div>
            
            <!-- Image Upload Button -->
            <button @click="triggerImageUpload" :disabled="uploading" class="p-1 hover:bg-gray-200 rounded flex items-center space-x-1" title="Upload Gambar">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span class="text-xs">{{ uploading ? '...' : 'Gambar' }}</span>
            </button>
            <input type="file" ref="fileInput" @change="handleImageUpload" class="hidden" accept="image/png, image/jpeg" />
          </div>
          <editor-content :editor="editor" />
        </div>
        <p v-if="errors.content" class="mt-1 text-sm text-red-500">{{ errors.content[0] }}</p>
      </div>

      <!-- Difficulty & Type -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">Tingkat Kesulitan</label>
          <select v-model="form.difficulty" class="w-full border-gray-200 rounded-lg focus:ring-blue-500">
            <option value="easy">Mudah</option>
            <option value="medium">Sedang</option>
            <option value="hard">Sulit</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">Tipe Soal</label>
          <select v-model="form.type" class="w-full border-gray-200 rounded-lg focus:ring-blue-500">
            <option value="mcq">Pilihan Ganda (MCQ)</option>
            <option value="essay">Uraian (Essay)</option>
          </select>
        </div>
      </div>

      <!-- Dynamic Options Builder -->
      <div v-if="form.type === 'mcq'" class="space-y-4">
        <div class="flex items-center justify-between">
          <label class="block text-sm font-semibold text-gray-700">Pilihan Jawaban</label>
          <button @click="addOption" class="text-sm px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
            + Tambah Opsi
          </button>
        </div>
        
        <div v-for="(option, index) in form.options" :key="index" class="flex items-start space-x-3 p-4 bg-white border border-gray-200 rounded-lg shadow-sm hover:border-blue-300 transition">
          <div class="mt-2">
            <input 
              type="radio" 
              :name="'correct-answer'" 
              :checked="option.is_correct" 
              @change="setCorrect(index)"
              class="w-5 h-5 text-blue-600 focus:ring-blue-500"
            />
          </div>
          <div class="flex-1 space-y-2">
            <textarea 
              v-model="option.content" 
              placeholder="Ketik pilihan jawaban... (Gunakan $...$ untuk LaTeX)" 
              class="w-full border-gray-100 rounded-lg focus:border-blue-500 resize-none h-16 text-sm"
            ></textarea>
            
            <!-- LaTeX Preview -->
            <div v-if="option.content.includes('$')" class="text-xs text-gray-500 italic p-1 bg-gray-50 rounded">
              Preview: <span v-html="renderLaTeX(option.content)"></span>
            </div>
          </div>
          <button @click="removeOption(index)" v-if="form.options.length > 2" class="mt-2 text-red-400 hover:text-red-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
        <p v-if="errors.options" class="text-sm text-red-500">{{ errors.options[0] }}</p>
      </div>

      <!-- Explanation -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Penjelasan Jawaban (Optional)</label>
        <textarea v-model="form.explanation" class="w-full border-gray-200 rounded-lg focus:ring-blue-500 h-24 text-sm" placeholder="Tulis pembahasan soal di sini..."></textarea>
      </div>

      <!-- Submit Action -->
      <div class="pt-4 border-t">
        <button 
          @click="submitForm" 
          :disabled="loading"
          class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-bold text-lg hover:bg-blue-700 active:transform active:scale-95 transition disabled:bg-blue-300"
        >
          <span v-if="loading">Menyimpan...</span>
          <span v-else>Simpan Soal</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style>
.ProseMirror {
  outline: none;
}
.ProseMirror p {
  margin: 0.5em 0;
}
</style>
