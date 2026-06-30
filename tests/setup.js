/**
 * Configuración Global de Jest
 * Define timeouts, globals y configuración para todas las pruebas
 */

// Aumentar timeout para pruebas asincrónicas (10 segundos)
jest.setTimeout(10000);

// Mock global de console (opcional, descomentar si es necesario)
// global.console = {
//   ...console,
//   log: jest.fn(),
//   debug: jest.fn(),
//   error: jest.fn(),
//   warn: jest.fn(),
// };

// Limpiar todos los mocks después de cada prueba
afterEach(() => {
  jest.clearAllMocks();
});

// Suprimir advertencias no deseadas durante las pruebas
// (Agregar según sea necesario)
global.suppressDeprecatedWarnings = true;
