<script setup>
import { ref } from 'vue'

const ventaId = ref('')
const monto = ref(0)
const metodo = ref('Yape')
const usuarioId = ref('')
const clienteId = ref('')
const status = ref('')

async function submit() {
  status.value = 'Enviando...'
  try {
    const res = await fetch('/api/propinas', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ venta_id: ventaId.value || null, monto: parseFloat(monto.value), metodo_pago: metodo.value, usuario_id: usuarioId.value || null, cliente_id: clienteId.value || null })
    })
    if (!res.ok) throw new Error('Error')
    status.value = 'Propina registrada'
  } catch (e) {
    status.value = 'Error al registrar'
  }
}
</script>

<template>
  <div style="padding:20px; max-width:600px">
    <h2>Registrar Propina</h2>
    <div>
      <label>Venta ID: <input v-model="ventaId" /></label>
    </div>
    <div>
      <label>Cliente ID: <input v-model="clienteId" /></label>
    </div>
    <div>
      <label>Monto: <input type="number" v-model="monto" /></label>
    </div>
    <div>
      <label>Método: <input v-model="metodo" /></label>
    </div>
    <div>
      <label>Usuario ID (cajero): <input v-model="usuarioId" /></label>
    </div>
    <div style="margin-top:10px">
      <button @click="submit">Registrar</button>
      <span style="margin-left:10px">{{ status }}</span>
    </div>
  </div>
</template>

<style scoped>
input { padding:6px; margin:4px 0; }
button { padding:8px 12px }
</style>
