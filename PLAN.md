# Point Diesel Services — MVP Implementation Plan

## Estado del Proyecto

El proyecto está sobre **Laravel 12 + Inertia v2 + React 19 + TypeScript + Tailwind v4 + shadcn/ui**. Actualmente tiene:
- Autenticación completa (login, register, forgot password, email verification)
- Settings (profile, password, appearance)
- Sidebar con navegación
- **Módulo 1 completado**: Clientes + Unidades con CRUD, búsqueda, validaciones
- 59 tests pasando (26 auth + 22 customers + 11 units)
- Larastan level 8, Pint, TypeScript — todo limpio

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
**Estado**: [ ] Pendiente

#### Backend
- [ ] Migración `create_parts_table`: sku (unique), name, description, cost, sale_price, stock, min_stock, timestamps
- [ ] Migración `create_labor_services_table`: name, description, default_price, timestamps
- [ ] Modelos `Part` y `LaborService` con factories y seeders
- [ ] DTOs: `PartData`, `LaborServiceData` con `#[TypeScript]`
- [ ] Form Requests para CRUD de ambos
- [ ] `PartController` — CRUD con búsqueda y filtros
- [ ] `LaborServiceController` — CRUD con búsqueda
- [ ] Scope/query para stock bajo: `stock <= min_stock`

#### Frontend
- [ ] Página `inventory/index.tsx` — Lista de refacciones con badge stock bajo
- [ ] Páginas CRUD para refacciones
- [ ] Página `services/index.tsx` — Lista de servicios de mano de obra
- [ ] Páginas CRUD para servicios
- [ ] Badge de alerta en sidebar cuando hay items con stock bajo
- [ ] Navegación sidebar: "Inventory" y "Services"

#### Tests
- [ ] Feature: PartController CRUD, búsqueda, SKU unique, stock bajo
- [ ] Feature: LaborServiceController CRUD

---

### Módulo 3: Constructor de Estimates `feat/estimates`
**Estado**: [ ] Pendiente

#### Backend
- [ ] Migración `create_estimates_table`: customer_id, unit_id, status (enum), public_token (UUID), subtotal_parts, subtotal_labor, shop_supplies_amount, tax_amount, total, notes, approved_at, approved_ip, timestamps
- [ ] Migración `create_estimate_lines_table`: estimate_id, lineable_type/id (polymorphic → Part o LaborService), description, quantity, unit_price, line_total, sort_order
- [ ] Modelo `Estimate` con relaciones, estados (enum: draft, sent, approved, invoiced)
- [ ] Modelo `EstimateLine` con relación polymorphic
- [ ] DTOs: `EstimateData`, `EstimateLineData`
- [ ] `EstimateController` — CRUD, envío, cálculo automático
- [ ] Action `CalculateEstimateTotalsAction` — calcula subtotales, shop supplies, tax, total
- [ ] Action `SendEstimateAction` — genera link público, envía por WhatsApp/email
- [ ] Rutas para CRUD y acciones especiales (send, approve)

#### Frontend
- [ ] Página `estimates/index.tsx` — Lista con badges de estado (colores)
- [ ] Página `estimates/create.tsx` — Constructor con búsqueda unificada de partes y servicios
- [ ] Página `estimates/show.tsx` — Vista de detalle con acciones
- [ ] Página `estimates/edit.tsx` — Edición (solo en draft/sent)
- [ ] Componente búsqueda unificada de catálogo (partes + servicios agrupados)
- [ ] Cálculo automático en frontend (preview) + backend (source of truth)

#### Tests
- [ ] Feature: CRUD estimates
- [ ] Feature: Cálculo de totales (shop supplies, tax)
- [ ] Feature: Estados y transiciones
- [ ] Feature: Líneas polymorphic (partes y servicios)
- [ ] Feature: Búsqueda de catálogo

---

### Módulo 4: Portal de Aprobación (Vista Cliente) `feat/client-portal`
**Estado**: [ ] Pendiente

#### Backend
- [ ] Ruta pública `GET /estimate/{token}` — sin auth
- [ ] Ruta pública `POST /estimate/{token}/approve` — sin auth
- [ ] `PublicEstimateController` — show (vista pública), approve (con confirmación)
- [ ] Registrar IP + timestamp en aprobación
- [ ] Idempotencia: si ya está aprobado, mostrar mensaje sin duplicar

#### Frontend
- [ ] Página `public/estimate.tsx` — Vista mobile-first del estimate
- [ ] Layout público sin sidebar (auth no requerido)
- [ ] Botón "Aprobar Presupuesto" con confirmación
- [ ] Botones "Llamar" (tel:) y "WhatsApp" (wa.me/)
- [ ] Estado visual: si ya aprobado, mostrar confirmación

#### Tests
- [ ] Feature: Vista pública accesible sin auth
- [ ] Feature: Aprobación con registro de IP/timestamp
- [ ] Feature: Idempotencia de aprobación
- [ ] Feature: Estimate no encontrado (404)

---

### Módulo 5: Notificaciones y Facturación `feat/invoicing`
**Estado**: [ ] Pendiente

#### Backend
- [ ] Migración `create_invoices_table`: estimate_id, invoice_number (INV-0001), issued_at, subtotal_parts, subtotal_labor, shop_supplies_amount, tax_amount, total, timestamps
- [ ] Modelo `Invoice` con numeración secuencial
- [ ] Action `ConvertEstimateToInvoiceAction` — crea invoice, descuenta inventario
- [ ] Action `NotifyVehicleReadyAction` — envía WhatsApp (Twilio) + Email (Resend)
- [ ] Generación de PDF con barryvdh/laravel-dompdf
- [ ] Warning visual si stock insuficiente (permite negativo)

#### Frontend
- [ ] Botón "Vehicle Ready" en estimate aprobado
- [ ] Botón "Convert to Invoice" en estimate aprobado
- [ ] Página `invoices/show.tsx` — Detalle del invoice
- [ ] Descarga de PDF

#### Tests
- [ ] Feature: Conversión estimate → invoice
- [ ] Feature: Numeración secuencial INV-0001
- [ ] Feature: Descuento de inventario
- [ ] Feature: Warning de stock negativo
- [ ] Feature: Generación de PDF

---

### Módulo 6: Dashboard `feat/dashboard`
**Estado**: [ ] Pendiente

- [ ] Vista tipo "lista de trabajo" con estimates recientes
- [ ] Badges de color por estado de estimate
- [ ] Badge de stock bajo en menú de inventario
- [ ] Lista de items con stock bajo

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
- PHPUnit (no Pest) con `RefreshDatabase`
- Feature tests para cada endpoint
- Factories para crear datos de prueba
- `php artisan test --compact --filter=testName`

### Git
- Branch por módulo: `feat/clients-units`, `feat/inventory-catalog`, etc.
- Conventional commits: `feat:`, `fix:`, `test:`, etc.
- Merge a master al completar cada módulo
