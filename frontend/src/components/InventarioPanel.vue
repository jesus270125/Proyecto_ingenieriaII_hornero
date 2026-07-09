<script setup>
/**
 * InventarioPanel.vue - Módulo de Gestión de Inventarios
 *
 * Componente principal que implementa RF31, RF32, RF33 y RF34.
 * Se integra como pestaña dentro de AdminView.vue.
 *
 * Sub-pestañas:
 * 1. Insumos (RF31) - CRUD completo del catálogo de insumos
 * 2. Entradas (RF32) - Registro de compras/entradas de mercadería
 * 3. Recetas (RF33) - Definición de ingredientes por producto
 * 4. Alertas (RF34) - Visualización de insumos con stock bajo
 */
import { ref, onMounted, computed } from 'vue';
import api from '../api';

// ================================================================
// Estado general
// ================================================================
const activeSubTab = ref('insumos');
const loading = ref(false);

// ================================================================
// RF31: INSUMOS
// ================================================================
const insumos = ref([]);
const categorias = ref([]);
const filtroCategoria = ref('');
const filtroEstado = ref('');
const insumoForm = ref({
  id: null,
  nombre: '',
  categoria: '',
  unidad_medida: 'kg',
  stock_actual: 0,
  stock_minimo: 0,
});
const editingInsumo = ref(false);
const showInsumoForm = ref(false);

const loadInsumos = async () => {
  loading.value = true;
  try {
    const params = {};
    if (filtroCategoria.value) params.categoria = filtroCategoria.value;
    if (filtroEstado.value) params.estado = filtroEstado.value;
    const r = await api.get('/inventario/insumos', { params });
    insumos.value = r.data;
  } catch (e) { console.error(e); }
  loading.value = false;
};

const loadCategorias = async () => {
  try {
    const r = await api.get('/inventario/categorias');
    categorias.value = r.data;
  } catch (e) { console.error(e); }
};

const submitInsumo = async () => {
  try {
    const payload = {
      nombre: insumoForm.value.nombre,
      categoria: insumoForm.value.categoria,
      unidad_medida: insumoForm.value.unidad_medida,
      stock_actual: Number(insumoForm.value.stock_actual),
      stock_minimo: Number(insumoForm.value.stock_minimo),
    };
    if (editingInsumo.value && insumoForm.value.id) {
      await api.put(`/inventario/insumos/${insumoForm.value.id}`, payload);
    } else {
      await api.post('/inventario/insumos', payload);
    }
    clearInsumoForm();
    await loadInsumos();
    await loadCategorias();
  } catch (e) {
    const msg = e?.response?.data?.errors
      ? Object.values(e.response.data.errors).flat()[0]
      : 'Error al guardar insumo';
    alert(msg);
  }
};

const editInsumo = (item) => {
  editingInsumo.value = true;
  showInsumoForm.value = true;
  insumoForm.value = {
    id: item.id,
    nombre: item.nombre,
    categoria: item.categoria,
    unidad_medida: item.unidad_medida,
    stock_actual: item.stock_actual,
    stock_minimo: item.stock_minimo,
  };
};

const toggleInsumo = async (item) => {
  const accion = item.estado === 'activo' ? 'desactivar' : 'activar';
  if (!confirm(`¿${accion.charAt(0).toUpperCase() + accion.slice(1)} el insumo "${item.nombre}"?`)) return;
  try {
    await api.delete(`/inventario/insumos/${item.id}`);
    await loadInsumos();
  } catch (e) { console.error(e); }
};

const clearInsumoForm = () => {
  editingInsumo.value = false;
  showInsumoForm.value = false;
  insumoForm.value = { id: null, nombre: '', categoria: '', unidad_medida: 'kg', stock_actual: 0, stock_minimo: 0 };
};

const unidadesComunes = ['kg', 'gramos', 'litros', 'ml', 'unidades'];

// ================================================================
// RF32: ENTRADAS DE MERCADERÍA
// ================================================================
const entradas = ref([]);
const entradaForm = ref({
  insumo_id: '',
  cantidad: '',
  fecha: new Date().toISOString().split('T')[0],
  costo: '',
  proveedor: '',
  observacion: '',
});
const filtroEntradaInsumo = ref('');
const filtroEntradaFrom = ref('');
const filtroEntradaTo = ref('');

const insumosActivos = computed(() => insumos.value.filter(i => i.estado === 'activo'));

const loadEntradas = async () => {
  try {
    const params = {};
    if (filtroEntradaInsumo.value) params.insumo_id = filtroEntradaInsumo.value;
    if (filtroEntradaFrom.value) params.from = filtroEntradaFrom.value;
    if (filtroEntradaTo.value) params.to = filtroEntradaTo.value;
    const r = await api.get('/inventario/entradas', { params });
    entradas.value = r.data;
  } catch (e) { console.error(e); }
};

const submitEntrada = async () => {
  if (!entradaForm.value.insumo_id || !entradaForm.value.cantidad) {
    alert('Selecciona un insumo e ingresa la cantidad.');
    return;
  }
  try {
    const payload = {
      insumo_id: Number(entradaForm.value.insumo_id),
      cantidad: Number(entradaForm.value.cantidad),
      fecha: entradaForm.value.fecha,
      costo: entradaForm.value.costo ? Number(entradaForm.value.costo) : null,
      proveedor: entradaForm.value.proveedor || null,
      observacion: entradaForm.value.observacion || null,
    };
    const r = await api.post('/inventario/entradas', payload);
    if (r.data.success) {
      alert(r.data.msg);
      clearEntradaForm();
      await loadEntradas();
      await loadInsumos();
    }
  } catch (e) {
    const msg = e?.response?.data?.errors
      ? Object.values(e.response.data.errors).flat()[0]
      : 'Error al registrar entrada';
    alert(msg);
  }
};

const clearEntradaForm = () => {
  entradaForm.value = {
    insumo_id: '',
    cantidad: '',
    fecha: new Date().toISOString().split('T')[0],
    costo: '',
    proveedor: '',
    observacion: '',
  };
};

// ================================================================
// RF33: RECETAS
// ================================================================
const menuItems = ref([]);
const selectedMenuId = ref('');
const recetaIngredientes = ref([]);
const recetaMenuNombre = ref('');

const loadMenu = async () => {
  try {
    const r = await api.get('/menu');
    menuItems.value = r.data;
  } catch (e) { console.error(e); }
};

const loadReceta = async () => {
  if (!selectedMenuId.value) {
    recetaIngredientes.value = [];
    recetaMenuNombre.value = '';
    return;
  }
  try {
    const r = await api.get(`/inventario/recetas/${selectedMenuId.value}`);
    recetaMenuNombre.value = r.data.menu_nombre;
    recetaIngredientes.value = r.data.ingredientes.map(i => ({
      insumo_id: i.insumo_id,
      nombre: i.nombre,
      unidad_medida: i.unidad_medida,
      cantidad_requerida: i.cantidad_requerida,
    }));
  } catch (e) {
    recetaIngredientes.value = [];
    recetaMenuNombre.value = '';
  }
};

const addIngrediente = () => {
  recetaIngredientes.value.push({
    insumo_id: '',
    nombre: '',
    unidad_medida: '',
    cantidad_requerida: '',
  });
};

const removeIngrediente = (index) => {
  recetaIngredientes.value.splice(index, 1);
};

const onIngredienteInsumoChange = (index) => {
  const ing = recetaIngredientes.value[index];
  const insumo = insumos.value.find(i => i.id === Number(ing.insumo_id));
  if (insumo) {
    ing.nombre = insumo.nombre;
    ing.unidad_medida = insumo.unidad_medida;
  }
};

const submitReceta = async () => {
  if (!selectedMenuId.value) {
    alert('Selecciona un producto del menú.');
    return;
  }
  const ingredientes = recetaIngredientes.value
    .filter(i => i.insumo_id && i.cantidad_requerida)
    .map(i => ({
      insumo_id: Number(i.insumo_id),
      cantidad_requerida: Number(i.cantidad_requerida),
    }));

  if (ingredientes.length === 0) {
    alert('Agrega al menos un ingrediente con cantidad.');
    return;
  }

  try {
    const r = await api.post('/inventario/recetas', {
      menu_id: Number(selectedMenuId.value),
      ingredientes,
    });
    if (r.data.success) {
      alert(r.data.msg);
      await loadReceta();
    }
  } catch (e) {
    alert('Error al guardar receta');
  }
};

// ================================================================
// RF34: ALERTAS
// ================================================================
const alertas = ref([]);

const loadAlertas = async () => {
  try {
    const r = await api.get('/inventario/alertas');
    alertas.value = r.data;
  } catch (e) { console.error(e); }
};

const getNivelClass = (nivel) => {
  if (nivel === 'agotado') return 'nivel-agotado';
  if (nivel === 'critico') return 'nivel-critico';
  return 'nivel-bajo';
};

const getNivelLabel = (nivel) => {
  if (nivel === 'agotado') return '⛔ AGOTADO';
  if (nivel === 'critico') return '🔴 CRÍTICO';
  return '🟠 BAJO';
};

// ================================================================
// Navegación de sub-pestañas
// ================================================================
const setSubTab = (tab) => {
  activeSubTab.value = tab;
  if (tab === 'insumos') { loadInsumos(); loadCategorias(); }
  if (tab === 'entradas') { loadEntradas(); if (!insumos.value.length) loadInsumos(); }
  if (tab === 'recetas') { loadMenu(); if (!insumos.value.length) loadInsumos(); }
  if (tab === 'alertas') { loadAlertas(); }
};

// Cargar datos iniciales
onMounted(() => {
  loadInsumos();
  loadCategorias();
});
</script>

<template>
  <div class="inventario-panel">
    <!-- Sub-pestañas internas -->
    <div class="sub-tabs">
      <button :class="['sub-tab', activeSubTab === 'insumos' ? 'active' : '']" @click="setSubTab('insumos')">
        📦 Insumos
      </button>
      <button :class="['sub-tab', activeSubTab === 'entradas' ? 'active' : '']" @click="setSubTab('entradas')">
        🚛 Entradas
      </button>
      <button :class="['sub-tab', activeSubTab === 'recetas' ? 'active' : '']" @click="setSubTab('recetas')">
        📋 Recetas
      </button>
      <button :class="['sub-tab', activeSubTab === 'alertas' ? 'active' : '']" @click="setSubTab('alertas')">
        🔔 Alertas
        <span v-if="alertas.length" class="alert-badge">{{ alertas.length }}</span>
      </button>
    </div>

    <!-- ============================================================ -->
    <!-- RF31: SUB-PESTAÑA INSUMOS -->
    <!-- ============================================================ -->
    <section v-if="activeSubTab === 'insumos'" class="inv-section">
      <div class="section-header">
        <h2 class="section-title">📦 Catálogo de Insumos</h2>
        <button class="btn btn-primary" @click="showInsumoForm = !showInsumoForm; if(!showInsumoForm) clearInsumoForm()">
          {{ showInsumoForm ? '✕ Cerrar' : '+ Nuevo Insumo' }}
        </button>
      </div>

      <!-- Formulario crear/editar insumo -->
      <div v-if="showInsumoForm" class="inv-form">
        <h3 style="margin: 0 0 12px; color: #f1af32;">{{ editingInsumo ? 'Editar Insumo' : 'Nuevo Insumo' }}</h3>
        <form @submit.prevent="submitInsumo">
          <div class="form-grid-inv">
            <div class="form-group-inv">
              <label>Nombre</label>
              <input v-model="insumoForm.nombre" class="form-input" placeholder="Ej. Pollo entero crudo" required>
            </div>
            <div class="form-group-inv">
              <label>Categoría</label>
              <input v-model="insumoForm.categoria" class="form-input" list="categorias-list" placeholder="Ej. Carnes" required>
              <datalist id="categorias-list">
                <option v-for="cat in categorias" :key="cat" :value="cat" />
              </datalist>
            </div>
            <div class="form-group-inv">
              <label>Unidad de Medida</label>
              <select v-model="insumoForm.unidad_medida" class="form-input" required>
                <option v-for="u in unidadesComunes" :key="u" :value="u">{{ u }}</option>
              </select>
            </div>
            <div class="form-group-inv">
              <label>Stock Actual</label>
              <input v-model.number="insumoForm.stock_actual" type="number" step="0.001" min="0" class="form-input" required>
            </div>
            <div class="form-group-inv">
              <label>Stock Mínimo</label>
              <input v-model.number="insumoForm.stock_minimo" type="number" step="0.001" min="0" class="form-input" required>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ editingInsumo ? 'Actualizar' : 'Guardar' }}</button>
            <button type="button" class="btn btn-cancel" @click="clearInsumoForm()">Cancelar</button>
          </div>
        </form>
      </div>

      <!-- Filtros -->
      <div class="inv-filters">
        <div class="filter-group">
          <label>Categoría:</label>
          <select v-model="filtroCategoria" class="form-input filter-input" @change="loadInsumos()">
            <option value="">Todas</option>
            <option v-for="cat in categorias" :key="cat" :value="cat">{{ cat }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Estado:</label>
          <select v-model="filtroEstado" class="form-input filter-input" @change="loadInsumos()">
            <option value="">Todos</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
          </select>
        </div>
      </div>

      <!-- Tabla de insumos -->
      <div v-if="loading" class="loading-msg">Cargando...</div>
      <div v-else class="table-container">
        <table class="inv-table">
          <thead>
            <tr>
              <th>NOMBRE</th>
              <th>CATEGORÍA</th>
              <th>UNIDAD</th>
              <th>STOCK ACTUAL</th>
              <th>STOCK MÍNIMO</th>
              <th>ESTADO</th>
              <th>ACCIONES</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in insumos" :key="item.id" :class="{ 'row-stock-bajo': item.stock_bajo && item.estado === 'activo', 'row-inactivo': item.estado === 'inactivo' }">
              <td class="fw-bold">{{ item.nombre }}</td>
              <td>{{ item.categoria }}</td>
              <td>{{ item.unidad_medida }}</td>
              <td>
                <span :class="{ 'stock-danger': item.stock_bajo && item.estado === 'activo' }">
                  {{ Number(item.stock_actual).toFixed(3) }}
                </span>
                <span v-if="item.stock_bajo && item.estado === 'activo'" class="stock-badge-mini">⚠️ BAJO</span>
              </td>
              <td>{{ Number(item.stock_minimo).toFixed(3) }}</td>
              <td>
                <span :class="['estado-badge', item.estado === 'activo' ? 'estado-activo' : 'estado-inactivo']">
                  {{ item.estado }}
                </span>
              </td>
              <td class="actions-cell">
                <button class="btn-icon btn-edit" @click="editInsumo(item)" title="Editar">✏️</button>
                <button class="btn-icon" :class="item.estado === 'activo' ? 'btn-deactivate' : 'btn-activate'" @click="toggleInsumo(item)" :title="item.estado === 'activo' ? 'Desactivar' : 'Activar'">
                  {{ item.estado === 'activo' ? '🚫' : '✅' }}
                </button>
              </td>
            </tr>
            <tr v-if="insumos.length === 0">
              <td colspan="7" class="empty-msg">No se encontraron insumos</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- ============================================================ -->
    <!-- RF32: SUB-PESTAÑA ENTRADAS DE MERCADERÍA -->
    <!-- ============================================================ -->
    <section v-if="activeSubTab === 'entradas'" class="inv-section">
      <h2 class="section-title">🚛 Registro de Entradas de Mercadería</h2>

      <!-- Formulario de entrada -->
      <div class="inv-form">
        <h3 style="margin: 0 0 12px; color: #f1af32;">Nueva Entrada</h3>
        <form @submit.prevent="submitEntrada">
          <div class="form-grid-inv">
            <div class="form-group-inv">
              <label>Insumo *</label>
              <select v-model="entradaForm.insumo_id" class="form-input" required>
                <option value="">Seleccionar insumo...</option>
                <option v-for="ins in insumosActivos" :key="ins.id" :value="ins.id">
                  {{ ins.nombre }} ({{ ins.unidad_medida }}) — Stock: {{ Number(ins.stock_actual).toFixed(1) }}
                </option>
              </select>
            </div>
            <div class="form-group-inv">
              <label>Cantidad *</label>
              <input v-model.number="entradaForm.cantidad" type="number" step="0.001" min="0.001" class="form-input" placeholder="Ej. 10.5" required>
            </div>
            <div class="form-group-inv">
              <label>Fecha *</label>
              <input v-model="entradaForm.fecha" type="date" class="form-input" required>
            </div>
            <div class="form-group-inv">
              <label>Costo (S/.) <small>opcional</small></label>
              <input v-model.number="entradaForm.costo" type="number" step="0.01" min="0" class="form-input" placeholder="Ej. 150.00">
            </div>
            <div class="form-group-inv">
              <label>Proveedor <small>opcional</small></label>
              <input v-model="entradaForm.proveedor" class="form-input" placeholder="Ej. Distribuidora Avícola">
            </div>
            <div class="form-group-inv">
              <label>Observación <small>opcional</small></label>
              <input v-model="entradaForm.observacion" class="form-input" placeholder="Nota adicional...">
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">📥 Registrar Entrada</button>
            <button type="button" class="btn btn-cancel" @click="clearEntradaForm()">Limpiar</button>
          </div>
        </form>
      </div>

      <!-- Filtros del historial -->
      <div class="inv-filters" style="margin-top: 20px;">
        <div class="filter-group">
          <label>Insumo:</label>
          <select v-model="filtroEntradaInsumo" class="form-input filter-input" @change="loadEntradas()">
            <option value="">Todos</option>
            <option v-for="ins in insumosActivos" :key="ins.id" :value="ins.id">{{ ins.nombre }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Desde:</label>
          <input v-model="filtroEntradaFrom" type="date" class="form-input filter-input" @change="loadEntradas()">
        </div>
        <div class="filter-group">
          <label>Hasta:</label>
          <input v-model="filtroEntradaTo" type="date" class="form-input filter-input" @change="loadEntradas()">
        </div>
      </div>

      <!-- Tabla de entradas -->
      <div class="table-container">
        <table class="inv-table">
          <thead>
            <tr>
              <th>FECHA</th>
              <th>INSUMO</th>
              <th>CANTIDAD</th>
              <th>COSTO</th>
              <th>PROVEEDOR</th>
              <th>OBSERVACIÓN</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="e in entradas" :key="e.id">
              <td>{{ e.fecha }}</td>
              <td class="fw-bold">{{ e.insumo?.nombre || '—' }}</td>
              <td>{{ Number(e.cantidad).toFixed(3) }} {{ e.insumo?.unidad_medida || '' }}</td>
              <td>{{ e.costo ? 'S/. ' + Number(e.costo).toFixed(2) : '—' }}</td>
              <td>{{ e.proveedor || '—' }}</td>
              <td>{{ e.observacion || '—' }}</td>
            </tr>
            <tr v-if="entradas.length === 0">
              <td colspan="6" class="empty-msg">No hay entradas registradas</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- ============================================================ -->
    <!-- RF33: SUB-PESTAÑA RECETAS -->
    <!-- ============================================================ -->
    <section v-if="activeSubTab === 'recetas'" class="inv-section">
      <h2 class="section-title">📋 Gestión de Recetas</h2>
      <p class="section-desc">Define qué insumos y en qué cantidad se requieren para preparar cada producto del menú.</p>

      <!-- Selector de producto -->
      <div class="inv-form">
        <div class="form-group-inv" style="max-width: 400px;">
          <label>Seleccionar Producto del Menú</label>
          <select v-model="selectedMenuId" class="form-input" @change="loadReceta()">
            <option value="">— Seleccionar producto —</option>
            <option v-for="m in menuItems" :key="m.id" :value="m.id">
              {{ m.nombre }} — S/. {{ Number(m.precio).toFixed(2) }}
            </option>
          </select>
        </div>

        <!-- Receta del producto seleccionado -->
        <div v-if="selectedMenuId" class="receta-editor">
          <div class="receta-header">
            <h3 style="margin: 0; color: #333;">Ingredientes de "{{ recetaMenuNombre || '...' }}"</h3>
            <button class="btn btn-small btn-primary" @click="addIngrediente()">+ Agregar Ingrediente</button>
          </div>

          <div v-if="recetaIngredientes.length === 0" class="empty-receta">
            <p>Este producto no tiene receta definida. Agrega ingredientes para poder descontar stock automáticamente.</p>
          </div>

          <div v-for="(ing, index) in recetaIngredientes" :key="index" class="ingrediente-row">
            <div class="ing-field">
              <label>Insumo</label>
              <select v-model="ing.insumo_id" class="form-input" @change="onIngredienteInsumoChange(index)">
                <option value="">Seleccionar...</option>
                <option v-for="ins in insumosActivos" :key="ins.id" :value="ins.id">
                  {{ ins.nombre }} ({{ ins.unidad_medida }})
                </option>
              </select>
            </div>
            <div class="ing-field ing-field-small">
              <label>Cantidad</label>
              <input v-model.number="ing.cantidad_requerida" type="number" step="0.001" min="0.001" class="form-input" placeholder="0.000">
            </div>
            <div class="ing-field ing-field-unit">
              <label>Unidad</label>
              <span class="unit-label">{{ ing.unidad_medida || '—' }}</span>
            </div>
            <button class="btn-icon btn-remove" @click="removeIngrediente(index)" title="Eliminar">🗑️</button>
          </div>

          <div v-if="recetaIngredientes.length > 0" class="form-actions" style="margin-top: 16px;">
            <button class="btn btn-primary" @click="submitReceta()">💾 Guardar Receta</button>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================ -->
    <!-- RF34: SUB-PESTAÑA ALERTAS -->
    <!-- ============================================================ -->
    <section v-if="activeSubTab === 'alertas'" class="inv-section">
      <h2 class="section-title">🔔 Alertas de Stock Mínimo</h2>

      <div v-if="alertas.length === 0" class="empty-alertas">
        <div class="empty-icon">✅</div>
        <p>Todos los insumos tienen stock por encima del mínimo.</p>
      </div>

      <div v-else class="alertas-grid">
        <div v-for="a in alertas" :key="a.id" :class="['alerta-card', getNivelClass(a.nivel)]">
          <div class="alerta-header">
            <span class="alerta-nivel">{{ getNivelLabel(a.nivel) }}</span>
            <span class="alerta-categoria">{{ a.categoria }}</span>
          </div>
          <h3 class="alerta-nombre">{{ a.nombre }}</h3>
          <div class="alerta-stocks">
            <div class="alerta-stock-item">
              <span class="alerta-stock-label">Stock Actual</span>
              <span class="alerta-stock-value">{{ Number(a.stock_actual).toFixed(3) }} {{ a.unidad_medida }}</span>
            </div>
            <div class="alerta-stock-item">
              <span class="alerta-stock-label">Stock Mínimo</span>
              <span class="alerta-stock-value">{{ Number(a.stock_minimo).toFixed(3) }} {{ a.unidad_medida }}</span>
            </div>
          </div>
          <div class="alerta-bar-container">
            <div class="alerta-bar" :style="{ width: Math.min(a.porcentaje, 100) + '%' }" :class="getNivelClass(a.nivel)"></div>
          </div>
          <span class="alerta-porcentaje">{{ a.porcentaje }}% del mínimo</span>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* ================================================================
   INVENTARIO PANEL - Estilos consistentes con AdminView
   ================================================================ */
.inventario-panel {
  width: 100%;
}

/* Sub-pestañas */
.sub-tabs {
  display: flex;
  gap: 6px;
  margin-bottom: 18px;
  border-bottom: 2px solid #eee;
  padding-bottom: 0;
}
.sub-tab {
  padding: 10px 18px;
  border: none;
  background: transparent;
  cursor: pointer;
  font-weight: 600;
  color: #888;
  font-size: 0.95rem;
  border-bottom: 3px solid transparent;
  transition: all 0.2s ease;
  position: relative;
}
.sub-tab:hover {
  color: #f1af32;
}
.sub-tab.active {
  color: #f1af32;
  border-bottom-color: #f1af32;
}
.alert-badge {
  position: absolute;
  top: 4px;
  right: 2px;
  background: #e53935;
  color: white;
  font-size: 0.7rem;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

/* Secciones */
.inv-section {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
}
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.section-title {
  font-size: 18px;
  color: #f1af32;
  margin: 0 0 12px 0;
  border-bottom: 2px solid #ffcc80;
  padding-bottom: 8px;
}
.section-desc {
  color: #666;
  font-size: 0.9rem;
  margin: -8px 0 16px 0;
}

/* Formularios */
.inv-form {
  background: #fefcf7;
  border: 1px solid #ffe0b2;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 18px;
}
.form-grid-inv {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 14px;
  margin-bottom: 14px;
}
.form-group-inv {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.form-group-inv label {
  font-weight: 600;
  color: #444;
  font-size: 0.85rem;
}
.form-group-inv label small {
  font-weight: 400;
  color: #999;
}
.form-input {
  padding: 9px 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.95rem;
  background: white;
  transition: border-color 0.2s;
}
.form-input:focus {
  outline: none;
  border-color: #f1af32;
  box-shadow: 0 0 0 3px rgba(241, 175, 50, 0.15);
}
.form-actions {
  display: flex;
  gap: 10px;
}

/* Botones */
.btn {
  padding: 9px 16px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.9rem;
  transition: all 0.2s;
}
.btn-primary {
  background: #f1af32;
  color: white;
}
.btn-primary:hover {
  background: #e6a42b;
}
.btn-cancel {
  background: #eee;
  color: #666;
}
.btn-cancel:hover {
  background: #ddd;
}
.btn-small {
  padding: 6px 12px;
  font-size: 0.85rem;
}
.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.1rem;
  padding: 4px 6px;
  border-radius: 6px;
  transition: background 0.2s;
}
.btn-icon:hover {
  background: #f5f5f5;
}
.btn-edit:hover { background: #e3f2fd; }
.btn-deactivate:hover { background: #ffebee; }
.btn-activate:hover { background: #e8f5e9; }
.btn-remove:hover { background: #ffebee; }

/* Filtros */
.inv-filters {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  margin-bottom: 14px;
  align-items: flex-end;
}
.filter-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.filter-group label {
  font-size: 0.8rem;
  color: #888;
  font-weight: 600;
}
.filter-input {
  min-width: 160px;
}

/* Tablas */
.table-container {
  overflow-x: auto;
}
.inv-table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #e0e0e0;
}
.inv-table th {
  background-color: #f1af32;
  color: white;
  text-transform: uppercase;
  font-size: 0.78rem;
  font-weight: 700;
  padding: 10px 12px;
  text-align: left;
  border: 1px solid #e6a830;
}
.inv-table td {
  padding: 10px 12px;
  border: 1px solid #e0e0e0;
  font-size: 0.9rem;
  vertical-align: middle;
}
.inv-table tr:hover {
  background-color: #fffdf5;
}
.fw-bold {
  font-weight: 700;
  color: #333;
}
.empty-msg {
  text-align: center;
  color: #999;
  padding: 30px !important;
  font-style: italic;
}
.loading-msg {
  text-align: center;
  color: #f1af32;
  padding: 30px;
  font-weight: 600;
}
.actions-cell {
  white-space: nowrap;
}

/* Stock indicators */
.row-stock-bajo {
  background-color: #fff8f0 !important;
}
.row-stock-bajo:hover {
  background-color: #fff3e0 !important;
}
.row-inactivo {
  opacity: 0.55;
}
.stock-danger {
  color: #e53935;
  font-weight: 700;
}
.stock-badge-mini {
  font-size: 0.7rem;
  font-weight: 700;
  margin-left: 6px;
  color: #ff6f00;
}
.estado-badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
}
.estado-activo {
  background: #e8f5e9;
  color: #2e7d32;
}
.estado-inactivo {
  background: #ffebee;
  color: #c62828;
}

/* Recetas */
.receta-editor {
  margin-top: 18px;
  border-top: 1px solid #ffe0b2;
  padding-top: 16px;
}
.receta-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}
.ingrediente-row {
  display: flex;
  gap: 12px;
  align-items: flex-end;
  margin-bottom: 10px;
  padding: 10px;
  background: white;
  border: 1px solid #eee;
  border-radius: 8px;
}
.ing-field {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.ing-field label {
  font-size: 0.78rem;
  color: #888;
  font-weight: 600;
}
.ing-field-small {
  flex: 0 0 120px;
}
.ing-field-unit {
  flex: 0 0 80px;
}
.unit-label {
  padding: 9px 0;
  color: #666;
  font-size: 0.9rem;
}
.empty-receta {
  text-align: center;
  color: #999;
  padding: 20px;
  background: #fafafa;
  border: 1px dashed #ddd;
  border-radius: 8px;
}

/* RF34: Alertas */
.alertas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}
.alerta-card {
  border-radius: 12px;
  padding: 16px;
  border-left: 5px solid;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.alerta-card.nivel-agotado {
  background: #ffebee;
  border-left-color: #c62828;
}
.alerta-card.nivel-critico {
  background: #fff3e0;
  border-left-color: #e65100;
}
.alerta-card.nivel-bajo {
  background: #fff8e1;
  border-left-color: #f9a825;
}
.alerta-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}
.alerta-nivel {
  font-weight: 700;
  font-size: 0.8rem;
}
.alerta-categoria {
  font-size: 0.75rem;
  color: #888;
  background: #f5f5f5;
  padding: 2px 8px;
  border-radius: 10px;
}
.alerta-nombre {
  margin: 0 0 10px 0;
  font-size: 1.05rem;
  color: #333;
}
.alerta-stocks {
  display: flex;
  gap: 20px;
  margin-bottom: 10px;
}
.alerta-stock-item {
  display: flex;
  flex-direction: column;
}
.alerta-stock-label {
  font-size: 0.72rem;
  color: #888;
  text-transform: uppercase;
  font-weight: 600;
}
.alerta-stock-value {
  font-size: 1rem;
  font-weight: 700;
  color: #333;
}
.alerta-bar-container {
  height: 6px;
  background: #eee;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 4px;
}
.alerta-bar {
  height: 100%;
  border-radius: 3px;
  transition: width 0.3s ease;
}
.alerta-bar.nivel-agotado { background: #c62828; }
.alerta-bar.nivel-critico { background: #e65100; }
.alerta-bar.nivel-bajo { background: #f9a825; }
.alerta-porcentaje {
  font-size: 0.75rem;
  color: #888;
}

.empty-alertas {
  text-align: center;
  padding: 40px;
  color: #888;
}
.empty-icon {
  font-size: 3rem;
  margin-bottom: 10px;
}

@media (max-width: 768px) {
  .form-grid-inv {
    grid-template-columns: 1fr;
  }
  .ingrediente-row {
    flex-wrap: wrap;
  }
  .sub-tabs {
    overflow-x: auto;
  }
  .alertas-grid {
    grid-template-columns: 1fr;
  }
}
</style>
