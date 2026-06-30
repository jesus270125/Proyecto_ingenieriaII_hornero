/**
 * Utilidades y Helpers para Pruebas
 * Contiene funciones reutilizables para mocks, esperas, etc.
 */

/**
 * Simula una espera asincrónica
 * @param {number} ms - Milisegundos a esperar
 */
const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/**
 * Mock de la base de datos MySQL
 * Simula operaciones de inserción y actualización
 */
class MockDatabase {
  constructor() {
    this.data = {
      pedidos: [],
      mesas: [],
    };
  }

  async insertPedido(pedidoData) {
    const newPedido = {
      id: this.data.pedidos.length + 1,
      ...pedidoData,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    };
    this.data.pedidos.push(newPedido);
    return newPedido;
  }

  async updateEstadoMesa(mesaId, nuevoEstado) {
    const mesa = this.data.mesas.find((m) => m.id === mesaId);
    if (mesa) {
      mesa.estado = nuevoEstado;
      mesa.updated_at = new Date().toISOString();
      return mesa;
    }
    throw new Error(`Mesa ${mesaId} no encontrada`);
  }

  async getPedidoById(pedidoId) {
    return this.data.pedidos.find((p) => p.id === pedidoId);
  }

  clear() {
    this.data = { pedidos: [], mesas: [] };
  }
}

/**
 * Mock de WebSocket para comunicación tiempo real
 */
class MockWebSocket {
  constructor() {
    this.listeners = {};
    this.isConnected = false;
  }

  connect() {
    this.isConnected = true;
    this.emit('connected', { message: 'Conectado al servidor' });
  }

  disconnect() {
    this.isConnected = false;
    this.emit('disconnected', {});
  }

  on(event, callback) {
    if (!this.listeners[event]) {
      this.listeners[event] = [];
    }
    this.listeners[event].push(callback);
  }

  emit(event, data) {
    if (this.listeners[event]) {
      this.listeners[event].forEach((callback) => callback(data));
    }
  }

  send(event, data) {
    // Simula envío de datos
    return Promise.resolve({ success: true, ...data });
  }
}

/**
 * Mock de Notificación (para mozo)
 */
class MockNotification {
  static show(title, options) {
    return {
      title,
      options,
      timestamp: new Date().toISOString(),
    };
  }
}

/**
 * Espera a que una condición sea verdadera
 * Útil para pruebas asincrónicas
 * @param {Function} condition - Función que retorna boolean
 * @param {number} timeout - Timeout en ms (default 5000)
 */
async function waitForCondition(condition, timeout = 5000) {
  const startTime = Date.now();
  while (!condition()) {
    if (Date.now() - startTime > timeout) {
      throw new Error(`Condición no se cumplió dentro de ${timeout}ms`);
    }
    await delay(100);
  }
}

/**
 * Crear un mock de respuesta HTTP
 */
function createMockResponse(data, statusCode = 200) {
  return {
    status: statusCode,
    data,
    success: statusCode >= 200 && statusCode < 300,
  };
}

module.exports = {
  delay,
  MockDatabase,
  MockWebSocket,
  MockNotification,
  waitForCondition,
  createMockResponse,
};
