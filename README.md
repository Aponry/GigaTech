*IMPORTANTE*
//*Todo lo de este archivo es unicamente para terminar de especificar funcionamientos de la página
y para hacer una mini guía de instalación local de dicha página y dejar en perfecto funcionamiento
de forma local.*//

# GigaTech - Sistema de Gestión de Pizzería

## Descripción del Proyecto

GigaTech es un sistema web completo para la gestión de una pizzería, que permite tanto a clientes como empleados interactuar con el sistema. Los clientes pueden ver el menú, agregar productos al carrito y realizar pedidos, mientras que los empleados pueden gestionar productos, ingredientes, promociones y pedidos.

## Estructura del Proyecto

El proyecto está dividido en dos áreas principales:

1. **Cliente** (`/cliente`): Área pública para los clientes
   - Página principal con el menú completo
   - Detalle de productos
   - Carrito de compras
   - Gestión de pedidos

2. **Empleado** (`/empleado`): Área administrativa para empleados
   - Gestión de ingredientes
   - Gestión de productos
   - Gestión de promociones
   - Gestión de pedidos
   - Control de stock

## Requisitos del Sistema

- XAMPP (Apache + MySQL + PHP)
- Navegador web moderno

## Instalación Local

1. **Instalar XAMPP**:
   - Descargar e instalar XAMPP desde https://www.apachefriends.org/index.html
   - Asegurarse de que los servicios de Apache y MySQL estén incluidos

2. **Configurar el proyecto**:
   - Copiar todo el contenido del proyecto en la carpeta `htdocs` de XAMPP
   - La ruta típica sería: `C:\xampp\htdocs\GigaTech\GigaTech`

3. **Iniciar servicios**:
   - Abrir el Panel de Control de XAMPP
   - Iniciar los servicios de Apache y MySQL

4. **Crear la base de datos**:
   - Acceder a phpMyAdmin mediante `http://localhost/phpmyadmin`
   - Crear una nueva base de datos llamada `pizzaconmigo`
   - Importar el archivo de estructura de base de datos (si está disponible)

5. **Configurar la conexión a la base de datos**:
   - Verificar el archivo `conexion.php` en la raíz del proyecto
   - Asegurarse de que los datos de conexión sean correctos:
     ```php
     $host = '127.0.0.1';
     $puerto = '3306';
     $usuario = 'root';
     $contrasena = '';
     $base_datos = 'pizzaconmigo';
     ```

6. **Acceder al sistema**:
   - Cliente: `http://localhost/GigaTech/GigaTech/index.php`
   - Empleado: `http://localhost/GigaTech/GigaTech/empleado/menu.php`

## Funcionalidades

### Área Cliente

- **Menú completo**: Visualización de todos los productos disponibles
- **Detalle de productos**: Información detallada de cada producto, incluyendo ingredientes
- **Carrito de compras**: Agregar productos, gestionar cantidades y realizar pedidos
- **Promociones**: Visualización de promociones especiales

### Área Empleado

- **Gestión de productos**: Alta, baja, modificación y consulta de productos
- **Gestión de ingredientes**: Administración de ingredientes disponibles para productos personalizables
- **Gestión de promociones**: Creación y administración de promociones
- **Gestión de pedidos**: Seguimiento y actualización del estado de pedidos
- **Control de stock**: Verificación de niveles de inventario

## Solución de Problemas Comunes

### Error 500 en obtener_detalle_producto.php

Este error puede deberse a:
- Problemas de conexión con la base de datos
- Tablas inexistentes en la base de datos
- Consultas SQL incorrectas

Verifique que:
1. La base de datos `pizzaconmigo` exista y tenga todas las tablas necesarias
2. Los nombres de las tablas en las consultas SQL sean correctos
3. La conexión a la base de datos en `conexion.php` sea correcta

### Precios en cero para bebidas y postres

Este problema ocurre cuando:
- Los datos de postres y bebidas no se cargan correctamente en la página de detalles
- Las consultas SQL no incluyen la información necesaria

Se resuelve asegurando que:
1. El endpoint `obtener_detalle_producto.php` devuelve información sobre postres y bebidas
2. La estructura de datos sea consistente entre productos y promociones

### Errores en la visualización de imágenes

Para resolver problemas con imágenes:
1. Verifique que las rutas de las imágenes sean correctas
2. Asegúrese de que los archivos de imagen existan en las carpetas correspondientes
3. Compruebe los permisos de las carpetas de imágenes

## Estructura de la Base de Datos

La base de datos `pizzaconmigo` debe contener las siguientes tablas principales:
- `productos`: Información de productos
- `ingrediente`: Ingredientes disponibles
- `promocion`: Promociones especiales
- `promocion_producto`: Relación entre promociones y productos
- `pedido`: Pedidos realizados
- `pedido_producto`: Productos en pedidos

## Desarrollo y Mantenimiento

### Convenciones de Código

- Nombres de variables en español
- Comentarios en español
- Estructura de archivos organizada por funcionalidad
- Separación clara entre frontend y backend

### Actualizaciones

Para actualizar el sistema:
1. Realizar copia de seguridad de la base de datos
2. Respaldar archivos actuales
3. Aplicar cambios
4. Verificar funcionamiento

## Soporte

Para problemas técnicos, verificar:
1. Registros de error de Apache/PHP
2. Consola del navegador (para errores de JavaScript)
3. Estado de la base de datos
4. Permisos de archivos y carpetas

## API Endpoints Documentation

### Payment Proof Upload Endpoint

**Endpoint:** `POST /subir_comprobante.php`

**Purpose:** Handles secure upload of payment proof files (images/PDFs) for order verification.

**Parameters:**
- `comprobante_pago` (file, required): The payment proof file (JPG, PNG, GIF, or PDF)
- `csrf_token` (string, required): CSRF protection token

**Authentication:** Requires active user session

**File Constraints:**
- Maximum size: 5MB
- Allowed types: image/jpeg, image/png, image/gif, application/pdf
- Files are stored in `uploads/comprobantes/` with random filenames
- SHA256 hash calculated and stored for integrity verification

**Success Response (200):**
```json
{
  "ok": true,
  "upload_id": 123,
  "proof_path": "uploads/comprobantes/comprobante_abc123.jpg",
  "sha256": "hash_value"
}
```

**Error Responses:**
- `400`: Invalid file type, size exceeded, or missing file
- `403`: Invalid CSRF token
- `405`: Method not allowed (only POST)
- `500`: Database or file system errors

**Security Notes:**
- CSRF token validation required
- File type validation using MIME type detection
- Random filename generation to prevent enumeration attacks
- SHA256 hash for file integrity
- File size limits to prevent DoS attacks
- Files cleaned up on database insertion failure

### Place Order Endpoint

**Endpoint:** `POST /enviar_pedido.php`

**Purpose:** Processes customer orders, validates cart contents, and creates order records.

**Parameters (JSON payload):**
- `csrf_token` (string, required): CSRF protection token
- `cliente` (object, required):
  - `nombre` (string, required): Customer name
  - `email` (string, required): Valid email address
  - `telefono` (string, required): Phone number
  - `direccion` (string, required): Delivery address (required for delivery)
  - `notas` (string, optional): Order notes
  - `tipo_entrega` (string, required): 'delivery' or 'retiro'
- `metodo_pago` (string, required): One of: 'efectivo', 'tarjeta', 'transferencia'
- `upload_id` (integer, optional): Payment proof upload ID for transfer payments

**Authentication:** Requires active session with cart data (`$_SESSION['carrito']`)

**Validation:**
- Cart must not be empty
- All required customer fields must be present
- Email format validation
- Delivery type must be valid
- Payment method must be valid
- Address required for delivery
- Prices recalculated from database to prevent tampering
- Ingredient costs added to base prices

**Success Response (200):**
```json
{
  "ok": true,
  "order_id": 456,
  "order_code": "PC123456",
  "status": "pendiente_aprobacion",
  "total": 25.50
}
```

**Error Responses:**
- `400`: Invalid JSON, missing fields, invalid email, empty cart, etc.
- `403`: Invalid CSRF token
- `405`: Method not allowed (only POST)
- `500`: Database connection or transaction errors

**Database Operations:**
- Inserts into `pedido` table with status 'pendiente' or 'pendiente_aprobacion' (for transfers)
- Inserts order details into `detalle_pedido` table
- Links payment proof upload if provided
- Uses database transactions for consistency
- Clears cart session after successful order
- Generates unique order code

**Security Notes:**
- CSRF token validation
- Server-side price recalculation
- Input sanitization and validation
- Database prepared statements
- Transaction rollback on errors

### Admin Workflow for Manual Payment Verification

**Overview:** Orders with payment method 'transferencia' require manual verification by admins.

**Workflow Steps:**

1. **Order Submission:**
    - Customer places order with 'transferencia' payment method
    - Order status set to 'pendiente_aprobacion' automatically
    - Admin notification sent via email with order details

2. **Payment Proof Upload:**
     - Customer uploads payment proof via `subir_comprobante.php`
     - File stored securely in `uploads/comprobantes/` with SHA256 hash
     - Upload linked to order in `payment_uploads` table
    - No automatic order status change

3. **Admin Verification Process:**
    - Admin receives email notification with order details
    - Admin accesses order management (`empleado/pedidos/pedidos.php`)
    - Reviews order details, customer information, and payment proof
    - Manually verifies payment proof against order amount
    - Updates order status via `accion_pedido.php`:
      - 'pendiente_aprobacion' → 'confirmado' (payment verified)
      - Or 'cancelado' if payment invalid

4. **Status Transitions:**
    - `pendiente_aprobacion` → `confirmado` (payment verified)
    - `confirmado` → `en_preparacion` → `listo` → `en_reparto` → `entregado`
    - `pendiente_aprobacion` → `cancelado` (payment rejected)

**Admin Interface:**
- Located at `empleado/pedidos/pedidos.php`
- Filters by status, date range
- Displays customer details, order items, payment method, order code
- Status change dropdown with allowed transitions
- Payment proof files accessible for verification

**Security Considerations:**
- Payment proofs stored outside web root with restricted access
- Admin access restricted by session role validation
- File integrity verified with SHA256 hashes
- No automatic status changes based on uploads
- CSRF protection on all admin actions

**Implementation Notes:**
- Admin notification sent via PHP mail() function
- Payment verification is manual process requiring admin judgment
- Order codes (PC######) generated for tracking
- Database relationships: pedido linked to payment_uploads via id_pedido
- Email notifications include all order details for immediate review

## Notas Finales

Este sistema está diseñado para funcionar en un entorno local con XAMPP. Para entornos de producción, se recomienda:
- Configurar adecuadamente la seguridad
- Utilizar contraseñas seguras para la base de datos
- Configurar respaldos automáticos
- Implementar HTTPS si se va a usar en línea