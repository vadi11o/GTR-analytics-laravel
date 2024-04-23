# Instrucciones de Instalación y Uso

Bienvenido al proyecto. A continuación, encontrarás las instrucciones para descargar y poner en marcha el código en tu entorno local.

## Descarga e Instalación

Para comenzar, asegúrate de tener `composer` y Docker instalados en tu sistema. Luego, sigue estos pasos para preparar el entorno de desarrollo:

1. **Instalar Dependencias de Composer**

   Ejecuta el siguiente comando para instalar las dependencias PHP necesarias a través de Composer:

```
composer install
```

2. **Configurar Archivo .env**

Copia el archivo de ejemplo `.env.example` a `.env`, el cual se utilizará para configurar tu entorno:

```
cp .env.example .env
```

3. **Levantar los Contenedores de Docker**

Utiliza Laravel Sail, una interfaz de línea de comandos para gestionar tu entorno de Docker, para levantar los contenedores:

```
./vendor/bin/sail up -d
```

4. **Ejecutar Migraciones de Base de Datos**

Finalmente, ejecuta las migraciones de la base de datos para preparar tu esquema:

```
./vendor/bin/sail php artisan migrate
```

## Endpoints Disponibles

Una vez que el proyecto esté corriendo, podrás acceder a los siguientes endpoints:

- **Streams**

`http://localhost/analytics/streams`

- **Usuarios**

Accede a la información de un usuario específico usando su ID (reemplaza `1234` con el ID deseado):

`http://localhost/analytics/users?id=1234`

- **Top de los Tops**

`http://localhost/analytics/topsofthetops`

Estos endpoints te permitirán interactuar con el proyecto y probar sus funcionalidades. ¡Disfruta explorando lo que has construido!
