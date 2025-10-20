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
$mensaje = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ver-recetas-propias.php');
    exit;
}

$receta_id = intval($_GET['id']);

// Verificar que la receta pertenece al usuario
$stmt = $conexion->prepare("SELECT * FROM recetas WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $receta_id, $usuario_id);
$stmt->execute();
$receta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$receta) {
    header('Location: ver-recetas-propias.php');
    exit;
}

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $ingredientes = trim($_POST['ingredientes']);
    $instrucciones = trim($_POST['instrucciones']);
    $categoria_id = intval($_POST['categoria_id']);
    
    $nombre_imagen = $receta['imagen']; // Mantener la imagen actual por defecto
    
    // Procesar nueva imagen si se subió
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array(strtolower($extension), $extensiones_permitidas)) {
            $nombre_imagen = uniqid() . '.' . $extension;
            $ruta_destino = __DIR__ . '/../img/' . $nombre_imagen;
            
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
                // Eliminar imagen anterior si existe y no es la placeholder
                if (!empty($receta['imagen']) && $receta['imagen'] !== 'placeholder.jpg') {
                    $ruta_anterior = __DIR__ . '/../img/' . $receta['imagen'];
                    if (file_exists($ruta_anterior)) {
                        unlink($ruta_anterior);
                    }
                }
            }
        }
    }
    
    // Actualizar en la base de datos
    $stmt = $conexion->prepare("UPDATE recetas SET titulo = ?, descripcion = ?, ingredientes = ?, instrucciones = ?, imagen = ?, categoria_id = ? WHERE id = ?");
    $stmt->bind_param("sssssii", $titulo, $descripcion, $ingredientes, $instrucciones, $nombre_imagen, $categoria_id, $receta_id);
    
    if ($stmt->execute()) {
        $mensaje = "¡Receta actualizada exitosamente!";
        // Actualizar datos locales
        $receta['titulo'] = $titulo;
        $receta['descripcion'] = $descripcion;
        $receta['ingredientes'] = $ingredientes;
        $receta['instrucciones'] = $instrucciones;
        $receta['categoria_id'] = $categoria_id;
        $receta['imagen'] = $nombre_imagen;
    } else {
        $mensaje = "Error al actualizar la receta. Intenta nuevamente.";
    }
    $stmt->close();
}

// Obtener categorías para el select
$categorias = $conexion->query("SELECT * FROM categorias ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Editar Receta - RecepApp</title>
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
            <a href="<?php echo url('ver-recetas-propias.php'); ?>"><i class="fas fa-book"></i> Mis Recetas</a>
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
    <h2><i class="fas fa-edit"></i> Editar Receta</h2>
    <p>Modifica los detalles de tu receta</p>
  </section>

  <!-- === FORMULARIO DE EDICIÓN === -->
  <section class="form-container">
    <div class="auth-form">
      <?php if ($mensaje): ?>
        <div class="success-message"><?php echo $mensaje; ?></div>
      <?php endif; ?>
      
      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label for="titulo"><i class="fas fa-heading"></i> Título de la Receta</label>
          <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($receta['titulo']); ?>" required>
        </div>

        <div class="form-group">
          <label for="descripcion"><i class="fas fa-align-left"></i> Descripción</label>
          <textarea id="descripcion" name="descripcion" rows="3" required><?php echo htmlspecialchars($receta['descripcion']); ?></textarea>
        </div>

        <div class="form-group">
          <label for="categoria_id"><i class="fas fa-layer-group"></i> Categoría</label>
          <select id="categoria_id" name="categoria_id" required>
            <option value="">Selecciona una categoría</option>
            <?php while($categoria = $categorias->fetch_assoc()): ?>
              <option value="<?php echo $categoria['id']; ?>" <?php echo ($receta['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($categoria['nombre']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="imagen"><i class="fas fa-image"></i> Imagen de la Receta</label>
          <?php if (!empty($receta['imagen'])): ?>
            <div style="margin-bottom: 10px;">
              <img src="<?php echo asset('img/' . $receta['imagen']); ?>" alt="Imagen actual" style="max-width: 200px; border-radius: 8px;">
              <p><small>Imagen actual</small></p>
            </div>
          <?php endif; ?>
          <input type="file" id="imagen" name="imagen" accept="image/*">
          <small>Deja vacío para mantener la imagen actual. Formatos: JPG, PNG, GIF</small>
        </div>

        <div class="form-group">
          <label for="ingredientes"><i class="fas fa-shopping-basket"></i> Ingredientes</label>
          <textarea id="ingredientes" name="ingredientes" rows="6" required><?php echo htmlspecialchars($receta['ingredientes']); ?></textarea>
          <small>Un ingrediente por línea</small>
        </div>

        <div class="form-group">
          <label for="instrucciones"><i class="fas fa-list-ol"></i> Instrucciones</label>
          <textarea id="instrucciones" name="instrucciones" rows="8" required><?php echo htmlspecialchars($receta['instrucciones']); ?></textarea>
          <small>Un paso por línea</small>
        </div>

        <button type="submit" class="btn-primary">
          <i class="fas fa-save"></i> Guardar Cambios
        </button>
        
        <a href="<?php echo url('ver-recetas-propias.php'); ?>" class="btn-secondary">
          <i class="fas fa-arrow-left"></i> Volver a Mis Recetas
        </a>
        
        <a href="<?php echo url('ver-receta.php?id=' . $receta_id); ?>" class="btn-secondary">
          <i class="fas fa-eye"></i> Ver Receta
        </a>
      </form>
    </div>
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