# Point Diesel Services — MVP Specification

## Problema

Point Diesel Services es un taller mecánico de diesel en Texas que actualmente gestiona sus operaciones (clientes, presupuestos, inventario, facturación) de forma manual o con herramientas desconectadas. Esto genera:

- Pérdida de tiempo en la creación de presupuestos
- Falta de visibilidad del inventario de refacciones
- Proceso lento de aprobación de presupuestos con el cliente
- Sin historial centralizado de reparaciones por unidad
- Cobranza y facturación desorganizada

## Usuarios

| Rol | Descripción | Permisos clave |
|-----|-------------|----------------|
| **Admin** | Dueño/gerente del taller | Acceso total: configuración, inventario, catálogos, reportes, usuarios |
| **Encargado** | Personal de mostrador/operaciones | Crear clientes, unidades, estimates, enviar links, marcar "vehículo listo", facturar |
| **Cliente** (portal externo) | Dueño de la unidad | Ver estimate vía link público, aprobar/contactar al taller. Sin cuenta ni login |

> Roles gestionados con **Spatie Laravel Permission**. Arquitectura extensible para agregar más roles en el futuro.

## Requisitos Funcionales

### 1. Gestión de Clientes y Flotilla (CRM Ligero)

#### Registro de Cliente
- Campos: Nombre, Teléfono (formato WhatsApp), Email
- Teléfono y Email opcionales pero al menos uno requerido
- Búsqueda rápida por nombre, teléfono o email

#### Alta de Unidad (Vehículo)
- Campos: VIN (validación 17 caracteres alfanuméricos), Marca, Modelo, Motor, Kilometraje actual
- Vinculación obligatoria a un cliente existente
- Un cliente puede tener múltiples unidades

#### Historial Clínico
- Vista de reparaciones anteriores por unidad
- Muestra: fecha, descripción del trabajo, monto total, estado

### 2. Inventario Inteligente y Catálogo de Servicios

#### Gestión de Refacciones
- Campos: SKU (único), Nombre, Descripción, Costo, Precio de Venta, Stock actual, Stock mínimo
- CRUD completo con búsqueda y filtros
- Inventario centralizado (un solo almacén)

#### Catálogo de Mano de Obra
- Servicios mecánicos predefinidos con precio fijo por default
- Campos: Nombre del servicio, Descripción, Precio default
- El precio puede ajustarse manualmente al agregar a un estimate

#### Alertas de Stock Bajo
- Indicador visual (badge) cuando `stock_actual <= stock_minimo`
- Visible en: dashboard principal (badge en menú) + página de inventario
- Lista de items con stock bajo en el dashboard

### 3. Constructor de Estimates (Presupuestos)

#### Creación
- Seleccionar cliente y unidad existentes
- Búsqueda unificada: un solo campo que sugiere refacciones Y servicios del catálogo, agrupados por tipo
- Agregar líneas de tipo: refacción (con cantidad) o servicio
- Edición flexible: precios ajustables manualmente por línea

#### Cálculo Automático
- **Subtotal partes**: Σ (precio_unitario × cantidad) de refacciones
- **Subtotal labor**: Σ precios de servicios
- **Shop Supplies**: Porcentaje configurable por admin (default a definir) sobre (partes + labor)
- **Tax**: Tasa configurable (default 8.25%) sobre el total gravable
- **Total**: partes + labor + shop supplies + tax
- Todos los montos en USD, formato americano (1,234.56)

#### Estados del Estimate
```
Borrador → Enviado → Aprobado → Facturado
```
- **Borrador**: editable libremente
- **Enviado**: editable (cambios se reflejan en el link del cliente, se puede re-enviar)
- **Aprobado**: no editable, el cliente dio su aprobación
- **Facturado**: convertido a invoice, estado final

#### Link Público
- URL única con token seguro (UUID o signed URL)
- Sin expiración
- Accesible sin autenticación

### 4. Portal de Aprobación Remota (Vista Cliente)

#### Acceso
- URL pública, sin login ni descarga de app
- Vista responsive (mobile-first, diseñada para celular)

#### Visualización
- Estimate en formato web limpio con todos los detalles:
  - Datos del taller (hardcoded por ahora)
  - Datos del cliente y unidad
  - Líneas de refacciones y servicios
  - Subtotales, shop supplies, tax, total

#### Acciones del Cliente
- **"Aprobar Presupuesto"**: botón grande → paso de confirmación ("¿Está seguro?") → registra aprobación con timestamp + IP del cliente → cambia estado a "Aprobado" → notifica al taller
- **"Contactar al Taller"**: botones de un clic para:
  - Llamada telefónica (`tel:` link)
  - WhatsApp (`wa.me/` link)

### 5. Notificación de Entrega y Cierre (Facturación)

#### Botón "Vehículo Listo"
- Acción manual del encargado/admin
- Envía notificación al cliente vía:
  - **WhatsApp** (Twilio API): mensaje predeterminado con datos del vehículo
  - **Email** (Resend): notificación de que su unidad está lista

#### Conversión Estimate → Invoice
- Un clic para convertir estimate aprobado a invoice
- Numeración secuencial automática: `INV-0001`, `INV-0002`, ...
- Genera PDF descargable con:
  - Logo y datos del taller (hardcoded por ahora)
  - Datos del cliente y unidad
  - Detalle de partes, labor, shop supplies, tax, total
  - Número de invoice y fecha

#### Descuento de Inventario
- Al crear invoice, se descuenta automáticamente el stock de refacciones usadas
- Si stock insuficiente: **warning visual** pero permite facturar (inventario puede quedar en negativo)

### 6. Dashboard Principal

- Vista tipo "lista de trabajo" al entrar al sistema
- Estimates recientes con sus estados (badges de color por estado)
- Alertas de stock bajo (badge en menú de inventario)
- Sin gráficas ni KPIs por ahora

### 7. Configuración (Settings)

- **Shop Supplies %**: porcentaje configurable para el cálculo de estimates
- **Tax Rate %**: tasa de impuesto configurable (default 8.25%)
- **Datos del taller**: hardcoded por ahora (logo, nombre, dirección, teléfono) — se hace configurable después
- **Gestión de usuarios**: CRUD de usuarios con asignación de roles

## Flujos Principales

### Flujo 1: Registro de Cliente y Unidad
```
Encargado abre "Clientes" → "Nuevo Cliente" → Llena datos →
Guarda → "Agregar Unidad" → Llena VIN, Marca, etc. → Guarda
```

### Flujo 2: Creación y Envío de Estimate
```
Encargado abre "Nuevo Estimate" → Selecciona Cliente → Selecciona Unidad →
Busca y agrega refacciones/servicios → Ajusta precios si necesario →
Revisa totales → Guarda como Borrador → "Enviar al Cliente" →
Sistema genera link → Encargado envía link por WhatsApp/email (vía Twilio/Resend)
→ Estado cambia a "Enviado"
```

### Flujo 3: Aprobación del Cliente
```
Cliente recibe link → Abre en celular → Ve el estimate →
Presiona "Aprobar" → Confirma ("¿Está seguro?") →
Sistema registra aprobación (timestamp + IP) →
Estado cambia a "Aprobado" → Notificación interna al taller
```

### Flujo 4: Entrega y Facturación
```
Reparación completada → Encargado presiona "Vehículo Listo" →
Cliente recibe notificación WhatsApp + email →
Encargado presiona "Convertir a Invoice" →
Sistema genera Invoice con numeración secuencial →
Descuenta refacciones del inventario → PDF disponible para descarga
```

## Reglas de Negocio

1. **Un cliente puede tener múltiples unidades** (flotilla)
2. **Un estimate pertenece a exactamente un cliente + una unidad**
3. **El VIN debe tener exactamente 17 caracteres alfanuméricos**
4. **Los precios en el estimate pueden diferir del catálogo** (edición manual permitida)
5. **El estimate es editable solo en estados Borrador y Enviado**, no después de aprobado
6. **La aprobación del cliente es irreversible** una vez confirmada
7. **Al facturar, el inventario se descuenta automáticamente** — se permite stock negativo con warning
8. **La numeración de invoices es secuencial y no puede tener huecos**
9. **El portal del cliente NO requiere autenticación** — acceso vía token en URL
10. **Shop Supplies y Tax Rate son configurables por el admin** desde settings

## Requisitos No Funcionales

- **Performance**: Búsqueda de catálogo debe responder en < 300ms
- **Seguridad**: Links de estimates usan tokens seguros (UUID v4 o signed URLs). CSRF protection en todas las acciones
- **Responsive**: Portal del cliente 100% mobile-first. Backend UI responsive pero optimizado para desktop
- **PDF**: Generación de PDF server-side para invoices
- **Notificaciones**: WhatsApp vía Twilio API, Email vía Resend
- **Hosting**: AWS (mencionado en notas del SPEC)
- **Base de datos**: MySQL 8 (producción), SQLite :memory: (tests)

## Edge Cases

1. **Cliente sin teléfono ni email**: No se puede registrar — al menos uno requerido para comunicación
2. **Estimate con 0 líneas**: No se puede enviar un estimate vacío
3. **Stock negativo**: Se permite pero se muestra warning prominente al facturar y en inventario
4. **Mismo VIN para dos unidades**: El VIN debe ser único en el sistema
5. **Cliente aprueba después de que el encargado editó el estimate**: El cliente siempre ve la versión más reciente
6. **Doble clic en "Aprobar"**: Idempotente — si ya está aprobado, muestra mensaje de confirmación sin duplicar la acción
7. **SKU duplicado en refacciones**: El SKU debe ser único
8. **Invoice sin estimate aprobado**: No se puede crear invoice sin aprobación previa
9. **Eliminación de cliente con estimates/invoices**: Soft delete — el cliente se marca como inactivo, sus datos persisten

## Fuera de Alcance (MVP)

- Multi-tenancy (múltiples talleres)
- Decodificación automática de VIN vía API
- Dashboard con gráficas y KPIs avanzados
- Portal de cliente con historial (solo ve el estimate del link)
- Firma digital del cliente
- Pagos en línea
- Calendario/agenda de citas
- Asignación de mecánicos a trabajos
- Órdenes de compra a proveedores
- App móvil nativa
- Multi-moneda (solo USD)
- Multi-idioma
- Integración contable (QuickBooks, etc.)
- Datos del taller configurables desde UI (hardcoded por ahora)
- Expiración de links de estimates

## Stack Técnico

### Backend
- **PHP 8.3** + **Laravel 12**
- **Inertia.js v2** (bridge server ↔ client)
- **MySQL 8** (producción)
- **Spatie Laravel Data** (DTOs)
- **Spatie Laravel Permission** (roles y permisos) — a instalar
- **Larastan level 8** (análisis estático)
- **Laravel Pint** (formato PHP)
- **PHPUnit 11** (testing)

### Frontend
- **React 19** + **TypeScript 5**
- **Tailwind CSS v4** + **shadcn/ui** (diseño default por ahora)
- **Inertia React v2** (client-side)
- **Lucide React** (iconos)
- **Vite 6** (bundler)

### Servicios Externos
- **Twilio** — WhatsApp messaging API
- **Resend** — Transactional email

### Infraestructura
- **AWS** — servidor medium (~$60/mes)
- **Dominio** — a definir

## Paquetes Recomendados

| Necesidad | Paquete | Versión | Stars/Downloads | Justificación |
|-----------|---------|---------|-----------------|---------------|
| Roles y Permisos | `spatie/laravel-permission` | v7.2.3 | 12.8k stars | Estándar de la industria Laravel. Roles + permisos granulares. Score 94/100. Alta reputación |
| Generación PDF | `barryvdh/laravel-dompdf` | latest | 80M+ downloads | Sin dependencias externas (no requiere Chrome/Node). Ideal para invoices con HTML/CSS sencillo. Compatible con cualquier hosting. Para el MVP es más que suficiente |
| Email transaccional | `resend/resend-laravel` | v1.2.0 | SDK oficial | Se integra como mail driver de Laravel. Compatible con Laravel 12. Requiere solo API key |
| WhatsApp API | `twilio/sdk` | latest | SDK oficial PHP | SDK oficial de Twilio. Soporte WhatsApp Business API. Bien documentado para Laravel |

### Alternativas Evaluadas

| Paquete | Razón de descarte |
|---------|-------------------|
| `spatie/laravel-pdf` v2 | Más moderno y soporta Tailwind CSS en PDFs, pero requiere Chrome/Browsershot o Gotenberg. Over-engineering para invoices simples del MVP |
| `aloha/laravel-twilio` | Wrapper Laravel para Twilio, pero menos mantenido que usar el SDK oficial directamente |
| Custom roles (columna en DB) | Menos flexible. Spatie Permission ya resuelve esto con un ecosistema probado |

### Paquetes Ya Instalados (reutilizar)

| Paquete | Uso en MVP |
|---------|------------|
| `spatie/laravel-data` | DTOs para Estimates, Invoices, Clientes, etc. |
| `spatie/laravel-typescript-transformer` | Auto-generar tipos TS desde los DTOs |
| `tightenco/ziggy` | Named routes en el frontend React |
| shadcn/ui (Radix + Tailwind) | Componentes UI (ya instalado parcialmente) |
