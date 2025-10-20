
<?php
require_once __DIR__ . '/../backend/conexion.php';
require_once __DIR__ . '/../backend/session.php';

if (usuarioEstaLogeado()) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo']);
    $contraseña = $_POST['contraseña'];
    
    $stmt = $conexion->prepare("SELECT id, nombre, contraseña FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $nombre, $hash_contraseña);
        $stmt->fetch();
        
        if (password_verify($contraseña, $hash_contraseña)) {
            $_SESSION['usuario_id'] = $id;
            $_SESSION['usuario_nombre'] = $nombre;
            header("Location: index.php");
            exit();
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "Usuario no encontrado";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Iniciar Sesión - RecepApp</title>
  <link rel="stylesheet" href="css/style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
</head>
<body>
  <header class="navbar">
    <div class="logo">
      <i class="fas fa-utensils"></i>
      <h1>RecepApp</h1>
    </div>
    <nav class="menu">
      <a href="index.php">Inicio</a>
      <a href="registrarse.php">Registrarse</a>
    </nav>
  </header>

  <section class="auth-container">
    <div class="auth-form">
      <h2>Iniciar Sesión</h2>
      <?php if (isset($_GET['registro']) && $_GET['registro'] === 'exitoso'): ?>
        <div class="success-message">¡Registro exitoso! Ahora puedes iniciar sesión.</div>
      <?php endif; ?>
      
      <?php if (isset($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
      <?php endif; ?>
      
      <form method="POST">
        <div class="form-group">
          <label>Correo Electrónico:</label>
          <input type="email" name="correo" required>
        </div>
        
        <div class="form-group">
          <label>Contraseña:</label>
          <input type="password" name="contraseña" required>
        </div>
        
        <button type="submit" class="btn-primary">Iniciar Sesión</button>
      </form>
      
      <p>¿No tienes cuenta? <a href="registrarse.php">Regístrate aquí</a></p>
      <a href="index.php" class="btn-secondary">← Volver al Inicio</a>
    </div>
  </section>
</body>
</html>