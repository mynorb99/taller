# Taller Torque

Aplicación Laravel para administrar órdenes de trabajo, cotizaciones, clientes, inventario, caja y garantías. Usa SQLite como base de datos predeterminada.

## Puesta en marcha

Requisitos: PHP 8.3 o superior y Composer.

```powershell
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Abre `http://127.0.0.1:8000`. Al crear por primera vez la cuenta `admin`, el seeder genera una contraseña aleatoria y la muestra en la consola. Guárdala al ejecutar `php artisan migrate --seed`; el seeder no cambia la contraseña de una cuenta existente. Configura credenciales propias antes de desplegar la aplicación.

La base de datos SQLite se guarda en `database/database.sqlite`.

## Verificación

```powershell
php artisan test
```
