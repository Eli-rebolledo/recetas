<?php
require_once __DIR__ . '/../backend/session.php';
require_once __DIR__ . '/../backend/conexion.php';
require_once 'helpers.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$receta_id = intval($_GET['id']);

// Obtener información de la receta
$stmt = $conexion->prepare("SELECT r.*, c.nombre as categoria_nombre, u.nombre as usuario_nombre 
                           FROM recetas r 
                           LEFT JOIN categorias c ON r.categoria_id = c.id 
                           LEFT JOIN usuarios u ON r.usuario_id = u.id 
                           WHERE r.id = ?");
$stmt->bind_param("i", $receta_id);
$stmt->execute();
$receta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$receta) {
    header('Location: index.php');
    exit;
}

// Verificar si la receta está en favoritos del usuario actual
$es_favorito = false;
if (usuarioEstaLogeado()) {
    $usuario_id = $_SESSION['usuario_id'];
    $stmt = $conexion->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND receta_id = ?");
    $stmt->bind_param("ii", $usuario_id, $receta_id);
    $stmt->execute();
    $es_favorito = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

// Procesar agregar/eliminar de favoritos
if (isset($_POST['accion_favorito']) && usuarioEstaLogeado()) {
    if ($_POST['accion_favorito'] === 'agregar') {
        $stmt = $conexion->prepare("INSERT INTO favoritos (usuario_id, receta_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $usuario_id, $receta_id);
        $stmt->execute();
        $stmt->close();
        $es_favorito = true;
    } elseif ($_POST['accion_favorito'] === 'eliminar') {
        $stmt = $conexion->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND receta_id = ?");
        $stmt->bind_param("ii", $usuario_id, $receta_id);
        $stmt->execute();
        $stmt->close();
        $es_favorito = false;
    }
    header("Location: ver-receta.php?id=$receta_id");
    exit;
}

// Procesar comentarios
if (isset($_POST['comentario']) && usuarioEstaLogeado()) {
    $comentario = trim($_POST['comentario']);
    if (!empty($comentario)) {
        $stmt = $conexion->prepare("INSERT INTO comentarios (usuario_id, receta_id, comentario) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $usuario_id, $receta_id, $comentario);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: ver-receta.php?id=$receta_id");
    exit;
}

// Procesar eliminar comentario
if (isset($_POST['eliminar_comentario']) && usuarioEstaLogeado()) {
    $comentario_id = intval($_POST['eliminar_comentario']);
    $usuario_id = $_SESSION['usuario_id'];
    
    // Verificar que el comentario pertenece al usuario
    $stmt = $conexion->prepare("SELECT id FROM comentarios WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $comentario_id, $usuario_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt = $conexion->prepare("DELETE FROM comentarios WHERE id = ?");
        $stmt->bind_param("i", $comentario_id);
        $stmt->execute();
    }
    $stmt->close();
    header("Location: ver-receta.php?id=$receta_id");
    exit;
}

// Obtener comentarios
$comentarios = $conexion->query("SELECT c.*, u.nombre as usuario_nombre 
                                FROM comentarios c 
                                LEFT JOIN usuarios u ON c.usuario_id = u.id 
                                WHERE c.receta_id = $receta_id 
                                ORDER BY c.fecha DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($receta['titulo']); ?> - RecepApp</title>
  <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <script src="https://kit.fontawesome.com/a2d9d6cfd5.js" crossorigin="anonymous"></script>
</head>
<body>
<!-- === NAVBAR === -->
<header class="navbar">
  <div class="logo">
    <i class="fas fa-utensils"></i>
    <h1>RecepApp</h1>
  </div>
  <nav class="menu">
    <a href="<?php echo url('index.php'); ?>">Inicio</a>
    <a href="<?php echo url('ver-mas-recetas.php'); ?>">Recetas</a>
    <?php if (usuarioEstaLogeado()): ?>
      <span class="user">Hola, <?php echo htmlspecialchars(obtenerUsuarioNombre()); ?></span>
      <div class="profile-menu">
        <button class="profile-btn"><i class="fas fa-user"></i> Mi Perfil</button>
        <!-- AQUÍ VA EL DROPDOWN -->
        <div class="profile-dropdown">
          <a href="<?php echo url('ver-recetas-propias.php'); ?>"><i class="fas fa-book"></i> Mis Recetas</a>
          <a href="<?php echo url('ver-favoritos.php'); ?>"><i class="fas fa-heart"></i> Favoritos</a>
          <a href="<?php echo url('../backend/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
        <!-- FIN DEL DROPDOWN -->
      </div>
    <?php else: ?>
      <a href="<?php echo url('iniciar-sesion.php'); ?>">Iniciar Sesión</a>
      <a href="<?php echo url('registrarse.php'); ?>">Registrarse</a>
    <?php endif; ?>
  </nav>
</header>

  <!-- === DETALLE DE RECETA === -->
  <main class="receta-detalle">
    <div class="receta-header">
      <div class="receta-imagen">
        <img src="<?php echo asset('img/' . ($receta['imagen'] ?: 'placeholder.jpg')); ?>" 
             alt="<?php echo htmlspecialchars($receta['titulo']); ?>"
             onerror="this.src='<?php echo asset('img/placeholder.jpg'); ?>'">
      </div>
      
      <div class="receta-info">
        <h1><?php echo htmlspecialchars($receta['titulo']); ?></h1>
        
        <div class="receta-meta">
          <span><i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($receta['categoria_nombre']); ?></span>
          <span><i class="fas fa-user"></i> Por <?php echo htmlspecialchars($receta['usuario_nombre']); ?></span>
          <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($receta['fecha_creacion'])); ?></span>
        </div>
        
        <div class="receta-desc">
          <?php echo nl2br(htmlspecialchars($receta['descripcion'])); ?>
        </div>
        
        <!-- Botón de favoritos -->
        <?php if (usuarioEstaLogeado()): ?>
          <form method="POST" class="favorito-form">
            <?php if ($es_favorito): ?>
              <button type="submit" name="accion_favorito" value="eliminar" class="btn-favorito active">
                <i class="fas fa-heart"></i> Quitar de Favoritos
              </button>
            <?php else: ?>
              <button type="submit" name="accion_favorito" value="agregar" class="btn-favorito">
                <i class="far fa-heart"></i> Agregar a Favoritos
              </button>
            <?php endif; ?>
          </form>
        <?php else: ?>
          <div class="login-prompt">
            <a href="<?php echo url('iniciar-sesion.php'); ?>">Inicia sesión</a> para agregar a favoritos
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="receta-content">
      <!-- Ingredientes -->
      <div class="ingredientes">
        <h3><i class="fas fa-shopping-basket"></i> Ingredientes</h3>
        <div class="ingredientes-lista">
          <?php
          $ingredientes = explode("\n", $receta['ingredientes']);
          foreach ($ingredientes as $ingrediente):
            if (trim($ingrediente) !== ''):
          ?>
          <div class="ingrediente-item">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars(trim($ingrediente)); ?></span>
          </div>
          <?php
            endif;
          endforeach;
          ?>
        </div>
      </div>

      <!-- Instrucciones -->
      <div class="instrucciones">
        <h3><i class="fas fa-list-ol"></i> Preparación</h3>
        <div class="instrucciones-lista">
          <?php
          $instrucciones = explode("\n", $receta['instrucciones']);
          $paso_num = 1;
          foreach ($instrucciones as $instruccion):
            if (trim($instruccion) !== ''):
          ?>
          <div class="instruccion-item">
            <div class="paso-num"><?php echo $paso_num; ?></div>
            <div class="paso-texto"><?php echo htmlspecialchars(trim($instruccion)); ?></div>
          </div>
          <?php
              $paso_num++;
            endif;
          endforeach;
          ?>
        </div>
      </div>
    </div>

    <!-- Comentarios -->
    <section class="comentarios-section">
      <h3><i class="fas fa-comments"></i> Comentarios</h3>
      
      <!-- Formulario para comentar -->
      <?php if (usuarioEstaLogeado()): ?>
        <form method="POST" class="comentario-form">
          <textarea name="comentario" placeholder="Escribe tu comentario..." required></textarea>
          <button type="submit" class="btn-primary">Publicar Comentario</button>
        </form>
      <?php else: ?>
        <div class="login-prompt">
          <a href="<?php echo url('iniciar-sesion.php'); ?>">Inicia sesión</a> para dejar un comentario
        </div>
      <?php endif; ?>

      <!-- Lista de comentarios -->
      <div class="comentarios-lista">
        <?php if ($comentarios->num_rows > 0): ?>
          <?php while($comentario = $comentarios->fetch_assoc()): ?>
            <div class="comentario-item">
              <div class="comentario-header">
                <strong><?php echo htmlspecialchars($comentario['usuario_nombre']); ?></strong>
                <span><?php echo date('d/m/Y H:i', strtotime($comentario['fecha'])); ?></span>
                
                <!-- Botón eliminar (solo para el dueño del comentario) -->
                <?php if (usuarioEstaLogeado() && $_SESSION['usuario_id'] == $comentario['usuario_id']): ?>
                  <form method="POST" class="eliminar-comentario-form" style="display: inline;">
                    <button type="submit" name="eliminar_comentario" value="<?php echo $comentario['id']; ?>" 
                            class="btn-eliminar" onclick="return confirm('¿Eliminar este comentario?')">
                      <i class="fas fa-trash"></i> Eliminar
                    </button>
                  </form>
                <?php endif; ?>
              </div>
              <div class="comentario-texto">
                <?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="no-comentarios">
            <p>No hay comentarios aún. ¡Sé el primero en comentar!</p>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <!-- === FOOTER === -->
  <footer class="footer-simple">
    <p>© 2025 RecepApp — Contacto: <a href="mailto:contacto@recepapp.com">contacto@recepapp.com</a></p>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const profileBtn = document.querySelector('.profile-btn');
      if (profileBtn) {
        profileBtn.addEventListener('click', function() {
          document.querySelector('.profile-dropdown').classList.toggle('show');
        });
      }
      
      window.addEventListener('click', function(e) {
        if (!e.target.matches('.profile-btn')) {
          const dropdown = document.querySelector('.profile-dropdown');
          if (dropdown && dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
          }
        }
      });
    });
  </script>
</body>
</html>