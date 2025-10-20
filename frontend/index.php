<?php
require_once __DIR__ . '/../backend/session.php';
require_once __DIR__ . '/../backend/conexion.php';
require_once 'helpers.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cocina Fácil</title>
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

  <!-- === HERO === -->
  <section class="hero">
    <h2>Descubre tu próxima receta favorita 🍰</h2>
    <p>Busca entre cientos de recetas fáciles y deliciosas. Filtra por categoría o guarda tus favoritas.</p>
    <form action="<?php echo url('ver-mas-recetas.php'); ?>" method="GET" class="search-container">
      <input type="text" name="busqueda" placeholder="Buscar recetas..." />
      <button type="submit"><i class="fas fa-search"></i> Buscar</button>
    </form>
  </section>

  <!-- === CATEGORÍAS === -->
  <section class="categorias">
    <h3>Categorías Populares</h3>
    <div class="cat-grid">
      <?php
      $categorias = $conexion->query("SELECT * FROM categorias LIMIT 4");
      while($categoria = $categorias->fetch_assoc()):
        // Asignar imágenes específicas por categoría
        $imagenes_categorias = [
          'Postres' => 'pastel de chocolate.jpg',
          'Ensaladas' => 'ensalada fresca.jpg',
          'Sopas' => 'sopa de verduras.jpg',
          'Platos Principales' => 'pollo al horno.jpg',
          'Desayunos' => 'brownies-237776_1280.jpg',
          'Bebidas' => 'ensalada de frutas.jpg'
        ];
        
        $imagen_categoria = $imagenes_categorias[$categoria['nombre']] ?? 'placeholder.jpg';
      ?>
      <a href="<?php echo url('ver-mas-recetas.php?categoria=' . $categoria['id']); ?>" class="cat-card-link">
        <div class="cat-card">
          <img src="<?php echo asset('img/' . $imagen_categoria); ?>" alt="<?php echo $categoria['nombre']; ?>" onerror="this.src='<?php echo asset('img/placeholder.jpg'); ?>'">
          <h4><?php echo $categoria['nombre']; ?></h4>
        </div>
      </a>
      <?php endwhile; ?>
    </div>
  </section>

<!-- === RECETAS DESTACADAS === -->
<section class="recetas-destacadas">
  <h3>Recetas Destacadas</h3>
  <div class="recetas-grid">
    <?php
    $recetas = $conexion->query("SELECT r.*, c.nombre as categoria_nombre 
                                FROM recetas r 
                                LEFT JOIN categorias c ON r.categoria_id = c.id 
                                ORDER BY r.fecha_creacion DESC 
                                LIMIT 6");
    
    if ($recetas->num_rows > 0):
      while($receta = $recetas->fetch_assoc()):
        // DEBUG: Mostrar información de cada receta
        echo "<!-- DEBUG Receta ID: " . $receta['id'] . " -->";
        echo "<!-- DEBUG Título: " . $receta['titulo'] . " -->";
        echo "<!-- DEBUG Imagen en BD: " . $receta['imagen'] . " -->";
        
        $ruta_completa = __DIR__ . '/../img/' . $receta['imagen'];
        echo "<!-- DEBUG Ruta completa: " . $ruta_completa . " -->";
        echo "<!-- DEBUG Existe archivo: " . (file_exists($ruta_completa) ? 'SÍ' : 'NO') . " -->";
        
        $imagen_ruta = 'img/' . $receta['imagen'];
    ?>
    <div class="card">
      <img src="<?php echo asset($imagen_ruta); ?>" 
           alt="<?php echo htmlspecialchars($receta['titulo']); ?>"
           onerror="console.log('Error cargando imagen: <?php echo $receta['imagen']; ?>'); this.src='<?php echo asset('img/placeholder.jpg'); ?>'">
      <h4><?php echo htmlspecialchars($receta['titulo']); ?></h4>
      <p><?php echo substr(htmlspecialchars($receta['descripcion']), 0, 100); ?>...</p>
      <a href="<?php echo url('ver-receta.php?id=' . $receta['id']); ?>" class="btn-ver">Ver Receta</a>
    </div>
    <?php 
      endwhile;
    else:
      echo "<p>No hay recetas disponibles aún.</p>";
    endif;
    ?>
  </div>
</section>

  <!-- === FOOTER SIMPLE === -->
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