# Taller Torque con Laravel

Aplicación para administrar órdenes de trabajo, inventario de repuestos, caja diaria y garantías. El proyecto usa Laravel, Blade, Eloquent y SQLite.

## Requisitos

- PHP 8.3 o superior con las extensiones requeridas por Laravel (incluidas `pdo_sqlite` y `sqlite3` para la base predeterminada).
- Composer.

## Puesta en marcha

Desde esta carpeta:

```powershell
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Abre `http://127.0.0.1:8000`. La base de datos SQLite se guarda en `database/database.sqlite`.

Al crear por primera vez la cuenta `admin`, `DatabaseSeeder` genera una contraseña aleatoria y la muestra en la consola. Guárdala al ejecutar `php artisan migrate --seed`; el seeder no cambia la contraseña de una cuenta existente. Configura credenciales propias antes de desplegar la aplicación.

## Funciones

- Inicio/cierre de sesión con sesiones de Laravel y protección CSRF.
- Panel operativo y órdenes con estados de recepción, reparación, listo y finalizada.
- Registro de clientes y filtro por nombre o celular para asignar clientes existentes al crear órdenes, con resumen de saldo e historial completo por cliente.
- Repuestos con control de existencias, snapshots de precio y devolución de stock al retirar una pieza.
- Mano de obra, pagos y reflejo automático de los abonos en el libro de caja.
- Ingresos/gastos manuales e historial de trabajos con cálculo de garantía de 90 días.
- Formularios Blade con validación en servidor y datos persistidos mediante Eloquent.

## Verificación

```powershell
php artisan test
```
