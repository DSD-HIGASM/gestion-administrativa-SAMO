# 🏥 Sistema de Gestión Administrativa SAMO (Beta 1)

### Hospital Interzonal General de Agudos "Gral. San Martín" — La Plata
**División de Salud Digital**

---

## 📝 Descripción
Este sistema ha sido diseñado para centralizar y optimizar la facturación de prestaciones médicas, permitiendo la transición fluida de datos desde la **Historia de Salud Integrada (HSI)** hacia los procesos administrativos de **SAMO**[cite: 1, 2].

El objetivo principal de la **Beta 1** es la digitalización total del flujo de **Guardia**, eliminando el uso de papel y permitiendo una auditoría en tiempo real de cada expediente.

---

## ✨ Características Principales

*   **⚡ Bandeja de Guardia Inteligente**: Recepción, búsqueda y asignación masiva de episodios importados de HSI.
*   **📑 Expediente Digital Único**: Gestión integral de prácticas, diagnósticos (CIE-10) y documentación adjunta por cada atención.
*   **💳 Padronización de Coberturas**: Sistema multiobrapuente que gestiona diversas coberturas por paciente con validación de vigencia automática.
*   **⚙️ Reglas de Jefatura**: Panel de configuración para ocultar automáticamente servicios o profesionales no facturables (ej. Enfermería), manteniendo la bandeja de trabajo limpia.
*   **📥 Ingesta de Datos HSI**: Motor de importación procesado en segundo plano para archivos Excel provenientes de sistemas externos.
*   **🕵️ Auditoría "Caja Negra"**: Registro pormenorizado de cada acción, cambio de estado y usuario interviniente para trazabilidad total[cite: 2].

---

## 🛠️ Stack Tecnológico

*   **Framework:** [Laravel 11+](https://laravel.com/)[cite: 2]
*   **Frontend:** [Livewire (Volt)](https://livewire.laravel.com/) & [Tailwind CSS](https://tailwindcss.com/)[cite: 2]
*   **Base de Datos:** MySQL / MariaDB[cite: 2]
*   **Permisos:** [Spatie Permission](https://spatie.be/docs/laravel-permission/v6/introduction)[cite: 2]
*   **Excel:** [Laravel Excel (Maatwebsite)](https://docs.laravel-excel.com/)[cite: 2]
*   **Bundler:** Vite[cite: 2]

---

## 🚀 Instalación (Desarrollo)

1. **Clonar el repositorio:**
   ```bash
   git clone [URL-DEL-REPO]
   cd gestion-administrativa-SAMO
    ```
2. **Instalar dependencias:**
    ```bash
    composer install
    npm install
    ```

3. **Configurar el entorno:**

* Copia el archivo de ejemplo: cp .env.example .env.

* Genera la key: php artisan key:generate.

* Configura tus credenciales de DB en el .env.

4. **Migraciones y Seeders:**

```bash
php artisan migrate --seed
```
Esto cargará los estados iniciales, roles y el catálogo CIE-10.

5. **Iniciar:**

```bash
npm run dev
php artisan serve
```

🏗️ **Despliegue en Producción**

Para garantizar la estabilidad en el entorno hospitalario, siga estos pasos:

1. Compilar activos: ```npm run build```.

2. Optimizar Laravel: ```php artisan optimize```.

3. Enlace de Archivos: ```php artisan storage:link```.

4. Colas (Queues): Configurar Supervisor para ejecutar ```php artisan queue:work```. Esto es indispensable para la importación de archivos HSI.

👥 **Créditos**
Desarrollado con compromiso por la División de Salud Digital del HIGA General San Martín.

SAMO v1.0 Beta 1 | 2026
