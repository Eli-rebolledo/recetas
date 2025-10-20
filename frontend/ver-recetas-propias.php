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

// Procesar eliminar receta
if (isset($_POST['eliminar_receta'])) {
    $receta_id = intval($_POST['eliminar_receta']);
    
    // Verificar que la receta pertenece al usuario
    $stmt = $conexion->prepare("SELECT id FROM recetas WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $receta_id, $usuario_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        // Eliminar la receta
        $stmt = $conexion->prepare("DELETE FROM recetas WHERE id = ?");
        $stmt->bind_param("i", $receta_id);
        $stmt->execute();
    }
    $stmt->close();
    
    header("Location: ver-recetas-propias.php");
    exit;
}

// Obtener recetas del usuario
$recetas = $conexion->query("
    SELECT r.*, c.nombre as categoria_nombre 
    FROM recetas r 
    LEFT JOIN categorias c ON r.categoria_id = c.id 
    WHERE r.usuario_id = $usuario_id 
    ORDER BY r.fecha_creacion DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mis Recetas - RecepApp</title>
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
          <div class="profile-dropdown">
            <a href="<?php echo url('ver-recetas-propias.php'); ?>" class="active"><i class="fas fa-book"></i> Mis Recetas</a>
            <a href="<?php echo url('ver-favoritos.php'); ?>"><i class="fas fa-heart"></i> Favoritos</a>
            <a href="<?php echo url('../backend/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?php echo url('iniciar-sesion.php'); ?>">Iniciar Sesión</a>
        <a href="<?php echo url('registrarse.php'); ?>">Registrarse</a>
      <?php endif; ?>
    </nav>
  </header>

  <!-- === HEADER DE PÁGINA === -->
  <section class="page-header">
    <h2><i class="fas fa-book"></i> Mis Recetas</h2>
    <p>Gestiona todas las recetas que has creado</p>
    <a href="<?php echo url('agregar-receta.php'); ?>" class="btn-primary">
      <i class="fas fa-plus"></i> Agregar Nueva Receta
    </a>
  </section>

  <!-- === LISTA DE RECETAS PROPIAS === -->
  <section class="recetas-lista">
    <?php if ($recetas->num_rows > 0): ?>
      <div class="recetas-grid">
        <?php while($receta = $recetas->fetch_assoc()): ?>
          <div class="card">
            <img src="<?php echo asset('img/' . ($receta['imagen'] ?: 'placeholder.jpg')); ?>" 
                 alt="<?php echo htmlspecialchars($receta['titulo']); ?>"
                 onerror="this.src='<?php echo asset('img/placeholder.jpg'); ?>'">
            
            <div class="card-content">
              <span class="categoria-badge"><?php echo htmlspecialchars($receta['categoria_nombre']); ?></span>
              
              <h4><?php echo htmlspecialchars($receta['titulo']); ?></h4>
              
              <p class="descripcion"><?php echo substr(htmlspecialchars($receta['descripcion']), 0, 100); ?>...</p>
              
              <div class="card-meta">
                <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($receta['fecha_creacion'])); ?></span>
                <span><i class="fas fa-eye"></i> <?php echo rand(10, 100); ?> vistas</span>
              </div>
              
              <div class="card-actions">
                <a href="<?php echo url('ver-receta.php?id=' . $receta['id']); ?>" class="btn-ver">
                  <i class="fas fa-eye"></i> Ver
                </a>
                
                <a href="<?php echo url('editar-receta.php?id=' . $receta['id']); ?>" class="btn-editar">
                  <i class="fas fa-edit"></i> Editar
                </a>
                
                <form method="POST" class="eliminar-receta-form">
                  <button type="submit" 
                          name="eliminar_receta" 
                          value="<?php echo $receta['id']; ?>" 
                          class="btn-eliminar"
                          onclick="return confirm('¿Estás seguro de eliminar esta receta? Esta acción no se puede deshacer.')">
                    <i class="fas fa-trash"></i> Eliminar
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <!-- Mensaje cuando no hay recetas -->
      <div class="no-resultados">
        <i class="fas fa-book" style="font-size: 4rem; color: #f1cadd; margin-bottom: 20px;"></i>
        <h3>No has creado ninguna receta aún</h3>
        <p>Comparte tus recetas favoritas con la comunidad de RecepApp.</p>
        <a href="<?php echo url('agregar-receta.php'); ?>" class="btn-primary">
          <i class="fas fa-plus"></i> Crear Mi Primera Receta
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