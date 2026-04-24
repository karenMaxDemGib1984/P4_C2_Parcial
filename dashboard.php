<?php
// =====================================================
// dashboard.php - Panel de administración (solo autenticados)
// =====================================================
require_once 'config/db.php';
require_once 'config/session.php';

requiereLogin();
$usuario = obtenerUsuario();

// Leer mensajes de sesión
$prodExito = $prodError = $pedExito = $pedError = '';
if (isset($_SESSION['form_exito']))   { $prodExito = $_SESSION['form_exito'];   unset($_SESSION['form_exito']); }
if (isset($_SESSION['form_error']))   { $prodError = $_SESSION['form_error'];   unset($_SESSION['form_error']); }
if (isset($_SESSION['pedido_exito'])) { $pedExito  = $_SESSION['pedido_exito']; unset($_SESSION['pedido_exito']); }
if (isset($_SESSION['pedido_error'])) { $pedError  = $_SESSION['pedido_error']; unset($_SESSION['pedido_error']); }
$formDatos  = $_SESSION['form_datos']  ?? []; unset($_SESSION['form_datos']);
$pedDatos   = $_SESSION['pedido_datos'] ?? []; unset($_SESSION['pedido_datos']);

// Obtener datos de BD
$productos = $pedidos = [];
try {
    $pdo = conectarBD();
    $productos = $pdo->query("SELECT * FROM productos ORDER BY categoria, nombre")->fetchAll();
    $pedidos   = $pdo->query("SELECT * FROM pedidos ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) { error_log($e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard · La Despensa de Don Juan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<style>
  :root {
    --verde:#1a6b2f; --verde-cl:#2e9447; --amarillo:#f5c518;
    --rojo:#c0392b; --borde:#e0e0e0; --bg:#f0f4f0;
  }
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Lato',sans-serif;background:var(--bg);color:#333;}

  /* SIDEBAR */
  .layout{display:flex;min-height:100vh;}
  .sidebar{width:230px;background:var(--verde);color:#fff;padding:1.5rem 0;flex-shrink:0;position:sticky;top:0;height:100vh;}
  .sidebar-logo{padding:0 1.5rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.15);}
  .sidebar-logo h2{font-family:'Playfair Display',serif;font-size:1.1rem;line-height:1.3;}
  .sidebar-logo span{color:var(--amarillo);}
  .sidebar-user{padding:1rem 1.5rem;font-size:.82rem;opacity:.7;}
  nav a{display:block;padding:.75rem 1.5rem;color:rgba(255,255,255,.85);text-decoration:none;font-weight:700;font-size:.9rem;transition:background .15s;}
  nav a:hover,nav a.activo{background:rgba(255,255,255,.12);color:#fff;}
  .sidebar-bottom{position:absolute;bottom:1.5rem;left:0;width:100%;padding:0 1.5rem;}
  .btn-logout{display:block;text-align:center;background:rgba(255,255,255,.1);color:#fff;text-decoration:none;padding:.6rem;border-radius:6px;font-size:.85rem;transition:background .2s;}
  .btn-logout:hover{background:var(--rojo);}

  /* CONTENIDO */
  .content{flex:1;padding:2rem;overflow-x:auto;}
  .page-title{font-family:'Playfair Display',serif;color:var(--verde);font-size:1.8rem;margin-bottom:1.5rem;}
  .section{background:#fff;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:2rem;overflow:hidden;}
  .section-header{background:var(--verde);color:#fff;padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;}
  .section-header h3{font-family:'Playfair Display',serif;font-size:1.15rem;}
  .section-body{padding:1.5rem;}

  /* ALERTAS */
  .alert{padding:.8rem 1.2rem;border-radius:6px;margin-bottom:1rem;font-weight:700;font-size:.9rem;}
  .alert-ok {background:#e8f5e9;color:#2e7d32;border-left:4px solid #4caf50;}
  .alert-err{background:#fdecea;color:#c62828;border-left:4px solid var(--rojo);}

  /* FORMULARIOS */
  .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;}
  .form-group{display:flex;flex-direction:column;gap:.3rem;}
  .form-group label{font-weight:700;font-size:.82rem;color:#555;text-transform:uppercase;letter-spacing:.4px;}
  .form-group input,.form-group select,.form-group textarea{
    padding:.6rem .9rem;border:2px solid var(--borde);border-radius:6px;
    font-size:.95rem;font-family:'Lato',sans-serif;transition:border .2s;
  }
  .form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--verde-cl);}
  .form-group textarea{resize:vertical;min-height:70px;}
  .form-full{grid-column:1/-1;}
  .radio-group{display:flex;gap:1.5rem;padding:.5rem 0;flex-wrap:wrap;}
  .radio-group label{display:flex;align-items:center;gap:.5rem;font-weight:400;font-size:.95rem;cursor:pointer;}
  .checkbox-label{display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:.95rem;}
  .btn-submit{background:var(--verde);color:#fff;border:none;padding:.75rem 2rem;border-radius:6px;font-size:1rem;font-weight:700;cursor:pointer;transition:background .2s;margin-top:.5rem;}
  .btn-submit:hover{background:var(--verde-cl);}

  /* TABLAS */
  .tabla-wrap{overflow-x:auto;}
  table{width:100%;border-collapse:collapse;font-size:.9rem;}
  thead{background:var(--bg);}
  th{padding:.7rem 1rem;text-align:left;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px;color:#666;border-bottom:2px solid var(--borde);}
  td{padding:.7rem 1rem;border-bottom:1px solid var(--borde);}
  tr:hover td{background:#f9fbf9;}
  .badge{display:inline-block;padding:.22rem .6rem;border-radius:20px;font-size:.75rem;font-weight:700;}
  .badge-pendiente  {background:#fff8e1;color:#f57f17;}
  .badge-procesando {background:#e8eaf6;color:#283593;}
  .badge-entregado  {background:#e8f5e9;color:#2e7d32;}
  .badge-cancelado  {background:#fce4ec;color:#c62828;}

  @media(max-width:768px){.sidebar{display:none;}.form-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="layout">

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <h2>🛒 La Despensa<br>de <span>Don Juan</span></h2>
  </div>
  <div class="sidebar-user">👤 <?= htmlspecialchars($usuario['nombre']) ?></div>
  <nav>
    <a href="#productos" class="activo">📦 Productos</a>
    <a href="#pedidos">📋 Pedidos</a>
    <a href="index.php">🌐 Vista pública</a>
  </nav>
  <div class="sidebar-bottom">
    <a href="auth/logout.php" class="btn-logout">🚪 Cerrar sesión</a>
  </div>
</aside>

<!-- CONTENIDO -->
<div class="content">
  <h1 class="page-title">Panel de Administración</h1>

  <!-- ========== SECCIÓN PRODUCTOS ========== -->
  <div class="section" id="productos">
    <div class="section-header">
      <h3>📦 Registrar Nuevo Producto</h3>
    </div>
    <div class="section-body">
      <?php if ($prodExito): ?><div class="alert alert-ok">✔ <?= $prodExito ?></div><?php endif; ?>
      <?php if ($prodError): ?><div class="alert alert-err">⚠ <?= $prodError ?></div><?php endif; ?>

      <form action="actions/guardar_producto.php" method="POST">
        <div class="form-grid">
          <div class="form-group">
            <label>Nombre del producto *</label>
            <input type="text" name="nombre" maxlength="150" required
                   value="<?= htmlspecialchars($formDatos['nombre'] ?? '') ?>">
          </div>

          <!-- SELECT - tipo requerido por la tarea -->
          <div class="form-group">
            <label>Categoría *</label>
            <select name="categoria" required>
              <option value="">— Seleccione —</option>
              <?php
              $cats = ['lacteos'=>'🥛 Lácteos','carnes'=>'🥩 Carnes','bebidas'=>'🥤 Bebidas',
                       'panaderia'=>'🍞 Panadería','limpieza'=>'🧴 Limpieza',
                       'frutas_verduras'=>'🥦 Frutas y Verduras','otros'=>'📦 Otros'];
              $selCat = $formDatos['categoria'] ?? '';
              foreach ($cats as $val => $lbl):
              ?>
              <option value="<?= $val ?>" <?= $selCat === $val ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Precio ($) *</label>
            <input type="number" name="precio" step="0.01" min="0.01" required
                   value="<?= htmlspecialchars($formDatos['precio'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label>Stock (unidades) *</label>
            <input type="number" name="stock" min="0" required
                   value="<?= htmlspecialchars($formDatos['stock'] ?? '0') ?>">
          </div>

          <div class="form-group form-full">
            <label>Descripción <em style="font-weight:300">(opcional)</em></label>
            <textarea name="descripcion" maxlength="500"><?= htmlspecialchars($formDatos['descripcion'] ?? '') ?></textarea>
          </div>

          <!-- CHECKBOX - tipo requerido por la tarea -->
          <div class="form-group">
            <label>Disponibilidad</label>
            <label class="checkbox-label">
              <input type="checkbox" name="disponible" value="1"
                     <?= isset($formDatos['disponible']) || !isset($formDatos['nombre']) ? 'checked' : '' ?>>
              Marcar como disponible
            </label>
          </div>
        </div>
        <button type="submit" class="btn-submit">💾 Guardar Producto</button>
      </form>
    </div>
  </div>

  <!-- Tabla de productos -->
  <div class="section">
    <div class="section-header"><h3>📋 Listado de Productos</h3></div>
    <div class="section-body">
      <div class="tabla-wrap">
        <table>
          <thead>
            <tr><th>#</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Disponible</th><th>Descripción</th></tr>
          </thead>
          <tbody>
            <?php foreach ($productos as $i => $p): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
              <td><?= str_replace('_',' ',$p['categoria']) ?></td>
              <td>$<?= number_format($p['precio'],2) ?></td>
              <td><?= $p['stock'] ?></td>
              <td><?= $p['disponible'] ? '✔' : '✘' ?></td>
              <td><?= $p['descripcion'] ? htmlspecialchars($p['descripcion']) : '<em style="color:#bbb">NULL</em>' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ========== SECCIÓN PEDIDOS ========== -->
  <div class="section" id="pedidos">
    <div class="section-header"><h3>📋 Registrar Nuevo Pedido</h3></div>
    <div class="section-body">
      <?php if ($pedExito): ?><div class="alert alert-ok">✔ <?= $pedExito ?></div><?php endif; ?>
      <?php if ($pedError): ?><div class="alert alert-err">⚠ <?= $pedError ?></div><?php endif; ?>

      <form action="actions/guardar_pedido.php" method="POST">
        <div class="form-grid">
          <div class="form-group">
            <label>Nombre del cliente *</label>
            <input type="text" name="cliente_nombre" maxlength="150" required
                   value="<?= htmlspecialchars($pedDatos['cliente_nombre'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Correo del cliente *</label>
            <input type="email" name="cliente_email" maxlength="150" required
                   value="<?= htmlspecialchars($pedDatos['cliente_email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Teléfono (####-####) *</label>
            <input type="text" name="cliente_telefono" pattern="\d{4}-\d{4}"
                   placeholder="7654-3210" maxlength="9" required
                   value="<?= htmlspecialchars($pedDatos['cliente_telefono'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Total ($) *</label>
            <input type="number" name="total" step="0.01" min="0.01" required
                   value="<?= htmlspecialchars($pedDatos['total'] ?? '') ?>">
          </div>

          <!-- RADIO BUTTONS - tipo requerido por la tarea -->
          <div class="form-group">
            <label>Método de pago *</label>
            <div class="radio-group">
              <?php foreach (['efectivo'=>'💵 Efectivo','tarjeta'=>'💳 Tarjeta','transferencia'=>'🏦 Transferencia'] as $val=>$lbl): ?>
              <label>
                <input type="radio" name="metodo_pago" value="<?= $val ?>"
                       <?= ($pedDatos['metodo_pago'] ?? 'efectivo') === $val ? 'checked' : '' ?>> <?= $lbl ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- SELECT estado -->
          <div class="form-group">
            <label>Estado del pedido</label>
            <select name="estado">
              <?php foreach (['pendiente','procesando','entregado','cancelado'] as $est): ?>
              <option value="<?= $est ?>" <?= ($pedDatos['estado'] ?? 'pendiente') === $est ? 'selected' : '' ?>>
                <?= ucfirst($est) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group form-full">
            <label class="checkbox-label">
              <input type="checkbox" name="requiere_factura" value="1" id="chkFac"
                     <?= isset($pedDatos['requiere_factura']) ? 'checked' : '' ?>
                     onchange="toggleNIT(this)">
              ¿Requiere factura?
            </label>
          </div>

          <div class="form-group" id="nitGroup" style="<?= isset($pedDatos['requiere_factura']) ? '' : 'display:none' ?>">
            <label>NIT del cliente</label>
            <input type="text" name="nit" maxlength="20" placeholder="0614-XXXXXX-XXX-X"
                   value="<?= htmlspecialchars($pedDatos['nit'] ?? '') ?>">
          </div>

          <div class="form-group form-full">
            <label>Notas adicionales <em style="font-weight:300">(opcional)</em></label>
            <textarea name="notas"><?= htmlspecialchars($pedDatos['notas'] ?? '') ?></textarea>
          </div>
        </div>
        <button type="submit" class="btn-submit">💾 Registrar Pedido</button>
      </form>
    </div>
  </div>

  <!-- Tabla de pedidos -->
  <div class="section">
    <div class="section-header"><h3>📊 Listado de Pedidos</h3></div>
    <div class="section-body">
      <div class="tabla-wrap">
        <table>
          <thead>
            <tr><th>#</th><th>Cliente</th><th>Correo</th><th>Teléfono</th><th>Pago</th><th>Total</th><th>Estado</th><th>Factura</th><th>NIT</th><th>Fecha</th></tr>
          </thead>
          <tbody>
            <?php foreach ($pedidos as $i => $p): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><strong><?= htmlspecialchars($p['cliente_nombre']) ?></strong></td>
              <td><?= htmlspecialchars($p['cliente_email']) ?></td>
              <td><?= htmlspecialchars($p['cliente_telefono']) ?></td>
              <td><?= ucfirst($p['metodo_pago']) ?></td>
              <td>$<?= number_format($p['total'],2) ?></td>
              <td><span class="badge badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
              <td><?= $p['requiere_factura'] ? 'Sí' : 'No' ?></td>
              <td><?= $p['nit'] ?? '<em style="color:#bbb">NULL</em>' ?></td>
              <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div><!-- /content -->
</div><!-- /layout -->

<script>
function toggleNIT(cb){
  document.getElementById('nitGroup').style.display = cb.checked ? '' : 'none';
}
</script>
</body>
</html>
