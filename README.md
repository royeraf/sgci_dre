# SGCI DRE - Sistema de Gestión de Control Interno

Sistema de gestión integral para la Dirección Regional de Educación (DRE), diseñado para controlar accesos, visitas y ocurrencias, proporcionando herramientas modernas para la administración de seguridad y personal.

![Laravel](https://img.shields.io/badge/Laravel-10-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)
![Inertia.js](https://img.shields.io/badge/Inertia.js-2.0-9553E9?style=for-the-badge&logo=inertia&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)

## 📋 Características Principales

- **Control de Acceso (Entradas/Salidas)**: Registro y monitoreo de entradas y salidas del personal.
- **Gestión de Visitas Externas**: Registro detallado de visitantes, incluyendo datos personales y motivos de visita.
- **Registro de Ocurrencias**: Sistema para reportar incidentes o eventos relevantes.
- **Gestión de Usuarios y Roles**: Sistema robusto de permisos utilizando `spatie/laravel-permission`.
- **Generación de Reportes PDF**:
  - Papeletas de salida.
  - Pases de visita.
  - Reportes de ocurrencias.
- **Dashboard Interactivo**: Vista general de métricas y actividades recientes.
- **Logs de Auditoría**: Rastro de acciones importantes dentro del sistema para seguridad y control.

## 🛠️ Tecnologías

### Backend
- **Framework**: Laravel 10
- **Base de Datos**: MySQL
- **Autenticación**: Laravel Sanctum / Fortify (implementación estándar de Laravel)
- **Roles y Permisos**: Spatie Laravel Permission
- **PDFs**: Laravel DomPDF

### Frontend
- **JavaScript Framework**: Vue.js 3
- **Adaptador**: Inertia.js (Monolito Moderno)
- **Estilos**: Tailwind CSS
- **Iconos**: Lucide Vue Next
- **Alertas**: SweetAlert2
- **PDF Cliente**: jsPDF / jsPDF-AutoTable

## 🚀 Requisitos de Instalación

1.  **Requisitos del Sistema**:
    - PHP >= 8.1
    - Composer
    - Node.js & NPM
    - MySQL

2.  **Pasos de Instalación**:

    ```bash
    # Clonar el repositorio
    git clone https://github.com/royeraf/sgci_dre.git

    # Ir al directorio del proyecto
    cd sgci_dre

    # Instalar dependencias de PHP
    composer install

    # Instalar dependencias de JavaScript
    npm install

    # Copiar archivo de entorno
    cp .env.example .env

    # Generar clave de aplicación
    php artisan key:generate
    ```

3.  **Configuración**:
    - Crea una base de datos en MySQL.
    - Edita el archivo `.env` con tus credenciales de base de datos (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

4.  **Ejecución**:

    ```bash
    # Correr migraciones y seeders (si aplica)
    php artisan migrate

    # Compilar assets y correr servidor de desarrollo
    npm run dev

    # En otra terminal, servir la aplicación
    php artisan serve
    ```

## 📄 Estructura del Proyecto

- `app/Models`: Modelos de Eloquent (EntryExit, ExternalVisit, Occurrence, etc.).
- `app/Http/Controllers`: Lógica del negocio.
- `resources/js/Pages`: Vistas de Vue.js organizadas por módulo.
- `resources/js/Components`: Componentes reutilizables.
- `database/migrations`: Definición de esquema de base de datos.

## 🤝 Contribución

1.  Haz un Fork del proyecto.
2.  Crea tu rama de funcionalidad (`git checkout -b feature/AmazingFeature`).
3.  Haz Commit de tus cambios (`git commit -m 'Add some AmazingFeature'`).
4.  Haz Push a la rama (`git push origin feature/AmazingFeature`).
5.  Abre un Pull Request.

---
Desarrollado para la Dirección Regional de Educación.
