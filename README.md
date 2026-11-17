# Taller Torque

La aplicación Laravel está en [`laravel/`](./laravel/). El archivo [`taller.html`](./taller.html) se conserva como referencia de la versión anterior.

Para iniciar:

```powershell
Set-Location .\laravel
composer install
php artisan migrate --seed
php artisan serve
```

Abre `http://127.0.0.1:8000`. Al crear la cuenta inicial, el seeder genera una contraseña aleatoria y la muestra en la consola; guárdala y cámbiala antes de desplegar. Los requisitos y detalles del proyecto están en [`laravel/README.md`](./laravel/README.md).

La migración de clientes enlaza también las órdenes que ya existían.
