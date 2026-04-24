###### Karen Esmeralda Portillo Portillo SMSS202223
###### Yolanda Isabel Marroquín Ulloa SMSS047424



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



### 3. ¿Qué riesgos de seguridad identificas en una app web con BD que maneja datos de los usuarios? ¿Cómo los mitigarían?

La Despensa de Don Juan opera en la zona oriental de El Salvador y su sistema maneja datos personales y comerciales de los clientes: nombres completos, correos electrónicos, números de teléfono, NIT y montos de pedidos. Esto representa una responsabilidad legal y ética ante los usuarios, por lo que identificamos los siguientes riesgos y sus mitigaciones:



**Riesgo 1 — Inyección SQL (SQL Injection)**

Ocurre cuando un atacante escribe código SQL malicioso dentro de un campo del formulario (por ejemplo, en el nombre del cliente) con el objetivo de manipular las consultas a la base de datos. Esto podría permitirle leer toda la información de los clientes, borrar tablas o incluso tomar control del servidor de BD.

Mitigación aplicada: En `actions/guardar_pedido.php` y `actions/guardar_producto.php` todas las consultas usan sentencias preparadas con PDO. Los datos del usuario nunca se concatenan directamente en el SQL:

```php
// INCORRECTO (vulnerable):
$pdo->query("SELECT * FROM usuarios WHERE email = '$email'");

// CORRECTO (aplicado en nuestro proyecto):
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
```

---

**Riesgo 2 — XSS (Cross-Site Scripting)**

Un atacante podría ingresar código JavaScript como nombre de producto o nombre de cliente. Si ese dato se imprime sin filtrar en el HTML, el script se ejecutaría en el navegador de cualquier usuario que vea la página, pudiendo robar sesiones o redirigir a sitios falsos.

Mitigación aplicada: En toda impresión de datos de la BD se aplica `htmlspecialchars()`, que convierte caracteres peligrosos en entidades HTML inofensivas. Además, la función `sanitizar()` en `config/session.php` aplica `strip_tags()` al recibir los datos del formulario:

```php
// En config/session.php
function sanitizar(string $valor): string {
    return htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
}

// Al imprimir en HTML (index.php, dashboard.php)
echo htmlspecialchars($producto['nombre']);
```

---

**Riesgo 3 — Contraseñas almacenadas en texto plano**

Si la base de datos fuera robada o accedida sin autorización y las contraseñas estuvieran guardadas tal como el usuario las escribe, el atacante tendría acceso inmediato a todas las cuentas del sistema.

Mitigación aplicada: Las contraseñas se almacenan usando el algoritmo **bcrypt** a través de `password_hash()`. Al momento del login, `password_verify()` compara la contraseña ingresada contra el hash almacenado, sin necesidad de descifrarla:

```php
// Al crear usuario (nunca se guarda el texto plano):
$hash = password_hash('miContrasena123', PASSWORD_BCRYPT);

// Al hacer login (auth/login.php):
if ($usuario && password_verify($password, $usuario['password'])) {
    // acceso concedido
}
```

---

**Riesgo 4 — Secuestro de sesión (Session Fixation / Hijacking)**

Si el ID de sesión no cambia después del login, un atacante que conozca ese ID previamente (por haberlo forzado o interceptado) podría suplantar al usuario autenticado sin necesitar su contraseña.

Mitigación aplicada: En `auth/login.php`, inmediatamente después de validar las credenciales correctas, se llama a `session_regenerate_id(true)`. Esto genera un ID de sesión nuevo e invalida el anterior. Al hacer logout, se destruye completamente la sesión:

```php
// auth/login.php — tras login exitoso
session_regenerate_id(true);
$_SESSION['usuario_id'] = $usuario['id'];

// auth/logout.php
session_unset();
session_destroy();
```

---

**Riesgo 5 — Acceso no autorizado a páginas protegidas**

Sin un control de acceso, cualquier persona podría escribir directamente la URL `dashboard.php` en el navegador y acceder al panel de administración sin haber iniciado sesión.

Mitigación aplicada: La función `requiereLogin()` definida en `config/session.php` verifica al inicio de `dashboard.php` si existe una sesión activa. Si no la hay, redirige inmediatamente a `index.php`:

```php
// config/session.php
function requiereLogin(): void {
    if (!estaAutenticado()) {
        header('Location: ../index.php?msg=acceso_denegado');
        exit;
    }
}

// dashboard.php — primera línea de lógica
requiereLogin();
```

---

**Riesgo 6 — Exposición de errores del servidor**

En modo de desarrollo, PHP muestra mensajes de error detallados que pueden revelar la ruta del archivo, el nombre de la base de datos, el usuario de MySQL o la estructura de las tablas. Esta información es muy valiosa para un atacante.

Mitigación aplicada: Todos los bloques `catch` registran el error real únicamente en el log del servidor con `error_log()` y muestran al usuario solo un mensaje genérico. En el servidor de producción se debe configurar `display_errors = Off` en el archivo `php.ini`:

```php
} catch (PDOException $e) {
    error_log("Error BD: " . $e->getMessage()); // solo en el log del servidor
    die('Error al conectar. Intente más tarde.'); // mensaje genérico al usuario
}
```

---

**Riesgo 7 — Fuerza bruta en el formulario de login**

Un atacante puede usar herramientas automatizadas para probar miles de combinaciones de correo y contraseña por segundo hasta encontrar una válida, especialmente si las contraseñas son débiles.

Mitigación propuesta para producción: Crear una tabla `intentos_login` que registre la IP y la hora de cada intento fallido. Si una IP supera 5 intentos en 10 minutos, se bloquea temporalmente. Adicionalmente, se recomienda agregar un CAPTCHA (como Google reCAPTCHA v3) al formulario de login.

---
**Riesgo 8 — Datos en tránsito sin cifrar (Man-in-the-Middle)**

Si el sistema corre sobre HTTP puro (sin SSL), cualquier persona en la misma red (por ejemplo, en una red Wi-Fi pública del mercado o de una empresa) puede interceptar los datos que viajan entre el navegador del usuario y el servidor, incluyendo credenciales y datos de clientes.

Mitigación propuesta para producción: Instalar un certificado SSL/TLS en el servidor web. Let's Encrypt ofrece certificados gratuitos y su renovación es automática. Esto fuerza que toda la comunicación viaje cifrada sobre HTTPS. En el archivo `.htaccess` se puede redirigir todo el tráfico HTTP a HTTPS:

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 📚 Diccionario de Datos

---

### Tabla: `usuarios`

Almacena las credenciales del personal autorizado para acceder al sistema de administración.

| Columna | Tipo de dato | Límite de caracteres | ¿Es nulo? | Descripción |
|---|---|---|---|---|
| id | INT AUTO_INCREMENT | — | NO | Identificador único del usuario. Clave primaria. |
| nombre | VARCHAR | 100 | NO | Nombre completo del empleado registrado en el sistema. |
| email | VARCHAR | 150 | NO | Correo electrónico del empleado. Se usa como nombre de usuario para el login. Valor único. |
| password | VARCHAR | 255 | NO | Contraseña del usuario almacenada como hash bcrypt. Nunca se guarda en texto plano. |
| rol | ENUM | — | NO | Rol del usuario dentro del sistema. Valores posibles: admin, empleado. |
| created_at | TIMESTAMP | — | NO | Fecha y hora en que se creó el registro del usuario en la base de datos. |

---

### Tabla: `productos`

Almacena el catálogo completo de productos que ofrece La Despensa de Don Juan.

| Columna | Tipo de dato | Límite de caracteres | ¿Es nulo? | Descripción |
|---|---|---|---|---|
| id | INT AUTO_INCREMENT | — | NO | Identificador único del producto. Clave primaria. |
| nombre | VARCHAR | 150 | NO | Nombre descriptivo del producto tal como aparece en el catálogo. |
| categoria | ENUM | — | NO | Categoría del producto. Valores: lacteos, carnes, bebidas, panaderia, limpieza, frutas_verduras, otros. |
| precio | DECIMAL(8,2) | — | NO | Precio unitario del producto en dólares estadounidenses (USD). |
| stock | INT | — | NO | Cantidad de unidades disponibles actualmente en el inventario de la tienda. |
| descripcion | TEXT | 65,535 bytes | SÍ | Descripción detallada y opcional del producto. Acepta NULL cuando no se proporciona información adicional. |
| disponible | TINYINT(1) | — | NO | Indica si el producto está activo para la venta. 1 = disponible, 0 = no disponible. |
| created_at | TIMESTAMP | — | NO | Fecha y hora en que se registró el producto en el sistema. |

---

### Tabla: `pedidos`

Registra cada uno de los pedidos realizados por los clientes de la tienda.

| Columna | Tipo de dato | Límite de caracteres | ¿Es nulo? | Descripción |
|---|---|---|---|---|
| id | INT AUTO_INCREMENT | — | NO | Identificador único del pedido. Clave primaria. |
| cliente_nombre | VARCHAR | 150 | NO | Nombre completo del cliente que realizó el pedido. |
| cliente_email | VARCHAR | 150 | NO | Correo electrónico de contacto del cliente. |
| cliente_telefono | VARCHAR | 20 | NO | Número de teléfono del cliente en formato ####-####. |
| metodo_pago | ENUM | — | NO | Método de pago seleccionado por el cliente. Valores: efectivo, tarjeta, transferencia. |
| requiere_factura | TINYINT(1) | — | NO | Indica si el cliente necesita factura fiscal. 1 = sí requiere, 0 = no requiere. |
| nit | VARCHAR | 20 | SÍ | Número de Identificación Tributaria del cliente. Acepta NULL cuando requiere_factura = 0. |
| total | DECIMAL(10,2) | — | NO | Monto total en dólares (USD) correspondiente al pedido realizado. |
| estado | ENUM | — | NO | Estado actual del pedido. Valores: pendiente, procesando, entregado, cancelado. |
| notas | TEXT | 65,535 bytes | SÍ | Instrucciones adicionales o comentarios del cliente sobre el pedido. Acepta NULL si no hay observaciones. |
| created_at | TIMESTAMP | — | NO | Fecha y hora exacta en que se registró el pedido en el sistema. |

---


