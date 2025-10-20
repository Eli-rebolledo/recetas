<?php
require_once __DIR__ . '/../backend/session.php';
require_once __DIR__ . '/../backend/conexion.php';
require_once 'helpers.php';

// Verificar que el usuario esté loggeado
if (!usuarioEstaLogeado()) {
    header('Location: iniciar-sesion.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Procesar eliminar de favoritos
if (isset($_POST['eliminar_favorito'])) {
    $receta_id = intval($_POST['eliminar_favorito']);
    
    $stmt = $conexion->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND receta_id = ?");
    $stmt->bind_param("ii", $usuario_id, $receta_id);
    $stmt->execute();
    $stmt->close();
    
    // Redirigir para evitar reenvío del formulario
    header("Location: ver-favoritos.php");
    exit;
}

// Obtener recetas favoritas del usuario
$favoritos = $conexion->query("
    SELECT r.*, c.nombre as categoria_nombre, u.nombre as usuario_nombre 
    FROM recetas r 
    LEFT JOIN categorias c ON r.categoria_id = c.id 
    LEFT JOIN usuarios u ON r.usuario_id = u.id 
    INNER JOIN favoritos f ON r.id = f.receta_id 
    WHERE f.usuario_id = $usuario_id 
    ORDER BY f.fecha DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mis Favoritos - RecepApp</title>
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

  <!-- === HEADER DE PÁGINA === -->
  <section class="page-header">
    <h2><i class="fas fa-heart"></i> Mis Recetas Favoritas</h2>
    <p>Aquí encontrarás todas las recetas que has guardado como favoritas</p>
  </section>

  <!-- === LISTA DE FAVORITOS === -->
  <section class="recetas-lista">
    <?php if ($favoritos->num_rows > 0): ?>
      <div class="recetas-grid">
        <?php while($receta = $favoritos->fetch_assoc()): ?>
          <div class="card">
            <img src="<?php echo asset('img/' . ($receta['imagen'] ?: 'placeholder.jpg')); ?>" 
                 alt="<?php echo htmlspecialchars($receta['titulo']); ?>"
                 onerror="this.src='<?php echo asset('img/placeholder.jpg'); ?>'">
            
            <div class="card-content">
              <span class="categoria-badge"><?php echo htmlspecialchars($receta['categoria_nombre']); ?></span>
              
              <h4><?php echo htmlspecialchars($receta['titulo']); ?></h4>
              
              <p class="descripcion"><?php echo substr(htmlspecialchars($receta['descripcion']), 0, 100); ?>...</p>
              
              <div class="card-meta">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($receta['usuario_nombre']); ?></span>
                <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($receta['fecha_creacion'])); ?></span>
              </div>
              
              <div class="card-actions">
                <a href="<?php echo url('ver-receta.php?id=' . $receta['id']); ?>" class="btn-ver">
                  <i class="fas fa-eye"></i> Ver Receta
                </a>
                
                <form method="POST" class="eliminar-favorito-form">
                  <button type="submit" 
                          name="eliminar_favorito" 
                          value="<?php echo $receta['id']; ?>" 
                          class="btn-eliminar"
                          onclick="return confirm('¿Quitar de favoritos?')">
                    <i class="fas fa-heart-broken"></i> Quitar
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <!-- Mensaje cuando no hay favoritos -->
      <div class="no-resultados">
        <i class="fas fa-heart" style="font-size: 4rem; color: #f1cadd; margin-bottom: 20px;"></i>
        <h3>No tienes recetas favoritas aún</h3>
        <p>Descubre recetas deliciosas y guárdalas como favoritas para encontrarlas fácilmente después.</p>
        <a href="<?php echo url('ver-mas-recetas.php'); ?>" class="btn-primary">
          <i class="fas fa-search"></i> Explorar Recetas
        </a>
      </div>
    <?php endif; ?>
  </section>

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