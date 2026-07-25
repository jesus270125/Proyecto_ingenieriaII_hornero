## RF38 – Historial de Preferencias y Pedidos del Cliente

- **ID:** RF38
- **Dependencia:** RF01, RF02
- **Tipo:** Funcionalidad
- **Nombre:** Historial de Preferencias y Pedidos del Cliente
- **Área:** Gestión de Ventas
- **Stakeholder:** Administrador / Mozo
- **Descripción:** El sistema permitirá al mozo buscar a los clientes recurrentes mediante una barra de búsqueda rápida (nombres, apellidos o teléfono) en la comanda. Al seleccionarlo, se desplegará su historial de consumo, puntos acumulados y promociones vigentes con la finalidad de ofrecer sugerencias personalizadas e incentivar la fidelización. El sistema actualizará automáticamente los puntos tras confirmar el nuevo pedido.
- **Prioridad:** Media
- **Seguridad:** Media
- **Estado:** Pendiente

## RF39 – Control y Registro de Propinas Digitales

- **ID:** RF39
- **Dependencia:** RF04
- **Tipo:** Funcionalidad
- **Nombre:** Control y Registro de Propinas Digitales
- **Área:** Gestión de Caja
- **Stakeholder:** Cajero
- **Descripción:** El sistema permitirá registrar propinas voluntarias efectuadas mediante medios de pago digitales. El monto de la propina será almacenado de forma independiente al importe del consumo, evitando afectar la base imponible del comprobante de pago y permitiendo su posterior consulta para fines administrativos.
- **Prioridad:** Media
- **Seguridad:** Alta
- **Estado:** Pendiente

## RF40 – Reporte Consolidado para Liquidación de Propinas

- **ID:** RF40
- **Dependencia:** RF39
- **Tipo:** Funcionalidad
- **Nombre:** Reporte Consolidado para Liquidación de Propinas
- **Área:** Gestión Administrativa
- **Stakeholder:** Administrador
- **Descripción:** El sistema permitirá generar un reporte consolidado de las propinas registradas por fecha, turno o cajero, facilitando el proceso de liquidación, auditoría y distribución entre los colaboradores autorizados.
- **Prioridad:** Media
- **Seguridad:** Alta
- **Estado:** Pendiente

---

Sprint objetivo: `sprint3-propinas-fidelizacion` (origin)

Notas:
- Se agregan migraciones y seeders para `propina` y `puntos_fidelidad`.
