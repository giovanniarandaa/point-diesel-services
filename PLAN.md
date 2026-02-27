# Point Diesel Services — MVP Implementation Plan

## Estado del Proyecto

El proyecto está sobre **Laravel 12 + Inertia v2 + React 19 + TypeScript + Tailwind v4 + shadcn/ui**. Actualmente tiene:
- Autenticación completa (login, register, forgot password, email verification)
- Settings (profile, password, appearance)
- Sidebar con navegación
- **Módulo 1 completado**: Clientes + Unidades con CRUD, búsqueda, validaciones
- **Módulo 2 completado**: Inventario (Parts) + Catálogo de Servicios (LaborServices) con CRUD, búsqueda, filtro stock bajo, badge sidebar
- 89 backend tests pasando (20 auth + 21 customers + 12 units + 22 parts + 14 services)
- 42 E2E tests pasando (3 auth + 2 nav + 11 customers + 7 units + 11 parts + 8 services)
- Larastan level 8, Pint, ESLint, Prettier, TypeScript — todo limpio

---

## Módulos del MVP

Cada módulo se implementa en su propia rama y se mergea a master al completar.

### Módulo 1: Clientes + Unidades (CRM Ligero) `feat/clients-units`
**Estado**: [x] Completado

#### Backend
- [x] Migración `create_customers_table`: name, phone, email, soft deletes, timestamps
- [x] Migración `create_units_table`: customer_id (FK cascade), vin (unique, 17 chars), make, model, engine, mileage, timestamps
- [x] Modelo `Customer` con relación hasMany → Unit, soft deletes, cascade delete en booted()
- [x] Modelo `Unit` con relación belongsTo → Customer, factory
- [x] `StoreCustomerRequest` y `UpdateCustomerRequest` Form Requests (con after() validation)
- [x] `StoreUnitRequest` y `UpdateUnitRequest` Form Requests (con prepareForValidation() para VIN uppercase)
- [x] `CustomerController` — index (con búsqueda debounce), create, store, show, edit, update, destroy
- [x] `UnitController` — store, update, destroy (nested bajo customer)
- [x] Rutas en `routes/customers.php` (incluido desde web.php)
- [x] Validación: al menos phone o email requerido, VIN 17 chars alfanuméricos (sin I,O,Q) unique, E.164 phone

#### Frontend
- [x] Página `customers/index.tsx` — Lista con búsqueda real-time debounce (300ms)
- [x] Página `customers/create.tsx` — Formulario de nuevo cliente
- [x] Página `customers/show.tsx` — Detalle del cliente con unidades en cards + unit dialog
- [x] Página `customers/edit.tsx` — Editar cliente
- [x] Componente `customers/unit-dialog.tsx` — Dialog unificado para crear/editar unidad
- [x] Componente `pagination.tsx` — Paginación reutilizable
- [x] Navegación sidebar: "Customers" con ícono Users
- [ ] TypeScript types generados desde DTOs (diferido — se usarán inline types por ahora)

#### Tests (33 tests)
- [x] Feature: CustomerController — CRUD completo, búsqueda por nombre/phone/email, validaciones
- [x] Feature: UnitController — CRUD, VIN validation (uppercase, length, chars), unique, customer association
- [x] Feature: Soft delete customer con cascade a unidades
- [x] Feature: Auth guard en todos los endpoints

#### Notas de implementación
- Arquitectura pragmática: controllers directos sin Actions/Repositories (suficiente para CRUD simple)
- DTOs diferidos — se implementarán cuando haya lógica compleja (Módulo 3+)
- `route('home')` → `route('dashboard')` en auth layouts (fix Ziggy)
- `/` es el dashboard (no `/dashboard`)

---

### Módulo 2: Inventario + Catálogo de Servicios `feat/inventory-catalog`
**Estado**: [x] Completado

#### Backend
- [x] Migración `create_parts_table`: sku (unique), name, description, cost, sale_price, stock, min_stock, timestamps
- [x] Migración `create_labor_services_table`: name, description, default_price, timestamps
- [x] Modelos `Part` y `LaborService` con factories y seeders
- [ ] DTOs: `PartData`, `LaborServiceData` con `#[TypeScript]` (diferido — se usarán inline types por ahora)
- [x] Form Requests para CRUD de ambos
- [x] `PartController` — CRUD con búsqueda y filtros
- [x] `LaborServiceController` — CRUD con búsqueda
- [x] Scope/query para stock bajo: `stock <= min_stock`

#### Frontend
- [x] Página `parts/index.tsx` — Lista de refacciones con badge stock bajo + botones editar/eliminar
- [x] Páginas CRUD para refacciones (create, show, edit)
- [x] Página `services/index.tsx` — Lista de servicios de mano de obra + botones editar/eliminar
- [x] Páginas CRUD para servicios (create, show, edit)
- [x] Badge de alerta en sidebar cuando hay items con stock bajo
- [x] Navegación sidebar: "Inventory" y "Services"
- [x] Botones editar/eliminar en tabla de Customers (mejora retroactiva)

#### Tests (36 backend + 19 E2E)
- [x] Feature: PartController CRUD, búsqueda, SKU unique, stock bajo (22 tests)
- [x] Feature: LaborServiceController CRUD (14 tests)
- [x] E2E: Parts CRUD, búsqueda, stock bajo, editar, eliminar (11 tests)
- [x] E2E: Services CRUD, búsqueda, editar, eliminar (8 tests)

#### Notas de implementación
- DTOs diferidos — se implementarán con Módulo 3+ (mismo enfoque que Módulo 1)
- Low stock count compartido vía `HandleInertiaRequests` middleware (lazy closure)
- `scopeLowStock` usa `whereColumn('stock', '<=', 'min_stock')` para comparar columnas
- SKU auto-uppercase vía `prepareForValidation()` en Form Requests
- Rutas organizadas en archivos separados: `routes/parts.php`, `routes/services.php`

---

### Módulo 3: Constructor de Estimates `feat/estimates`
**Estado**: [x] Completado

#### Backend
- [x] Migración `create_estimates_table`: customer_id, unit_id, status (enum), public_token (UUID), subtotal_parts, subtotal_labor, shop_supplies_amount, tax_amount, total, notes, approved_at, approved_ip, timestamps
- [x] Migración `create_estimate_lines_table`: estimate_id, lineable_type/id (polymorphic → Part o LaborService), description, quantity, unit_price, line_total, sort_order
- [x] Modelo `Estimate` con relaciones, estados (enum: draft, sent, approved, invoiced)
- [x] Modelo `EstimateLine` con relación polymorphic
- [x] DTOs: `EstimateData`, `EstimateLineData`
- [x] `EstimateController` — CRUD, envío, cálculo automático
- [x] Action `CalculateEstimateTotalsAction` — calcula subtotales, shop supplies, tax, total
- [x] Action `SendEstimateAction` — genera link público, envía por WhatsApp/email
- [x] Rutas para CRUD y acciones especiales (send, approve)

#### Frontend
- [x] Página `estimates/index.tsx` — Lista con badges de estado (colores)
- [x] Página `estimates/create.tsx` — Constructor con búsqueda unificada de partes y servicios
- [x] Página `estimates/show.tsx` — Vista de detalle con acciones
- [x] Página `estimates/edit.tsx` — Edición (solo en draft/sent)
- [x] Componente búsqueda unificada de catálogo (partes + servicios agrupados)
- [x] Cálculo automático en frontend (preview) + backend (source of truth)

#### Tests
- [x] Feature: CRUD estimates
- [x] Feature: Cálculo de totales (shop supplies, tax)
- [x] Feature: Estados y transiciones
- [x] Feature: Líneas polymorphic (partes y servicios)
- [x] Feature: Búsqueda de catálogo

#### Notas de implementación
- Enum `EstimateStatus` con 4 estados: draft, sent, approved, invoiced
- Polymorphic lines con `lineable_type` (Part o LaborService) + `lineable_id`
- `CalculateEstimateTotalsAction` calcula subtotales, shop supplies (3%), tax (8.25%)
- `SendEstimateAction` genera `public_token` UUID y marca como sent
- Búsqueda unificada de catálogo con agrupación por tipo (partes/servicios)
- Cálculo automático en frontend (preview) + backend (source of truth)

---

### Módulo 4: Portal de Aprobación (Vista Cliente) `feat/client-portal`
**Estado**: [x] Completado

#### Backend
- [x] Ruta pública `GET /estimate/{token}` — sin auth, con throttle (10 req/min)
- [x] Ruta pública `POST /estimate/{token}/approve` — sin auth, con throttle
- [x] `PublicEstimateController` — show (vista pública), approve (con confirmación)
- [x] `ApproveEstimateAction` — lógica de aprobación con idempotencia
- [x] `Estimate::markAsApproved($ip)` — método en modelo (mirrors markAsSent)
- [x] Registrar IP + timestamp en aprobación
- [x] Idempotencia: si ya está aprobado, mostrar banner sin duplicar
- [x] Shop phone configurado vía `SHOP_PHONE` en .env → `config('app.shop_phone')`

#### Frontend
- [x] Página `estimate-public.tsx` — Vista mobile-first del estimate
- [x] Layout `public-estimate-layout.tsx` — sin sidebar, con logo, header, footer
- [x] Botón "Approve Estimate" con confirmación (solo visible en status sent)
- [x] Botones "Call Shop" (tel:) y "WhatsApp" (wa.me/) con enlace directo
- [x] Banner "Already Approved" cuando el estimate ya fue aprobado
- [x] Flash messages (Sonner toast) en layout público

#### Tests (13 backend + 4 E2E)
- [x] Feature: Vista pública accesible sin auth (13 tests)
- [x] Feature: Aprobación con registro de IP/timestamp
- [x] Feature: Idempotencia de aprobación (approved e invoiced)
- [x] Feature: Estimate no encontrado (404)
- [x] Feature: Shop phone desde config
- [x] Feature: Visibilidad de sent/approved/invoiced
- [x] E2E: Crear, enviar, ver público, aprobar, verificar status (4 tests)

#### Notas de implementación
- Cero migraciones — los campos `public_token`, `approved_at`, `approved_ip` ya existían
- Rate limiting `throttle:10,1` en rutas públicas para prevenir abuso
- UUID v4 como token público (122 bits de entropía)
- Layout público usa Sonner toast para flash messages (consistente con app principal)
- E2E tests extraen public_token vía clipboard con `grantPermissions`
- Fix de E2E preexistentes: selectores más específicos para evitar colisiones con parallel workers

---

### Módulo 5: Notificaciones y Facturación `feat/invoicing`
**Estado**: [x] Completado

#### Backend
- [x] Migración `create_invoices_table`: estimate_id (unique FK), invoice_number (INV-0001), issued_at, totales copiados del estimate, timestamps
- [x] Migración `add_notified_at_to_invoices_table`: campo `notified_at` para idempotencia de notificaciones
- [x] Modelo `Invoice` con numeración secuencial (`generateInvoiceNumber()` con lockForUpdate), `markAsNotified()`, `wasNotified()`
- [x] Action `ConvertEstimateToInvoiceAction` — crea invoice, descuenta inventario, marca estimate como invoiced (transacción)
- [x] Action `DeductInventoryAction` — recorre líneas de tipo Part y decrementa stock, retorna warnings
- [x] Action `NotifyVehicleReadyAction` — envía notificación multicanal (Email + WhatsApp), idempotente
- [x] `VehicleReadyNotification` — Laravel Notification con canales condicionales (mail si Resend configurado, WhatsApp si Twilio configurado)
- [x] `TwilioWhatsAppChannel` — canal custom de Laravel Notifications para WhatsApp vía Twilio SDK
- [x] Generación de PDF con barryvdh/laravel-dompdf — template Blade funcional
- [x] Warning visual si stock insuficiente (permite negativo con confirmación)
- [x] API endpoint `stock-warnings` para verificar stock antes de convertir
- [x] Resend como mail provider (`resend/resend-laravel`)
- [x] Twilio SDK para WhatsApp (`twilio/sdk`) — sandbox configurado

#### Frontend
- [x] Botón "Convert to Invoice" en estimate aprobado (con verificación de stock previa)
- [x] Warning card con stock insuficiente y opción "Convert Anyway"
- [x] Botón "View Invoice" en estimate convertido (link al invoice)
- [x] Página `invoices/show.tsx` — Detalle del invoice con datos del estimate
- [x] Descarga de PDF desde la página del invoice
- [x] Botón "Vehicle Ready" — envía Email + WhatsApp al cliente, se reemplaza por badge "Notified" verde

#### Tests (21 backend)
- [x] Feature: Guest protection (5 tests: store, show, pdf, stock-warnings, notify)
- [x] Feature: Conversión estimate → invoice (approved, deducción stock, stock negativo)
- [x] Feature: Guards (draft/sent no pueden convertirse, no doble conversión)
- [x] Feature: Numeración secuencial INV-0001
- [x] Feature: Show invoice con relaciones
- [x] Feature: Generación de PDF
- [x] Feature: Stock warnings API (insuficiente, suficiente, ignora labor services)
- [x] Feature: Vehicle Ready (marca notified, idempotente, dispatches notification)

#### Notas de implementación
- Invoice copia totales del estimate (snapshot, no recalcula)
- Un estimate = un invoice (1:1, `estimate_id` unique constraint)
- Stock se descuenta al facturar, no al aprobar (flexibilidad operacional)
- barryvdh/laravel-dompdf v3.1 con template Blade básico
- Notificación idempotente: `notified_at` timestamp previene doble envío
- Canales condicionales: email se salta si `MAIL_MAILER=log`, WhatsApp se salta si no hay `TWILIO_SID`
- Customer model usa trait `Notifiable` con `routeNotificationForTwilioWhatsapp()` retornando campo `phone`
- Twilio sandbox activo (expira cada 72h, producción requiere WhatsApp Business approval)
- 193 backend tests pasando (172 existentes + 21 nuevos)

---

### Módulo 6: Dashboard `feat/dashboard`
**Estado**: [x] Completado

#### Backend
- [x] `DashboardController` (invokable) — stats, estimates recientes, low stock parts
- [x] Stats: total estimates, active estimates (sent+approved), invoices del mes, revenue del mes
- [x] Recent estimates: 10 más recientes con customer y unit eager-loaded
- [x] Low stock parts: todos los items con `stock <= min_stock`, ordenados por criticidad
- [x] Ruta actualizada en `web.php` usando controller en vez de closure

#### Frontend
- [x] 4 Stats Cards: Total Estimates, Active Estimates, Invoices This Month, Revenue This Month
- [x] Vista tipo "lista de trabajo" con estimates recientes (tabla con links clickeables)
- [x] Badges de color por estado de estimate (draft, sent, approved, invoiced)
- [x] Badge de stock bajo en menú de inventario (ya existía desde Módulo 2)
- [x] Lista de items con stock bajo (scrollable, stock actual vs mínimo, icono alerta)
- [x] Links a "View all" estimates y "View inventory" filtrado por low stock

#### Tests (11 backend)
- [x] Feature: Guest redirect
- [x] Feature: Stats correctos (conteos por status, invoices del mes, revenue)
- [x] Feature: Recent estimates con relaciones, límite 10, orden descendente
- [x] Feature: Low stock filtrado y ordenado por criticidad
- [x] Feature: Empty state (sin datos)

#### Notas de implementación
- Controller invokable sin Actions/Services (solo lectura de datos)
- Stats usa `selectRaw` con `groupBy` para un solo query de conteos por status
- Revenue formateada con `number_format` para evitar pérdida de precisión
- Low stock ordenado por `(stock - min_stock) asc` para mostrar los más críticos primero
- 202 backend tests pasando (193 existentes + 9 nuevos)

---

### Módulo 7: Configuración `feat/settings-business`
**Estado**: [ ] Pendiente

#### Backend
- [ ] Migración `create_settings_table`: key (unique), value, type
- [ ] Modelo `Setting` o config pattern (key-value)
- [ ] Default: shop_supplies_rate, tax_rate (8.25%)
- [ ] `BusinessSettingsController` — edit, update
- [ ] Gestión de usuarios con Spatie Permission (roles: admin, encargado)

#### Frontend
- [ ] Página `settings/business.tsx` — Shop supplies %, Tax rate %
- [ ] Página `settings/users.tsx` — CRUD de usuarios con roles
- [ ] Sub-navegación dentro de Settings

#### Tests
- [ ] Feature: CRUD settings
- [ ] Feature: Gestión de usuarios y roles

---

## Paquetes por Instalar

| Módulo | Paquete | Cuándo |
|--------|---------|--------|
| Módulo 1 | — | No requiere paquetes nuevos |
| Módulo 3 | — | No requiere paquetes nuevos |
| Módulo 5 | `barryvdh/laravel-dompdf` | Al iniciar Módulo 5 |
| Módulo 5 | `twilio/sdk` | Al iniciar Módulo 5 |
| Módulo 5 | `resend/resend-laravel` | Al iniciar Módulo 5 |
| Módulo 7 | `spatie/laravel-permission` | Al iniciar Módulo 7 |

---

## Convenciones de Implementación

### Backend
- Controllers thin → delegan a Actions/Services
- Form Requests para validación (array syntax)
- Spatie Laravel Data DTOs con `#[TypeScript]`
- `/** @var \App\Models\User $user */` para Larastan level 8
- Factories y seeders para cada modelo
- `php artisan make:*` para generar archivos

### Frontend
- `useForm()` de Inertia para formularios
- `<InputError>` para errores de validación
- `<AppLayout>` con breadcrumbs para páginas autenticadas
- `route('name')` via Ziggy para todas las URLs
- shadcn/ui components (instalar nuevos según se necesiten)

### Testing
- Pest PHP con `RefreshDatabase` (configurado en `tests/Pest.php`)
- Sintaxis: `test('description', fn() => ...)` — NO clases PHPUnit
- Feature tests para cada endpoint
- Factories para crear datos de prueba
- `php artisan test --compact --filter=testName`

### Git
- Branch por módulo: `feat/clients-units`, `feat/inventory-catalog`, etc.
- Conventional commits: `feat:`, `fix:`, `test:`, etc.
- Merge a master al completar cada módulo
