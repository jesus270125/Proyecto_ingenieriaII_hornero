<script setup>
import { ref } from 'vue'

const query = ref('')
const resultados = ref([])
const status = ref('')

async function buscar() {
  status.value = 'Buscando...'
  try {
    const res = await fetch(`/api/admin/clientes?search=${encodeURIComponent(query.value)}`)
    const data = await res.json()
    resultados.value = data
    status.value = ''
  } catch (e) {
    status.value = 'Error al buscar'
  }
}
</script>

<template>
  <div style="padding:20px; max-width:800px">
    <h2>Historial de Cliente</h2>
    <div>
      <input v-model="query" placeholder="Nombre, apellido o teléfono" style="width:60%" />
      <button @click="buscar">Buscar</button>
      <span style="margin-left:10px">{{ status }}</span>
    </div>

    <div v-if="resultados.length" style="margin-top:10px">
      <h3>Resultados</h3>
      <ul>
        <li v-for="c in resultados" :key="c.id">
          <strong>{{ c.nombre }}</strong> — puntos: {{ c.puntos_fidelidad }}
        </li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
input { padding:6px }
button { padding:6px 10px }
</style>
