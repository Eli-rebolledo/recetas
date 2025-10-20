<?php
require_once __DIR__ . '/../backend/session.php';
require_once __DIR__ . '/../backend/conexion.php';
require_once 'helpers.php';

// Obtener parámetros de búsqueda y filtro
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$categoria_id = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;

// Construir la consulta base
$sql = "SELECT r.*, c.nombre as categoria_nombre, u.nombre as usuario_nombre 
        FROM recetas r 
        LEFT JOIN categorias c ON r.categoria_id = c.id 
        LEFT JOIN usuarios u ON r.usuario_id = u.id 
        WHERE 1=1";

$params = [];
$types = '';

// Aplicar filtro de búsqueda
if (!empty($busqueda)) {
    $sql .= " AND (r.titulo LIKE ? OR r.descripcion LIKE ? OR r.ingredientes LIKE ?)";
    $search_term = "%$busqueda%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

// Aplicar filtro de categoría
if ($categoria_id > 0) {
    $sql .= " AND r.categoria_id = ?";
    $params[] = $categoria_id;
    $types .= 'i';
}

// Ordenar y completar consulta
$sql .= " ORDER BY r.fecha_creacion DESC";

// Preparar y ejecutar consulta
$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$recetas = $stmt->get_result();

// Obtener categorías para el filtro
$categorias = $conexion->query("SELECT * FROM categorias ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Todas las Recetas - RecepApp</title>
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
      <a href="<?php echo url('ver-mas-recetas.php'); ?>" class="active">Recetas</a>
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
    <h2><i class="fas fa-book-open"></i> Todas las Recetas</h2>
    <p>Descubre todas las recetas de nuestra comunidad</p>
  </section>

<!-- === FILTROS Y BÚSQUEDA MEJORADOS === -->
<!-- === FILTROS SIMPLIFICADOS === -->
<section class="filtros">
    <div class="filtros-container">
        <form method="GET" class="filtros-form">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" 
                       name="busqueda" 
                       value="<?php echo htmlspecialchars($busqueda); ?>" 
                       placeholder="Buscar recetas...">
            </div>
            
            <select name="categoria">
                <option value="0">Todas las categorías</option>
                <?php 
                $categorias_select = $conexion->query("SELECT * FROM categorias ORDER BY nombre");
                while($categoria = $categorias_select->fetch_assoc()): 
                ?>
                    <option value="<?php echo $categoria['id']; ?>" 
                            <?php echo ($categoria_id == $categoria['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
            <button type="submit" class="btn-buscar">
                <i class="fas fa-search"></i>
                Buscar
            </button>
            
            <?php if (!empty($busqueda) || $categoria_id > 0): ?>
                <a href="<?php echo url('ver-mas-recetas.php'); ?>" class="btn-limpiar">
                    Limpiar
                </a>
            <?php endif; ?>
        </form>
    </div>
</section>


  <!-- === RESULTADOS === -->
  <section class="recetas-lista">
    <?php if ($recetas->num_rows > 0): ?>
      
      <!-- Información de resultados -->
      <div class="resultados-info">
        <p>
          <strong><?php echo $recetas->num_rows; ?></strong> 
          receta<?php echo $recetas->num_rows != 1 ? 's' : ''; ?> encontrada<?php echo $recetas->num_rows != 1 ? 's' : ''; ?>
          
          <?php if (!empty($busqueda)): ?>
            para "<strong><?php echo htmlspecialchars($busqueda); ?></strong>"
          <?php endif; ?>
          
          <?php if ($categoria_id > 0): ?>
            <?php 
            $categoria_nombre = '';
            $cat_result = $conexion->query("SELECT nombre FROM categorias WHERE id = $categoria_id");
            if ($cat_result->num_rows > 0) {
                $categoria_nombre = $cat_result->fetch_assoc()['nombre'];
            }
            ?>
            en <strong><?php echo htmlspecialchars($categoria_nombre); ?></strong>
          <?php endif; ?>
        </p>
      </div>

      <!-- Grid de recetas -->
      <div class="recetas-grid">
        <?php while($receta = $recetas->fetch_assoc()): ?>
          <div class="card">
            <img src="<?php echo asset('img/' . ($receta['imagen'] ?: 'placeholder.jpg')); ?>" 
                 alt="<?php echo htmlspecialchars($receta['titulo']); ?>"
                 onerror="this.src='<?php echo asset('img/placeholder.jpg'); ?>'">
            
            <div class="card-content">
              <span class="categoria-badge"><?php echo htmlspecialchars($receta['categoria_nombre']); ?></span>
              
              <h4><?php echo htmlspecialchars($receta['titulo']); ?></h4>
              
              <p class="descripcion"><?php echo substr(htmlspecialchars($receta['descripcion']), 0, 120); ?>...</p>
              
              <div class="card-meta">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($receta['usuario_nombre']); ?></span>
                <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($receta['fecha_creacion'])); ?></span>
              </div>
              
              <div class="card-actions">
                <a href="<?php echo url('ver-receta.php?id=' . $receta['id']); ?>" class="btn-ver">
                  <i class="fas fa-eye"></i> Ver Receta
                </a>
                
                <?php if (usuarioEstaLogeado() && $_SESSION['usuario_id'] == $receta['usuario_id']): ?>
                  <a href="<?php echo url('editar-receta.php?id=' . $receta['id']); ?>" class="btn-editar">
                    <i class="fas fa-edit"></i> Editar
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>

    <?php else: ?>
      <!-- No hay resultados -->
      <div class="no-resultados">
        <i class="fas fa-search" style="font-size: 4rem; color: #f1cadd; margin-bottom: 20px;"></i>
        <h3>No se encontraron recetas</h3>
        <p>
          <?php if (!empty($busqueda)): ?>
            No hay recetas que coincidan con "<strong><?php echo htmlspecialchars($busqueda); ?></strong>"
            <?php if ($categoria_id > 0): ?>
              en la categoría seleccionada.
            <?php endif; ?>
          <?php else: ?>
            No hay recetas disponibles en este momento.
          <?php endif; ?>
        </p>
        <div class="no-resultados-actions">
          <a href="<?php echo url('ver-mas-recetas.php'); ?>" class="btn-primary">
            <i class="fas fa-list"></i> Ver Todas las Recetas
          </a>
          <?php if (usuarioEstaLogeado()): ?>
            <a href="<?php echo url('agregar-receta.php'); ?>" class="btn-secondary">
              <i class="fas fa-plus"></i> Crear Primera Receta
            </a>
          <?php endif; ?>
        </div>
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

      // Auto-submit al cambiar categoría (opcional)
      document.getElementById('categoria').addEventListener('change', function() {
        if (this.value !== '0') {
          this.form.submit();
        }
      });

      // Mostrar/ocultar botón limpiar
      const busquedaInput = document.getElementById('busqueda');
      const categoriaSelect = document.getElementById('categoria');
      
      function toggleClearButton() {
        const clearBtn = document.querySelector('.btn-secondary');
        if (busquedaInput.value || categoriaSelect.value !== '0') {
          if (!clearBtn) {
            const filtroGroup = document.querySelector('.filtro-group:last-child');
            const clearLink = document.createElement('a');
            clearLink.href = '<?php echo url('ver-mas-recetas.php'); ?>';
            clearLink.className = 'btn-secondary';
            clearLink.innerHTML = '<i class="fas fa-times"></i> Limpiar';
            filtroGroup.appendChild(clearLink);
          }
        } else if (clearBtn) {
          clearBtn.remove();
        }
      }
      
      busquedaInput.addEventListener('input', toggleClearButton);
      categoriaSelect.addEventListener('change', toggleClearButton);
    });
  </script>
</body>
</html>