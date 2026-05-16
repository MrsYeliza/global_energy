# Global Energy B.C. — Sistema de Ventas

Sistema web en PHP + MySQL para gestión de ventas a crédito de electrodomésticos.

---

## Requisitos

- XAMPP (o WAMP / LAMP) con PHP 7.4+ y MySQL 5.7+
- Navegador moderno

---

## Instalación paso a paso

### 1. Copiar archivos
Copia la carpeta `global_energy/` completa dentro de:
```
C:\xampp\htdocs\global_energy\        (Windows)
/Applications/XAMPP/htdocs/global_energy/  (Mac)
```

### 2. Crear la base de datos
1. Abre XAMPP y enciende **Apache** y **MySQL**
2. Ve a `http://localhost/phpmyadmin`
3. Haz clic en **Importar** (arriba)
4. Selecciona el archivo `sql/global_energy.sql`
5. Haz clic en **Continuar**

### 3. Configurar conexión (si es necesario)
Abre `includes/conexion.php` y ajusta si tienes contraseña en MySQL:
```php
define('DB_USER', 'root');
define('DB_PASS', '');   // Pon tu contraseña aquí si tienes una
```

### 4. Permisos de la carpeta uploads
En Linux/Mac ejecuta:
```bash
chmod 755 uploads/
```
En Windows XAMPP no requiere configuración adicional.

### 5. Acceder al sistema
Abre en tu navegador:
```
http://localhost/global_energy/
```

---

## Credenciales de prueba

| Usuario | Correo | Contraseña | Rol |
|---------|--------|------------|-----|
| Admin | admin@globalenergy.mx | password | Administrador |
| Vendedor | vendedor1@globalenergy.mx | password | Vendedor |

---

## Estructura del proyecto

```
global_energy/
├── index.php              → Redirección automática
├── login.php              → Inicio de sesión
├── logout.php             → Cerrar sesión
├── ventas.php             → Lista de ventas (pantalla principal)
├── agregar_venta.php      → Nueva venta / editar venta
├── detalle_venta.php      → Ver detalle de una venta
├── clientes.php           → Lista de clientes
├── productos.php          → Lista de productos/inventario
├── reportes.php           → Reportes y estadísticas
│
├── includes/
│   ├── conexion.php       → Conexión PDO a MySQL
│   ├── auth.php           → Sesiones y funciones de autenticación
│   ├── header.php         → HTML cabecera + sidebar
│   └── footer.php         → Cierre HTML
│
├── assets/
│   ├── css/style.css      → Estilos globales
│   └── js/main.js         → JavaScript básico
│
├── uploads/               → Archivos subidos (pólizas, facturas, etc.)
│
└── sql/
    └── global_energy.sql  → Script de base de datos con datos de prueba
```

---

## Funcionalidades incluidas

- ✅ Login con sesiones seguras (PHP sessions)
- ✅ Lista de ventas con búsqueda y filtros
- ✅ Registrar nueva venta (cliente + producto + crédito + documentos)
- ✅ Editar ventas existentes
- ✅ Ver detalle completo de una venta
- ✅ Subir documentos: Póliza, Factura, Contrato, Presupuesto, Requisitos
- ✅ Gestión de clientes
- ✅ Catálogo de productos con inventario
- ✅ Créditos quincenales con cálculo automático
- ✅ Reportes de ventas por mes y por tipo de producto
- ✅ Datos de prueba incluidos

---

## Seguridad implementada

- Consultas con PDO y parámetros preparados (previene SQL injection)
- `htmlspecialchars()` en toda salida HTML (previene XSS)
- Verificación de sesión en todas las páginas protegidas
- Validación de extensiones de archivo en uploads
- Cookies de sesión con `httponly` y `samesite`
