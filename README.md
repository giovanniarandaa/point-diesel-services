# Point Diesel Services

Sistema de gestión para taller de reparación diesel. Administra clientes, vehículos, inventario de partes, servicios de mano de obra y presupuestos con un flujo completo desde borrador hasta facturación.

## Stack Tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | Laravel 12 · PHP 8.3 |
| Frontend | React 19 · TypeScript 5.7 · Inertia.js v2 |
| Estilos | Tailwind CSS v4 · shadcn/ui (Radix UI) |
| Base de datos | MySQL 8 |
| Testing | Pest PHP · Playwright |
| Calidad | Larastan nivel 8 · Laravel Pint · ESLint · Prettier |

## Funcionalidades

### Gestión de Clientes (CRM)
- CRUD de clientes con nombre, teléfono (WhatsApp) y email
- Registro de vehículos (unidades) con VIN, marca, modelo, motor y millaje
- Historial de reparaciones por cliente/vehículo
- Soft-delete para preservar datos históricos
- Búsqueda por nombre, teléfono o email

### Inventario y Catálogo
- **Partes:** SKU, nombre, descripción, costo, precio de venta, stock y stock mínimo
- **Servicios de mano de obra:** nombre, descripción y precio por defecto
- Alertas de stock bajo (partes con stock ≤ stock mínimo)

### Presupuestos (Estimates)
- Creación con selección de cliente + vehículo
- Líneas de partes y servicios con cantidad y precio ajustable
- Cálculo automático: subtotales, shop supplies (5%), impuesto Texas (8.25%) y total
- Flujo de estados: **Borrador → Enviado → Aprobado → Facturado**
- Token público único (UUID) para compartir con el cliente

### Portal del Cliente
- Enlace público para ver el presupuesto sin autenticación
- Aprobación del presupuesto con registro de IP y timestamp
- Acciones rápidas de contacto (llamada, WhatsApp)

### Facturación
- Conversión de presupuesto aprobado a factura
- Notificación de "Vehículo Listo"

## Requisitos

- PHP 8.3+
- Node.js 22+
- MySQL 8+
- Composer 2+

## Instalación

```bash
# Clonar el repositorio
git clone https://github.com/giovanniarandaa/point-diesel-services.git
cd point-diesel-services

# Instalar dependencias
composer install
npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Configurar la base de datos en .env
# DB_CONNECTION=mysql
# DB_DATABASE=point_diesel_services
# DB_USERNAME=root
# DB_PASSWORD=

# Ejecutar migraciones y seeders
php artisan migrate --seed

# Generar archivos auxiliares
composer ide-helper
php artisan typescript:transform
npm run build
```

## Desarrollo

```bash
# Iniciar todos los servicios (server, queue, logs, vite)
composer dev
```

Esto ejecuta concurrentemente:
- `php artisan serve` — Servidor PHP en puerto 8000
- `php artisan queue:listen` — Procesador de colas
- `php artisan pail` — Logs en tiempo real
- `npm run dev` — Vite dev server con HMR

## Testing

### Tests PHP (Pest)

```bash
php artisan test                                    # Todos los tests
php artisan test tests/Feature/EstimateTest.php     # Archivo específico
php artisan test --filter 'can create estimate'     # Test específico
```

### Tests E2E (Playwright)

```bash
composer test:e2e          # Suite completa (swap .env, DB fresh, Playwright)
npm run test:e2e:ui        # Modo interactivo
npm run test:e2e:headed    # Con navegador visible
```

### Calidad de Código

```bash
composer format            # Formatear PHP (Pint)
composer analyze           # Análisis estático (Larastan nivel 8)
npm run lint               # ESLint + auto-fix
npm run format             # Prettier + auto-fix
npx tsc --noEmit           # Verificación de tipos TypeScript
```

## Arquitectura

```
app/
├── Actions/           # Lógica de negocio (responsabilidad única)
├── Data/              # DTOs via Spatie Laravel Data
├── Enums/             # EstimateStatus (draft|sent|approved|invoiced)
├── Http/
│   ├── Controllers/   # Thin controllers → delegan a Actions
│   └── Requests/      # Form Requests con validación
├── Models/            # Eloquent models con relaciones tipadas
├── Repositories/      # Abstracción de acceso a datos
└── Services/          # Orquestación de múltiples Actions

resources/js/
├── pages/             # Páginas React (mapeadas a rutas)
├── components/        # shadcn/ui + componentes custom
├── layouts/           # AppSidebarLayout, AuthLayout
├── hooks/             # Hooks de Inertia
└── types/             # Tipos TypeScript generados

tests/
├── Feature/           # Tests Pest PHP
└── e2e/               # Tests Playwright
```

### Flujo de Request

1. Request → Ruta Laravel (`routes/web.php`) → Controller
2. Controller → Action/Service → `Inertia::render('page', $props)`
3. Middleware `HandleInertiaRequests` inyecta props compartidos
4. Inertia resuelve `resources/js/pages/{page}.tsx` y renderiza
5. Navegación client-side sin recargas completas

## Base de Datos

```
customers ──┬── units ──── estimates ── estimate_lines ──┬── parts
             └── estimates ─────────────────────────────────┘   └── labor_services
```

- **customers** — Clientes con soft-delete
- **units** — Vehículos vinculados a clientes
- **parts** — Inventario con control de stock
- **labor_services** — Catálogo de servicios
- **estimates** — Presupuestos con flujo de estados
- **estimate_lines** — Líneas polimórficas (parts o labor_services)

## Licencia

Privado. Todos los derechos reservados.
