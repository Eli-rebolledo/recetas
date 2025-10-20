<?php
require_once __DIR__ . '/../backend/session.php';
require_once __DIR__ . '/../backend/conexion.php';
require_once 'helpers.php';

if (!usuarioEstaLogeado()) {
    header('Location: iniciar-sesion.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $ingredientes = trim($_POST['ingredientes']);
    $instrucciones = trim($_POST['instrucciones']);
    $categoria_id = intval($_POST['categoria_id']);
    
    // Por ahora, siempre usar placeholder para evitar errores
    $nombre_imagen = 'placeholder.jpg';
    
    // Insertar en BD
    $stmt = $conexion->prepare("INSERT INTO recetas (titulo, descripcion, ingredientes, instrucciones, imagen, categoria_id, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssii", $titulo, $descripcion, $ingredientes, $instrucciones, $nombre_imagen, $categoria_id, $usuario_id);
    
    if ($stmt->execute()) {
        $mensaje = "¡Receta agregada exitosamente!";
        // Limpiar campos
        $titulo = $descripcion = $ingredientes = $instrucciones = '';
        $categoria_id = 0;
    } else {
        $mensaje = "Error al agregar la receta. Intenta nuevamente.";
    }
    $stmt->close();
}

$categorias = $conexion->query("SELECT * FROM categorias ORDER BY nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Agregar Receta - RecepApp</title>
  <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body>
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

  <section class="page-header">
    <h2>Agregar Nueva Receta</h2>
  </section>

  <section class="form-container">
    <div class="auth-form">
      <?php if ($mensaje): ?>
        <div class="<?php echo strpos($mensaje, 'Error') === false ? 'success-message' : 'error-message'; ?>">
          <?php echo $mensaje; ?>
        </div>
      <?php endif; ?>
      
      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label>Título</label>
          <input type="text" name="titulo" value="<?php echo isset($titulo) ? htmlspecialchars($titulo) : ''; ?>" required>
        </div>

        <div class="form-group">
          <label>Descripción</label>
          <textarea name="descripcion" rows="3" required><?php echo isset($descripcion) ? htmlspecialchars($descripcion) : ''; ?></textarea>
        </div>

        <div class="form-group">
          <label>Categoría</label>
          <select name="categoria_id" required>
            <option value="">Selecciona categoría</option>
            <?php while($categoria = $categorias->fetch_assoc()): ?>
              <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Imagen</label>
          <input type="file" name="imagen" accept="image/*">
        </div>

        <div class="form-group">
          <label>Ingredientes</label>
          <textarea name="ingredientes" rows="6" required><?php echo isset($ingredientes) ? htmlspecialchars($ingredientes) : ''; ?></textarea>
        </div>

        <div class="form-group">
          <label>Instrucciones</label>
          <textarea name="instrucciones" rows="8" required><?php echo isset($instrucciones) ? htmlspecialchars($instrucciones) : ''; ?></textarea>
        </div>

        <button type="submit" class="btn-primary">Publicar Receta</button>
      </form>
    </div>
  </section>
</body>
</html>