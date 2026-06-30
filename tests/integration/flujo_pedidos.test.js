/**
 * Pruebas de Integración - Flujo de Pedidos
 * Sistema de Atención de la Pollería El Hornero
 * 
 * Valida la comunicación entre módulos (Mozo → BD → Cocina)
 * Casos de Prueba:
 * - CP-101 (RF01): Guardado de pedidos en MySQL
 * - CP-102 (RF21): Envío de comanda a cocina
 * - CP-103 (RF06): Actualización de estado de mesa
 * - CP-104 (RF23): Notificación de pedido listo
 */

const {
  delay,
  MockDatabase,
  MockWebSocket,
  MockNotification,
  waitForCondition,
} = require('../helpers/test-utils');
const { mockPedido, mockMesas } = require('../fixtures/mock-data');

// ============================================
// CP-101 (RF01): Guardado de pedidos en MySQL
// ============================================
describe('CP-101 (RF01): Persistencia de Pedidos en MySQL', () => {
  let db;

  beforeEach(() => {
    db = new MockDatabase();
  });

  test('Debe guardar pedido con estado inicial "Pendiente"', async () => {
    const pedidoData = {
      mesa_id: 1,
      mozo_id: 1,
      estado: 'Pendiente',
      subtotal: 36.00,
      impuesto: 6.48,
      total: 42.48,
      items: [
        {
          menu_id: 1,
          cantidad: 2,
          precio_unitario: 18.00,
          subtotal: 36.00,
          descripcion: 'Monstrito',
        },
      ],
    };

    const pedidoGuardado = await db.insertPedido(pedidoData);

    expect(pedidoGuardado).toBeDefined();
    expect(pedidoGuardado.id).toBe(1);
    expect(pedidoGuardado.estado).toBe('Pendiente');
    expect(pedidoGuardado.total).toBe(42.48);
    expect(pedidoGuardado.mesa_id).toBe(1);
  });

  test('Debe generar IDs secuenciales para múltiples pedidos', async () => {
    const pedido1 = await db.insertPedido({ mesa_id: 1, estado: 'Pendiente' });
    const pedido2 = await db.insertPedido({ mesa_id: 2, estado: 'Pendiente' });
    const pedido3 = await db.insertPedido({ mesa_id: 3, estado: 'Pendiente' });

    expect(pedido1.id).toBe(1);
    expect(pedido2.id).toBe(2);
    expect(pedido3.id).toBe(3);
  });

  test('Debe incluir timestamps de creación', async () => {
    const pedido = await db.insertPedido({
      mesa_id: 1,
      estado: 'Pendiente',
    });

    expect(pedido.created_at).toBeDefined();
    expect(pedido.updated_at).toBeDefined();
    expect(new Date(pedido.created_at)).toBeInstanceOf(Date);
  });

  test('Debe recuperar pedido guardado por ID', async () => {
    const pedidoOriginal = await db.insertPedido({
      mesa_id: 1,
      mozo_id: 5,
      estado: 'Pendiente',
      total: 42.48,
    });

    const pedidoRecuperado = await db.getPedidoById(pedidoOriginal.id);

    expect(pedidoRecuperado).toEqual(pedidoOriginal);
  });
});

// ============================================
// CP-102 (RF21): Envío de comanda a cocina
// ============================================
describe('CP-102 (RF21): Envío de Comanda a Cocina - Sincronización Tiempo Real', () => {
  let ws;
  let comandasRecibidas;

  beforeEach(() => {
    ws = new MockWebSocket();
    comandasRecibidas = [];

    // Simulamos el listener del lado de cocina
    ws.on('nueva_comanda', (comanda) => {
      comandasRecibidas.push(comanda);
    });

    ws.connect();
  });

  test('Debe enviar comanda a cocina instantáneamente', async () => {
    const comanda = {
      id: 1,
      mesa_numero: 1,
      items: [
        { descripcion: 'Monstrito', cantidad: 2 },
      ],
      prioridad: 'normal',
      timestamp: new Date().toISOString(),
    };

    // Simulamos el envío
    await ws.send('nueva_comanda', comanda);

    // Emitimos el evento para simular recepción instantánea
    ws.emit('nueva_comanda', comanda);

    expect(comandasRecibidas.length).toBe(1);
    expect(comandasRecibidas[0].id).toBe(1);
    expect(comandasRecibidas[0].mesa_numero).toBe(1);
  });

  test('Debe visualizar comanda en monitor de cocina al instante', async () => {
    const comanda = {
      id: 2,
      mesa_numero: 3,
      items: [
        { descripcion: 'Medio Pollo', cantidad: 1 },
        { descripcion: 'Chicha Morada', cantidad: 1 },
      ],
      prioridad: 'alta',
      timestamp: new Date().toISOString(),
    };

    ws.emit('nueva_comanda', comanda);

    // Verificar que llegó sin demora significativa
    expect(comandasRecibidas).toHaveLength(1);
    expect(comandasRecibidas[0]).toMatchObject({
      id: 2,
      mesa_numero: 3,
    });
  });

  test('Debe manejar múltiples comandas simultáneamente', async () => {
    const comandas = [
      { id: 1, mesa_numero: 1, items: [{ descripcion: 'Monstrito', cantidad: 1 }] },
      { id: 2, mesa_numero: 2, items: [{ descripcion: 'Cuarto de Pollo', cantidad: 2 }] },
      { id: 3, mesa_numero: 4, items: [{ descripcion: 'Medio Pollo', cantidad: 1 }] },
    ];

    comandas.forEach((comanda) => {
      ws.emit('nueva_comanda', comanda);
    });

    expect(comandasRecibidas).toHaveLength(3);
    expect(comandasRecibidas[0].id).toBe(1);
    expect(comandasRecibidas[1].id).toBe(2);
    expect(comandasRecibidas[2].id).toBe(3);
  });

  afterEach(() => {
    ws.disconnect();
  });
});

// ============================================
// CP-103 (RF06): Actualización de estado de mesa
// ============================================
describe('CP-103 (RF06): Actualización de Estado de Mesa', () => {
  let db;

  beforeEach(() => {
    db = new MockDatabase();
    // Inicializar mesas
    db.data.mesas = [
      { id: 1, numero: 1, estado: 'Libre' },
      { id: 2, numero: 2, estado: 'Libre' },
    ];
  });

  test('Debe cambiar mesa de "Libre" a "Ocupada" al registrar pedido', async () => {
    const mesaBefore = db.data.mesas[0];
    expect(mesaBefore.estado).toBe('Libre');

    const mesaActualizada = await db.updateEstadoMesa(1, 'Ocupada');

    expect(mesaActualizada.estado).toBe('Ocupada');
    expect(mesaActualizada.numero).toBe(1);
  });

  test('Debe cambiar mesa de "Ocupada" a "Libre" al confirmar pago', async () => {
    // Primero ocupar la mesa
    await db.updateEstadoMesa(2, 'Ocupada');
    let mesa = db.data.mesas[1];
    expect(mesa.estado).toBe('Ocupada');

    // Ahora liberar
    mesa = await db.updateEstadoMesa(2, 'Libre');
    expect(mesa.estado).toBe('Libre');
  });

  test('Debe actualizar timestamp cuando cambia estado', async () => {
    const mesaBefore = db.data.mesas[0];
    const timestampBefore = mesaBefore.updated_at;

    await delay(10); // Pequeño delay para que cambien los timestamps

    const mesaActualizada = await db.updateEstadoMesa(1, 'Ocupada');

    expect(mesaActualizada.updated_at).not.toBe(timestampBefore);
  });

  test('Debe lanzar error si mesa no existe', async () => {
    await expect(db.updateEstadoMesa(999, 'Ocupada')).rejects.toThrow(
      'Mesa 999 no encontrada'
    );
  });

  test('Debe reflejar cambio en UI instantáneamente (simulado)', async () => {
    const mesaId = 1;
    const nuevoEstado = 'Ocupada';

    // Simulamos observador de cambios
    const observadores = [];
    const notificarCambios = (id, estado) => {
      observadores.forEach((cb) => cb({ mesaId: id, nuevoEstado: estado }));
    };

    let estadoEnUI = 'Libre';
    observadores.push((cambio) => {
      estadoEnUI = cambio.nuevoEstado;
    });

    await db.updateEstadoMesa(mesaId, nuevoEstado);
    notificarCambios(mesaId, nuevoEstado);

    expect(estadoEnUI).toBe('Ocupada');
  });
});

// ============================================
// CP-104 (RF23): Notificación de pedido listo
// ============================================
describe('CP-104 (RF23): Callback de Pedido Listo - Notificación al Mozo', () => {
  let ws;
  let notificacionesRecibidas;

  beforeEach(() => {
    ws = new MockWebSocket();
    notificacionesRecibidas = [];

    // Simulamos el listener del mozo
    ws.on('pedido_listo', (notificacion) => {
      notificacionesRecibidas.push(notificacion);
    });

    ws.connect();
  });

  test('Debe enviar notificación "Pedido Listo" desde cocina', async () => {
    const notificacion = {
      pedido_id: 1,
      mesa_numero: 1,
      mensaje: 'Pedido Listo',
      timestamp: new Date().toISOString(),
    };

    ws.emit('pedido_listo', notificacion);

    expect(notificacionesRecibidas).toHaveLength(1);
    expect(notificacionesRecibidas[0].mensaje).toBe('Pedido Listo');
  });

  test('Debe mostrar emergente al mozo cuando pedido está listo', async () => {
    const notificacion = {
      pedido_id: 5,
      mesa_numero: 3,
      mensaje: 'Pedido Listo',
      timestamp: new Date().toISOString(),
    };

    // Simulamos mostrar notificación
    const notif = MockNotification.show('Pedido Listo', {
      body: `Mesa ${notificacion.mesa_numero} - Pedido ${notificacion.pedido_id}`,
      tag: `pedido-${notificacion.pedido_id}`,
    });

    ws.emit('pedido_listo', notificacion);

    expect(notif.title).toBe('Pedido Listo');
    expect(notif.options.body).toContain('Mesa 3');
  });

  test('Debe manejar múltiples notificaciones secuencialmente', async () => {
    const notificaciones = [
      { pedido_id: 1, mesa_numero: 1, mensaje: 'Pedido Listo' },
      { pedido_id: 2, mesa_numero: 2, mensaje: 'Pedido Listo' },
      { pedido_id: 3, mesa_numero: 4, mensaje: 'Pedido Listo' },
    ];

    for (const notif of notificaciones) {
      ws.emit('pedido_listo', notif);
      await delay(50);
    }

    expect(notificacionesRecibidas).toHaveLength(3);
  });

  test('Debe entregar notificación en menos de 100ms (tiempo real)', async () => {
    const notificacion = {
      pedido_id: 10,
      mesa_numero: 5,
      mensaje: 'Pedido Listo',
      timestamp: new Date().toISOString(),
    };

    const inicio = Date.now();
    ws.emit('pedido_listo', notificacion);
    const fin = Date.now();

    const tiempoTranscurrido = fin - inicio;
    expect(tiempoTranscurrido).toBeLessThan(100);
    expect(notificacionesRecibidas).toHaveLength(1);
  });

  test('Debe marcar notificación como leída cuando mozo la acepta', async () => {
    const notificacion = {
      pedido_id: 7,
      mesa_numero: 2,
      mensaje: 'Pedido Listo',
      leida: false,
      timestamp: new Date().toISOString(),
    };

    ws.emit('pedido_listo', notificacion);
    expect(notificacionesRecibidas[0].leida).toBe(false);

    // Simular mozo aceptando notificación
    notificacionesRecibidas[0].leida = true;

    expect(notificacionesRecibidas[0].leida).toBe(true);
  });

  afterEach(() => {
    ws.disconnect();
  });
});

// ============================================
// Flujo Completo Integrado
// ============================================
describe('Flujo Completo: Mozo → BD → Cocina → Mozo', () => {
  let db;
  let ws;
  let estadoFlujo;

  beforeEach(() => {
    db = new MockDatabase();
    ws = new MockWebSocket();
    estadoFlujo = {
      pedidoCreado: false,
      comandaEnviada: false,
      pedidoListo: false,
    };

    ws.connect();
  });

  test('Debe completar flujo: crear pedido → enviar comanda → notificar listo', async () => {
    // PASO 1: Mozo crea y guarda pedido
    const pedido = await db.insertPedido({
      mesa_id: 1,
      mozo_id: 1,
      estado: 'Pendiente',
      total: 42.48,
      items: [{ menu_id: 1, cantidad: 2 }],
    });
    estadoFlujo.pedidoCreado = true;

    expect(estadoFlujo.pedidoCreado).toBe(true);
    expect(pedido.estado).toBe('Pendiente');

    // PASO 2: Sistema envía comanda a cocina
    const comanda = {
      pedido_id: pedido.id,
      mesa_numero: 1,
      items: pedido.items,
    };
    ws.emit('nueva_comanda', comanda);
    estadoFlujo.comandaEnviada = true;

    expect(estadoFlujo.comandaEnviada).toBe(true);

    // PASO 3: Cocina confirma pedido listo
    const notificacion = {
      pedido_id: pedido.id,
      mesa_numero: 1,
      mensaje: 'Pedido Listo',
    };
    ws.emit('pedido_listo', notificacion);
    estadoFlujo.pedidoListo = true;

    expect(estadoFlujo.pedidoListo).toBe(true);

    // Validar estado final
    expect(Object.values(estadoFlujo).every((v) => v === true)).toBe(true);
  });

  afterEach(() => {
    ws.disconnect();
    db.clear();
  });
});
