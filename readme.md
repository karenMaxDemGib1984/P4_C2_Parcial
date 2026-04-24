###### La Despensa de Don Juan — Sistema de Tienda en Línea

**Proyecto:** App web con base de datos relacional en PHP  
**Empresa:** La Despensa de Don Juan· San Miguel, El Salvador  
**Tecnologías:** PHP 8+, MySQL, HTML5, CSS3



###### Preguntas y Respuestas

###### 1\. ¿Cómo manejan la conexión a la BD y qué pasa si algunos de los datos son incorrectos?

**Conexión:**  
La conexión se realiza en `config/db.php` usando **PDO (PHP Data Objects)** con el modo de error `PDO::ERRMODE\_EXCEPTION`. Esto significa que cualquier fallo de conexión o consulta lanza automáticamente una excepción que podemos capturar con `try/catch`.

```php
$pdo = new PDO($dsn, DB\_USER, DB\_PASS, \[
    PDO::ATTR\_ERRMODE => PDO::ERRMODE\_EXCEPTION,
    PDO::ATTR\_DEFAULT\_FETCH\_MODE => PDO::FETCH\_ASSOC,
    PDO::ATTR\_EMULATE\_PREPARES   => false,
]);
```

**¿Por qué PDO?** Porque es compatible con varios motores de BD (MySQL, PostgreSQL, SQLite) y, lo más importante, obliga a usar sentencias preparadas, eliminando la vulnerabilidad de SQL Injection.

**Si los datos de conexión son incorrectos:**

* La excepción `PDOException` es capturada.
* El error real se registra con `error\_log()` (solo visible en el servidor, no al usuario).
* Al usuario se le muestra un mensaje genérico: *"Error al conectar con la base de datos. Intente más tarde."*
* Esto protege información sensible (nombre de BD, usuario, contraseña) de ser expuesta.

**Validación de datos del formulario (doble capa):**

|Capa|Dónde|Ejemplo|
|-|-|-|
|**Frontend**|HTML5 atributos (`required`, `type="email"`, `pattern`)|Valida en el navegador antes de enviar|
|**Backend**|PHP (`filter\_var`, `is\_numeric`, `preg\_match`, `in\_array`)|Valida en el servidor aunque el usuario omita el frontend|

La validación del servidor es obligatoria porque cualquier usuario avanzado puede omitir las validaciones HTML5.

\---



###### 2\. ¿Cuál es la diferencia entre `$\_GET` y `$\_POST` en PHP? ¿Cuándo es más apropiado usar cada uno?

|Característica|`$\_GET`|`$\_POST`|
|-|-|-|
|**Visibilidad**|Los datos van en la URL: `?msg=ok\&id=5`|Los datos van en el cuerpo HTTP, invisibles en la URL|
|**Límite de datos**|\~2 000 caracteres (límite de URL)|Sin límite práctico (configurable en `php.ini`)|
|**Caché y favoritos**|Se puede guardar en favoritos o historial|No se guarda en historial ni se puede marcar|
|**Idempotencia**|Seguro para operaciones que no cambian datos|Para operaciones que modifican datos (INSERT, UPDATE)|
|**Seguridad**|Nunca para contraseñas o datos sensibles|Datos viajan en el cuerpo; usar HTTPS para cifrarlos|

**¿Cuándo usar cada uno?**

* `$\_GET`: Para **filtrar, buscar o paginar** sin alterar datos. Ejemplo: `productos.php?categoria=lacteos\&pagina=2`.
* `$\_POST`: Para **enviar formularios** que insertan, modifican o eliminan registros, y siempre que haya datos sensibles como contraseñas.

**Ejemplo real del proyecto:**

```php
// $\_GET — Muestra mensaje informativo después de cerrar sesión (no modifica datos)
// URL: index.php?msg=sesion\_cerrada
if (isset($\_GET\['msg']) \&\& array\_key\_exists($\_GET\['msg'], $mensajes)) {
    $msgSistema = $mensajes\[$\_GET\['msg']];
}

// $\_POST — Registro de un nuevo pedido (inserta un registro en la BD)
// En: actions/guardar\_pedido.php
$cliente\_nombre = sanitizar($\_POST\['cliente\_nombre'] ?? '');
$metodo\_pago    = sanitizar($\_POST\['metodo\_pago']    ?? '');
$total          = $\_POST\['total'] ?? '';
```

\---



###### 3\. ¿Qué riesgos de seguridad identificas en una app web con BD que maneja datos de los usuarios? ¿Cómo los mitigarían?

La app va a operar en la zona oriental de El Salvador, donde el uso de datos personales (nombres, correos, NIT, teléfonos) exige responsabilidad. Los riesgos identificados y sus mitigaciones son:

|#|Riesgo|Descripción|Mitigación aplicada|
|-|-|-|-|
|1|**SQL Injection**|Un atacante inyecta SQL malicioso en un campo del formulario para leer o destruir la BD|**Sentencias preparadas** con PDO: `$stmt->execute(\[$valor])`. Nunca se concatenan variables en el SQL.|
|2|**XSS (Cross-Site Scripting)**|El atacante guarda código JavaScript en la BD y se ejecuta en el navegador de otros usuarios|`htmlspecialchars()` al mostrar cualquier dato de la BD en HTML, y `strip\_tags()` al recibir los datos.|
|3|**Contraseñas en texto plano**|Si la BD es robada, las contraseñas quedan expuestas|`password\_hash()` con el algoritmo **bcrypt** para guardar y `password\_verify()` para comprobar.|
|4|**Session Fixation / Hijacking**|Un atacante roba o fija el ID de sesión para suplantar a un usuario|`session\_regenerate\_id(true)` tras el login exitoso; destruir sesión al hacer logout.|
|5|**Acceso no autorizado**|Usuarios no autenticados acceden a páginas de administración directamente|La función `requiereLogin()` redirige a `index.php` si no hay sesión activa.|
|6|**Exposición de errores**|Mensajes de error detallados revelan estructura de la BD o rutas del servidor|En producción, `display\_errors = Off` en `php.ini`; los errores reales solo se registran con `error\_log()`.|
|7|**Fuerza bruta en login**|Un bot prueba miles de contraseñas automáticamente|Implementar límite de intentos por IP (con tabla `intentos\_login`) y CAPTCHA en producción.|
|8|**Datos en tránsito sin cifrar**|Un intermediario intercepta la comunicación (Man-in-the-Middle)|Usar **HTTPS** con certificado SSL/TLS (Let's Encrypt es gratuito) en el servidor de producción.|

\---

## 

###### Diccionario de Datos

\---

###### Tabla: `usuarios`

Almacena las credenciales del personal autorizado para acceder al sistema.

|Columna|Tipo de dato|Límite de caracteres|¿Es nulo?|Descripción|
|-|-|-|-|-|
|`id`|INT AUTO\_INCREMENT|—|NO (PK)|Identificador único del usuario|
|`nombre`|VARCHAR|100|NO|Nombre completo del empleado|
|`email`|VARCHAR|150|NO (UNIQUE)|Correo electrónico; usado como usuario de login|
|`password`|VARCHAR|255|NO|Hash bcrypt de la contraseña|
|`rol`|ENUM|—|NO|Rol del usuario: `admin` o `empleado`|
|`created\_at`|TIMESTAMP|—|NO|Fecha y hora de creación del registro|

\---

###### Tabla: `productos`

Almacena el catálogo de productos disponibles en la tienda.

|Columna|Tipo de dato|Límite de caracteres|¿Es nulo?|Descripción|
|-|-|-|-|-|
|`id`|INT AUTO\_INCREMENT|—|NO (PK)|Identificador único del producto|
|`nombre`|VARCHAR|150|NO|Nombre descriptivo del producto|
|`categoria`|ENUM|—|NO|Categoría: `lacteos`, `carnes`, `bebidas`, `panaderia`, `limpieza`, `frutas\_verduras`, `otros`|
|`precio`|DECIMAL(8,2)|—|NO|Precio unitario en dólares (USD)|
|`stock`|INT|—|NO|Cantidad disponible en inventario|
|`descripcion`|TEXT|\~65 535 bytes|**SÍ**|Descripción opcional del producto (puede ser NULL)|
|`disponible`|TINYINT(1)|—|NO|1 = disponible para venta, 0 = no disponible|
|`created\_at`|TIMESTAMP|—|NO|Fecha y hora de registro del producto|

\---

###### Tabla: `pedidos`

Registra los pedidos realizados por los clientes.

|Columna|Tipo de dato|Límite de caracteres|¿Es nulo?|Descripción|
|-|-|-|-|-|
|`id`|INT AUTO\_INCREMENT|—|NO (PK)|Identificador único del pedido|
|`cliente\_nombre`|VARCHAR|150|NO|Nombre completo del cliente|
|`cliente\_email`|VARCHAR|150|NO|Correo electrónico del cliente|
|`cliente\_telefono`|VARCHAR|20|NO|Teléfono en formato `####-####`|
|`metodo\_pago`|ENUM|—|NO|Forma de pago: `efectivo`, `tarjeta`, `transferencia`|
|`requiere\_factura`|TINYINT(1)|—|NO|1 = requiere factura fiscal, 0 = no|
|`nit`|VARCHAR|20|**SÍ**|NIT del cliente; solo requerido si `requiere\_factura = 1` (puede ser NULL)|
|`total`|DECIMAL(10,2)|—|NO|Monto total del pedido en USD|
|`estado`|ENUM|—|NO|Estado del pedido: `pendiente`, `procesando`, `entregado`, `cancelado`|
|`notas`|TEXT|\~65 535 bytes|**SÍ**|Instrucciones adicionales del cliente (puede ser NULL)|
|`created\_at`|TIMESTAMP|—|NO|Fecha y hora en que se registró el pedido|



