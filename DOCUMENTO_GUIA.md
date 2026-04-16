# Centro de Documentos - Guía de Uso

## 📋 Descripción General

El Centro de Documentos es un sistema profesional de gestión de carpetas y archivos integrado en SystemCobros. Proporciona una interfaz moderna y minimalista para organizar, clasificar y gestionar documentos empresariales.

## ✨ Características

- **Diseño Profesional**: Interfaz minimalista corporativa con gradientes y efectos visuales sofisticados
- **Sistema de Carpetas Jerárquico**: Organiza documentos en múltiples niveles
- **Paleta de Colores**: 10 colores profesionales para identificar carpetas visualmente
- **Gestión Completa**: Crear, editar, eliminar documentos y carpetas
- **Navegación Intuitiva**: Breadcrumbs y navegación clara entre carpetas
- **Responsive Design**: Funciona perfectamente en dispositivos móviles y de escritorio

## 🎨 Paleta de Colores Disponibles

1. **Azul** (#3b82f6) - Profesional, neutral
2. **Cyan** (#06b6d4) - Moderno, fresco
3. **Violeta** (#8b5cf6) - Creativo, innovador
4. **Rosa** (#ec4899) - Energético, llamativo
5. **Naranja** (#f97316) - Cálido, dinámico
6. **Índigo** (#6366f1) - Corporativo, formal
7. **Lima** (#84cc16) - Positivo, natural
8. **Teal** (#14b8a6) - Equilibrado, profesional
9. **Ámbar** (#f59e0b) - Atención, importante
10. **Rojo** (#ef4444) - Urgente, crítico

## 🚀 Cómo Usar

### Acceder al Centro de Documentos

1. Inicia sesión en SystemCobros
2. En la barra lateral izquierda, haz clic en **"Documentos"**
3. Serás dirigido al Centro de Documentos

### Crear una Nueva Carpeta

1. Haz clic en el botón **"+ Crear Carpeta"** en la esquina superior derecha
2. Completa los siguientes campos:
   - **Nombre**: Nombre descriptivo de la carpeta
   - **Descripción**: Descripción opcional para identificar el contenido
   - **Color**: Selecciona uno de los 10 colores disponibles
   - **Ubicación**: Selecciona la carpeta padre (opcional)
3. Haz clic en **"Crear Documento"** para guardar

### Navegar en Carpetas

1. Haz clic en cualquier carpeta para abrir su contenido
2. Usa el **breadcrumb** (migas de pan) en la parte superior para navegar hacia carpetas padre
3. Usa el botón **"Atrás"** para regresar a la vista anterior

### Editar una Carpeta

1. Abre la carpeta que deseas editar
2. Busca las opciones de edición en la interfaz
3. Modifica el nombre, descripción o color
4. Guarda los cambios

### Eliminar una Carpeta

1. Las carpetas solo se pueden eliminar si están vacías
2. Primero, elimina todos los documentos dentro de la carpeta
3. Luego, podrás eliminar la carpeta vacía

## 📱 Vistas del Sistema

### Vista Principal (Centro de Documentos)
- Muestra todas las carpetas raíz del sistema
- Interfaz de grid responsiva con tarjetas
- Información de cantidad de items dentro de cada carpeta

### Vista de Carpeta
- Muestra el contenido de una carpeta específica
- Navegación por breadcrumb
- Botón para regresar a la vista anterior

### Vista de Crear Documento
- Formulario profesional con validación
- Selector visual de colores
- Previa del color seleccionado
- Opción para elegir tipo (Carpeta o Archivo)

### Vista de Editar Documento
- Formulario precargado con datos actuales
- Botón para eliminar el documento
- Información de fecha de creación y modificación

## 🎯 Mejores Prácticas

### Organización
- **Usa nombres descriptivos**: Ej. "Documentos Importantes" en lugar de "Docs"
- **Crea jerarquías lógicas**: Agrupiza por tema, departamento o períodos
- **Asigna colores consistentemente**: Usa el mismo color para carpetas del mismo tipo

### Colores Recomendados
- **Azul / Índigo**: Documentos corporativos, administrativos
- **Verde / Lima**: Documentos activos, en proceso
- **Naranja / Ámbar**: Documentos importantes, requieren atención
- **Rojo**: Documentos urgentes o críticos
- **Cyan / Violeta**: Documentos creativos, especiales

## 🔐 Permisos de Acceso

El Centro de Documentos está disponible para usuarios con los siguientes roles:
- Super Admin
- Admin
- Operator
- Viewer

## 🛠️ Funciones helpers Disponibles

### formatBytes()
Convierte bytes a un formato legible.
```php
formatBytes(1048576) // Retorna: "1 MB"
```

### getColorName()
Obtiene el nombre de un color hexadecimal.
```php
getColorName('#3b82f6') // Retorna: "Azul"
```

### getFileIcon()
Obtiene el icono Font Awesome adecuado para un tipo de archivo.
```php
getFileIcon('documento.pdf') // Retorna: "fa-file-pdf"
```

## 📊 Estructura de Base de Datos

La tabla `documents` contiene:
- `id`: Identificador único
- `name`: Nombre del documento
- `description`: Descripción opcional
- `type`: Tipo (folder o file)
- `parent_id`: ID de la carpeta padre (relación jerárquica)
- `path`: Ruta del archivo (si aplica)
- `icon`: Clase de icono Font Awesome
- `color`: Color hexadecimal de la carpeta
- `size`: Tamaño del archivo en bytes
- `created_by`: Usuario que creó el documento
- `updated_by`: Usuario que modificó el documento
- `timestamps`: Fechas de creación y modificación

## 🔄 Migraciones

Para aplicar las migraciones del Centro de Documentos:

```bash
php artisan migrate
```

Para ejecutar los seeders de ejemplo:

```bash
php artisan db:seed --class=DocumentSeeder
```

## 🎨 Personalización de Estilos

El sistema utiliza variables CSS personalizadas que pueden modificarse en:
- `resources/css/professional-design.css`

Variables disponibles:
```css
--color-primary: #3b82f6;
--color-success: #10b981;
--color-warning: #f59e0b;
--color-danger: #ef4444;
--radius-base: 8px;
--shadow-base: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
```

## 💡 Consejos Adicionales

1. **Usa espacios en blanco**: La interfaz está diseñada con espaciado generoso para una lectura fácil
2. **aprovecha las descripciones**: Las descripciones ayudan a otros usuarios a entender el propósito de cada carpeta
3. **Actualiza regularmente**: Mantén los documentos organizados y actualizados
4. **Revisa la información**: Cada carpeta muestra cuántos items contiene
5. **Usa el breadcrumb**: Es la forma más rápida de navegar hacia carpetas padre

## 📞 Soporte

Para reportar problemas o sugerir mejorias, contacta con el equipo de administración del sistema.

---

**Versión**: 1.0  
**Última actualización**: Abril 2026
