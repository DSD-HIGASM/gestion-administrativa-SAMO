<div align="center">
  <h1>🏥 Gestión Administrativa SAMO</h1>
  <p><i>Optimizando los procesos administrativos del HIGA Gral. San Martín</i></p>

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/Licencia-MIT-green?style=for-the-badge)
</div>

---

## 🚀 Descripción

Sistema robusto desarrollado en **Laravel** para la gestión integral de la facturación y administración del **SAMO** (Sistema de Atención Médica Organizada).

El objetivo principal es centralizar, agilizar y transparentar el flujo de trabajo del departamento de facturación del hospital, permitiendo la ingesta de datos desde sistemas externos, la organización eficiente de las atenciones y un control de auditoría riguroso.

## ✨ Características Principales

- **📥 Importación de Atenciones:** Carga masiva y gestión eficiente de registros para pacientes de Ambulatorio y Guardia.
- **🔄 Ingesta de Datos (HSI):** Módulo dedicado para la sincronización e integración transparente de datos hospitalarios.
- **🛡️ Auditoría y Expedientes:** Panel de control detallado para auditar el estado de los trámites y documentos.
- **📚 Gestión de Nomencladores:** Administración completa de prácticas, diagnósticos y sus exclusiones.
- **🔐 Control de Acceso:** Sistema seguro de gestión de usuarios, roles y permisos.

> **Nota:** 🚧 *El sistema se encuentra en desarrollo activo. Próximamente se incorporarán nuevas funcionalidades operativas.*

## 🛠️ Requisitos del Sistema

Asegurate de contar con el siguiente entorno para que el proyecto funcione a la perfección:

- **PHP** (Compatible con Laravel 13)
- **Composer** (Gestor de dependencias de PHP)
- **Node.js y NPM** (Para compilar los recursos del frontend)
- **MySQL** (Motor de base de datos)

## ⚙️ Puesta en Marcha

Seguí estos sencillos pasos para levantar el entorno de desarrollo local y empezar a trabajar:

1. **Clonar el repositorio:**
   ```bash
   git clone <url-del-repositorio>
   cd gestion-administrativa-SAMO
   ```
Instalar dependencias (Backend & Frontend):

```Bash
composer install
npm install && npm run build
```
Configurar el Entorno:
Duplicá el archivo de configuración de ejemplo y completá tus credenciales de MySQL (DB_DATABASE, DB_USERNAME, DB_PASSWORD).

```Bash
cp .env.example .env
```
Generar la Key y Migrar:
Estos comandos preparan la seguridad de tu app, crean las tablas en la base de datos y cargan la información inicial necesaria (como los roles y permisos).

```Bash
php artisan key:generate
php artisan migrate --seed
```
¡Iniciar el servidor!

```Bash
php artisan serve
```

📄 Licencia
Este proyecto está protegido bajo los términos de la licencia detallada en el archivo LICENSE incluido en la raíz del repositorio.
