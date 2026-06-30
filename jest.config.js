/**
 * Configuración de Jest
 * Sistema de Atención de la Pollería El Hornero
 */

module.exports = {
  // Ambiente de prueba
  testEnvironment: 'node',

  // Raíz de búsqueda de tests
  rootDir: './',

  // Patrones de archivos a probar
  testMatch: [
    '**/tests/**/*.test.js',
    '**/tests/**/*.spec.js',
  ],

  // Cobertura de código
  collectCoverage: false,
  collectCoverageFrom: [
    'tests/**/*.js',
  ],
  coveragePathIgnorePatterns: [
    '/node_modules/',
    '/vendor/',
  ],

  // Configuración de módulos
  moduleFileExtensions: ['js', 'json', 'node'],

  // Setup global
  setupFilesAfterEnv: ['<rootDir>/tests/setup.js'],

  // Verbosidad
  verbose: true,

  // Timeout para pruebas asincrónicas
  testTimeout: 10000,

  // Transformación de archivos
  transform: {},

  // Mapeo de módulos (si es necesario)
  moduleNameMapper: {},

  // Ignorar archivos
  testPathIgnorePatterns: [
    '/node_modules/',
    '/vendor/',
    '/.git/',
  ],
};
