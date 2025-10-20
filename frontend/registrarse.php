<?php
require_once __DIR__ . '/../backend/session.php';
require_once __DIR__ . '/../backend/conexion.php';
require_once 'helpers.php';
?>
<?php
require_once __DIR__ . '/../backend/conexion.php';
require_once __DIR__ . '/../backend/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $contraseña = $_POST['contraseña'];
    
    // Validar que no exista el correo
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $error = "Este correo ya está registrado";
    } else {
        // Crear usuario
        $hash_contraseña = password_hash($contraseña, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, correo, contraseña) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $correo, $hash_contraseña);
        
        if ($stmt->execute()) {
            header("Location: iniciar-sesion.php?registro=exitoso");
            exit();
        } else {
            $error = "Error al registrar usuario";
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registrarse - RecepApp</title>
  <link rel="stylesheet" href="css/style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
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

  <section class="auth-container">
    <div class="auth-form">
      <h2>Crear Cuenta</h2>
      <?php if (isset($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
      <?php endif; ?>
      
      <form method="POST">
        <div class="form-group">
          <label>Nombre Completo:</label>
          <input type="text" name="nombre" required>
        </div>
        
        <div class="form-group">
          <label>Correo Electrónico:</label>
          <input type="email" name="correo" required>
        </div>
        
        <div class="form-group">
          <label>Contraseña:</label>
          <input type="password" name="contraseña" required minlength="6">
        </div>
        
        <button type="submit" class="btn-primary">Registrarse</button>
      </form>
      
      <p>¿Ya tienes cuenta? <a href="iniciar-sesion.php">Inicia Sesión aquí</a></p>
      <a href="index.php" class="btn-secondary">← Volver al Inicio</a>
    </div>
  </section>
</body>
</html>