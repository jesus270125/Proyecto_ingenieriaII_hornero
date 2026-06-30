/**
 * Mock Data para Pruebas del Sistema de Atención
 * Simula datos de mesas, pedidos, ítems y usuarios
 */

const mockMesas = [
  {
    id: 1,
    numero: 1,
    capacidad: 2,
    estado: 'Libre',
    mozo_id: null,
    created_at: new Date().toISOString(),
  },
  {
    id: 2,
    numero: 2,
    capacidad: 4,
    estado: 'Libre',
    mozo_id: null,
    created_at: new Date().toISOString(),
  },
  {
    id: 3,
    numero: 3,
    capacidad: 6,
    estado: 'Ocupada',
    mozo_id: 5,
    created_at: new Date().toISOString(),
  },
];

const mockMenu = [
  {
    id: 1,
    nombre: 'Monstrito',
    precio: 18.00,
    descripcion: 'Pollo con papas y ensalada',
    categoria: 'Platos Principales',
    disponible: true,
  },
  {
    id: 2,
    nombre: 'Cuarto de Pollo',
    precio: 15.00,
    descripcion: 'Un cuarto de pollo con guarnición',
    categoria: 'Platos Principales',
    disponible: true,
  },
  {
    id: 3,
    nombre: 'Medio Pollo',
    precio: 25.00,
    descripcion: 'Medio pollo con guarnición completa',
    categoria: 'Platos Principales',
    disponible: true,
  },
  {
    id: 4,
    nombre: 'Chicha Morada',
    precio: 5.00,
    descripcion: 'Bebida tradicional',
    categoria: 'Bebidas',
    disponible: true,
  },
];

const mockPedido = {
  id: 1,
  mesa_id: 1,
  mozo_id: 1,
  estado: 'Pendiente',
  subtotal: 36.00,
  impuesto: 6.48,
  total: 42.48,
  items: [
    {
      id: 1,
      menu_id: 1,
      cantidad: 2,
      precio_unitario: 18.00,
      subtotal: 36.00,
      descripcion: 'Monstrito',
    },
  ],
  created_at: new Date().toISOString(),
  updated_at: new Date().toISOString(),
};

const mockUsuarios = [
  {
    id: 1,
    nombre: 'Juan Pérez',
    email: 'juan@polleria.com',
    rol: 'mozo',
    estado: 'activo',
  },
  {
    id: 2,
    nombre: 'Carlos López',
    email: 'carlos@polleria.com',
    rol: 'cocinero',
    estado: 'activo',
  },
];

module.exports = {
  mockMesas,
  mockMenu,
  mockPedido,
  mockUsuarios,
};
