<?php
// =====================================================
// index.php - Página pública con vista de productos y login
// =====================================================
require_once 'config/db.php';
require_once 'config/session.php';

// Si ya está autenticado, ir al dashboard
if (estaAutenticado()) {
    header('Location: dashboard.php');
    exit;
}

$loginError  = '';
$msgSistema  = '';

if (isset($_SESSION['login_error'])) {
    $loginError = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

$mensajes = [
    'acceso_denegado' => '⚠️ Debe iniciar sesión para acceder a esa sección.',
    'sesion_cerrada'  => '✔ Sesión cerrada correctamente.',
];
if (isset($_GET['msg']) && array_key_exists($_GET['msg'], $mensajes)) {
    $msgSistema = $mensajes[$_GET['msg']];
}

// Obtener productos para la vista pública, ordenados por categoría
$productos = [];
try {
    $pdo  = conectarBD();
    $stmt = $pdo->query(
        "SELECT nombre, categoria, precio, stock, descripcion, disponible
         FROM productos
         WHERE disponible = 1
         ORDER BY categoria ASC, nombre ASC"
    );
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>La Despensa de Don Juan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<style>
  :root {
    --verde:    #1a6b2f;
    --verde-cl: #2e9447;
    --amarillo: #f5c518;
    --rojo:     #c0392b;
    --crema:    #fdf6e3;
    --gris:     #4a4a4a;
    --borde:    #ddd;
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Lato',sans-serif; background:#f0f4f0; color:var(--gris); }

  /* NAV */
  nav {
    background:var(--verde);
    display:flex; align-items:center; justify-content:space-between;
    padding:0 2rem; height:64px;
  }
  .nav-logo { font-family:'Playfair Display',serif; color:#fff; font-size:1.5rem; letter-spacing:.5px; }
  .nav-logo span { color:var(--amarillo); }
  .nav-btn {
    background:var(--amarillo); color:var(--verde); font-weight:700;
    border:none; padding:.5rem 1.4rem; border-radius:4px; cursor:pointer;
    font-size:.9rem; text-transform:uppercase; letter-spacing:.5px;
  }
  .nav-btn:hover { background:#e0b00f; }

  /* HERO */
  .hero {
    background:linear-gradient(135deg, var(--verde) 0%, var(--verde-cl) 100%);
    color:#fff; text-align:center; padding:3.5rem 1rem 2.5rem;
  }
  .hero h1 { font-family:'Playfair Display',serif; font-size:2.6rem; line-height:1.2; }
  .hero h1 span { color:var(--amarillo); }
  .hero p  { margin-top:.7rem; font-size:1.1rem; opacity:.9; }

  /* MENSAJE SISTEMA */
  .msg-sys {
    background:#e8f5e9; border-left:4px solid var(--verde-cl);
    padding:.9rem 1.5rem; margin:1rem 2rem; border-radius:4px;
    font-weight:700; color:var(--verde);
  }

  /* CONTENIDO PRINCIPAL */
  main { max-width:1100px; margin:2rem auto; padding:0 1rem; }
  h2 { font-family:'Playfair Display',serif; color:var(--verde); margin-bottom:1rem; font-size:1.6rem; }

  /* TABLA */
  .tabla-wrap { overflow-x:auto; }
  table {
    width:100%; border-collapse:collapse; background:#fff;
    border-radius:8px; overflow:hidden;
    box-shadow:0 2px 12px rgba(0,0,0,.08);
  }
  thead { background:var(--verde); color:#fff; }
  thead th { padding:.8rem 1rem; text-align:left; font-size:.85rem; text-transform:uppercase; letter-spacing:.5px; }
  tbody tr:nth-child(even) { background:#f9fbf9; }
  tbody td { padding:.75rem 1rem; border-bottom:1px solid var(--borde); font-size:.95rem; }
  tbody tr:hover { background:#e8f5e9; }
  .badge {
    display:inline-block; padding:.25rem .65rem; border-radius:20px;
    font-size:.78rem; font-weight:700; text-transform:capitalize;
  }
  .badge-lacteos      { background:#e3f2fd; color:#1565c0; }
  .badge-carnes       { background:#fce4ec; color:#c62828; }
  .badge-bebidas      { background:#e8eaf6; color:#283593; }
  .badge-panaderia    { background:#fff8e1; color:#f57f17; }
  .badge-limpieza     { background:#f3e5f5; color:#6a1b9a; }
  .badge-frutas_verduras { background:#e8f5e9; color:#2e7d32; }
  .badge-otros        { background:#eceff1; color:#37474f; }
  .disponible-si { color:var(--verde-cl); font-weight:700; }
  .disponible-no { color:var(--rojo);     font-weight:700; }

  /* MODAL LOGIN */
  .overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:100; align-items:center; justify-content:center; }
  .overlay.activo { display:flex; }
  .modal {
    background:#fff; border-radius:10px; padding:2rem 2.5rem;
    width:100%; max-width:420px; position:relative;
    box-shadow:0 20px 60px rgba(0,0,0,.25);
  }
  .modal h3 { font-family:'Playfair Display',serif; color:var(--verde); font-size:1.5rem; margin-bottom:1.4rem; }
  .form-group { margin-bottom:1.1rem; }
  .form-group label { display:block; font-weight:700; font-size:.85rem; margin-bottom:.35rem; color:var(--gris); }
  .form-group input {
    width:100%; padding:.65rem .9rem; border:2px solid var(--borde);
    border-radius:6px; font-size:1rem; font-family:'Lato',sans-serif;
    transition:border .2s;
  }
  .form-group input:focus { outline:none; border-color:var(--verde-cl); }
  .btn-login {
    width:100%; background:var(--verde); color:#fff; border:none;
    padding:.8rem; border-radius:6px; font-size:1rem; font-weight:700;
    cursor:pointer; letter-spacing:.5px; transition:background .2s;
  }
  .btn-login:hover { background:var(--verde-cl); }
  .error-msg { background:#fdecea; border-left:4px solid var(--rojo); padding:.7rem 1rem; border-radius:4px; margin-bottom:1rem; color:var(--rojo); font-weight:700; font-size:.9rem; }
  .hint { font-size:.8rem; color:#888; text-align:center; margin-top:.8rem; }
  .cerrar-modal { position:absolute; top:.8rem; right:1rem; background:none; border:none; font-size:1.4rem; cursor:pointer; color:#999; }
  .cerrar-modal:hover { color:var(--gris); }

  footer { text-align:center; padding:2rem; font-size:.85rem; color:#888; margin-top:2rem; }
</style>
</head>
<body>

<nav>
  <div class="nav-logo">🛒 La Despensa de <span>Don Juan</span></div>
  <button class="nav-btn" onclick="abrirModal()">Iniciar sesión</button>
</nav>

<div class="hero">
  <h1>Tienda en Línea<br><span>La Despensa de Don Juan</span></h1>
  <p>Calidad y frescura para la familia salvadoreña · San Miguel, El Salvador</p>
</div>

<?php if ($msgSistema): ?>
<div class="msg-sys"><?= htmlspecialchars($msgSistema) ?></div>
<?php endif; ?>

<main>
  <h2>📦 Catálogo de Productos Disponibles</h2>
  <div class="tabla-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Producto</th>
          <th>Categoría</th>
          <th>Precio</th>
          <th>Stock</th>
          <th>Descripción</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($productos)): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:#999">No hay productos disponibles.</td></tr>
        <?php else: ?>
          <?php foreach ($productos as $i => $p): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
            <td><span class="badge badge-<?= $p['categoria'] ?>"><?= str_replace('_', ' ', $p['categoria']) ?></span></td>
            <td>$<?= number_format($p['precio'], 2) ?></td>
            <td><?= (int)$p['stock'] ?> uds.</td>
            <td><?= $p['descripcion'] ? htmlspecialchars($p['descripcion']) : '<em style="color:#bbb">—</em>' ?></td>
            <td class="<?= $p['disponible'] ? 'disponible-si' : 'disponible-no' ?>">
              <?= $p['disponible'] ? '✔ Disponible' : '✘ Agotado' ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- MODAL LOGIN -->
<div class="overlay" id="modalOverlay" onclick="cerrarModalFondo(event)">
  <div class="modal">
    <button class="cerrar-modal" onclick="cerrarModal()">✕</button>
    <h3>🔐 Acceso al Sistema</h3>
    <?php if ($loginError): ?>
      <div class="error-msg">⚠ <?= htmlspecialchars($loginError) ?></div>
    <?php endif; ?>
    <form action="auth/login.php" method="POST">
      <div class="form-group">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" required placeholder="usuario@despensa.com" autocomplete="email">
      </div>
      <div class="form-group">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="current-password">
      </div>
      <button type="submit" class="btn-login">Ingresar al sistema</button>
    </form>
    <p class="hint">Solo personal autorizado · admin@despensa.com / password</p>
  </div>
</div>

<footer>© <?= date('Y') ?> La Despensa de Don Juan · San Miguel, El Salvador</footer>

<script>
  function abrirModal()  { document.getElementById('modalOverlay').classList.add('activo'); }
  function cerrarModal() { document.getElementById('modalOverlay').classList.remove('activo'); }
  function cerrarModalFondo(e) { if (e.target.id === 'modalOverlay') cerrarModal(); }
  // Si hay error, abrir modal automáticamente
  <?php if ($loginError): ?> abrirModal(); <?php endif; ?>
</script>
</body>
</html>
