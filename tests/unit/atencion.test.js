/**
 * Pruebas Unitarias del Módulo de Atención
 * Sistema de Atención de la Pollería El Hornero
 * 
 * Casos de Prueba:
 * - CP-U01 (RF03): Validación de cantidad
 * - CP-U02 (RF01): Cálculo de subtotal
 * - CP-U03 (RF03): Verificación de mesa seleccionada
 */

// ============================================
// CP-U01 (RF03): Validar cantidad > 0
// ============================================
describe('CP-U01 (RF03): Validación de Cantidad en Pedidos', () => {
  /**
   * Función simulada que valida la cantidad
   * @param {number} cantidad - Cantidad de ítems
   * @throws {Error} Si la cantidad es menor o igual a 0
   */
  const validarCantidad = (cantidad) => {
    if (cantidad <= 0) {
      throw new Error('La cantidad debe ser mayor a 0');
    }
    return true;
  };

  test('Debe bloquear pedidos con cantidad cero', () => {
    expect(() => validarCantidad(0)).toThrow(
      'La cantidad debe ser mayor a 0'
    );
  });

  test('Debe bloquear pedidos con cantidad negativa', () => {
    expect(() => validarCantidad(-1)).toThrow(
      'La cantidad debe ser mayor a 0'
    );
  });

  test('Debe permitir pedidos con cantidad válida', () => {
    expect(() => validarCantidad(1)).not.toThrow();
    expect(() => validarCantidad(5)).not.toThrow();
  });

  test('Debe lanzar error específico con el mensaje requerido', () => {
    try {
      validarCantidad(0);
      fail('Debería haber lanzado un error');
    } catch (error) {
      expect(error.message).toBe('La cantidad debe ser mayor a 0');
    }
  });
});

// ============================================
// CP-U02 (RF01): Cálculo exacto de subtotal
// ============================================
describe('CP-U02 (RF01): Cálculo de Subtotal de Ítems', () => {
  /**
   * Calcula el subtotal de un ítem
   * @param {number} precioUnitario - Precio por unidad
   * @param {number} cantidad - Cantidad de ítems
   * @returns {number} Subtotal redondeado a 2 decimales
   */
  const calcularSubtotalItem = (precioUnitario, cantidad) => {
    return parseFloat((precioUnitario * cantidad).toFixed(2));
  };

  test('Debe calcular correctamente: Monstrito S/ 18.00 x 2 = S/ 36.00', () => {
    const resultado = calcularSubtotalItem(18.00, 2);
    expect(resultado).toBe(36.00);
  });

  test('Debe calcular correctamente: Cuarto de Pollo S/ 15.00 x 3 = S/ 45.00', () => {
    const resultado = calcularSubtotalItem(15.00, 3);
    expect(resultado).toBe(45.00);
  });

  test('Debe calcular correctamente: Medio Pollo S/ 25.00 x 1 = S/ 25.00', () => {
    const resultado = calcularSubtotalItem(25.00, 1);
    expect(resultado).toBe(25.00);
  });

  test('Debe redondear correctamente cuando hay decimales', () => {
    const resultado = calcularSubtotalItem(10.00, 3);
    expect(resultado).toBe(30.00);
  });

  test('Debe manejar precios con decimales: S/ 5.50 x 2 = S/ 11.00', () => {
    const resultado = calcularSubtotalItem(5.50, 2);
    expect(resultado).toBe(11.00);
  });

  test('Debe retornar 0 cuando la cantidad es 0', () => {
    const resultado = calcularSubtotalItem(18.00, 0);
    expect(resultado).toBe(0);
  });
});

// ============================================
// CP-U03 (RF03): Verificación de mesa seleccionada
// ============================================
describe('CP-U03 (RF03): Verificación de Mesa Seleccionada', () => {
  /**
   * Estados posibles de una mesa
   */
  const ESTADOS_MESA = {
    NO_SELECCIONADA: 'No seleccionada',
    LIBRE: 'Libre',
    OCUPADA: 'Ocupada',
  };

  /**
   * Verifica que una mesa esté seleccionada antes de enviar orden
   * @param {Object} mesa - Objeto con datos de la mesa
   * @throws {Error} Si la mesa no está seleccionada
   */
  const verificarMesaSeleccionada = (mesa) => {
    if (!mesa || mesa.estado === ESTADOS_MESA.NO_SELECCIONADA) {
      throw new Error('Debe seleccionar una mesa antes de enviar la orden');
    }
    return true;
  };

  test('Debe bloquear orden cuando no se selecciona mesa', () => {
    expect(() => verificarMesaSeleccionada(null)).toThrow(
      'Debe seleccionar una mesa antes de enviar la orden'
    );
  });

  test('Debe bloquear orden cuando mesa está en estado "No seleccionada"', () => {
    const mesaNoSeleccionada = { id: 1, numero: 1, estado: ESTADOS_MESA.NO_SELECCIONADA };
    expect(() => verificarMesaSeleccionada(mesaNoSeleccionada)).toThrow(
      'Debe seleccionar una mesa antes de enviar la orden'
    );
  });

  test('Debe permitir orden cuando mesa está en estado "Libre"', () => {
    const mesaLibre = { id: 1, numero: 1, estado: ESTADOS_MESA.LIBRE };
    expect(() => verificarMesaSeleccionada(mesaLibre)).not.toThrow();
  });

  test('Debe permitir orden cuando mesa está en estado "Ocupada"', () => {
    const mesaOcupada = { id: 2, numero: 2, estado: ESTADOS_MESA.OCUPADA };
    expect(() => verificarMesaSeleccionada(mesaOcupada)).not.toThrow();
  });

  test('Debe lanzar error específico cuando se intenta sin mesa', () => {
    try {
      verificarMesaSeleccionada(null);
      fail('Debería haber lanzado un error');
    } catch (error) {
      expect(error.message).toBe('Debe seleccionar una mesa antes de enviar la orden');
    }
  });
});
