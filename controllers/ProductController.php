<?php

require_once __DIR__ . '/../core/Controller.php';

class ProductController extends Controller
{
    public function detail(array $params = []): void
    {
        require_once __DIR__ . '/../models/Product.php';
        require_once __DIR__ . '/../models/User.php';
        require_once __DIR__ . '/../config/bootstrap.php';

        $productId = intval($params['id'] ?? 0);
        if ($productId <= 0) {
            $this->redirect("{$this->base}/home");
        }

        $productModel = new Product($this->conn);
        $producto     = $productModel->getById($productId);
        if (!$producto) {
            $this->redirect("{$this->base}/home");
        }

        $vendedorId      = $producto['usuario_id'] ?? null;
        $vendedor        = null;
        $yaSigue         = false;
        $usuarioLogueado = $_SESSION['user_id'] ?? null;

        if ($vendedorId) {
            $userModel = new User($this->conn);
            $vendedor  = $userModel->getById($vendedorId);
            if ($usuarioLogueado && $usuarioLogueado != $vendedorId) {
                $yaSigue = $userModel->sigueA($usuarioLogueado, $vendedorId);
            }
        }

        $productosSugeridos = $productModel->getRandomProducts(20, $productId);
        $imagenes           = is_array($producto['imagenes']) ? $producto['imagenes'] : [];
        $imgPrincipal       = !empty($imagenes)
            ? $this->base . '/' . $imagenes[0]['url']
            : $this->base . '/public/img/default.jpg';

        $precioStr = (!empty($producto['precio']) && (float) $producto['precio'] > 0)
            ? number_format((float) $producto['precio'], 2) . ' €'
            : 'Gratis';

        $tipoBadge = [
            'venta'       => ['bg-primary',          'Venta'],
            'intercambio' => ['bg-warning text-dark', 'Intercambio'],
            'mixto'       => ['bg-success',           'Venta / Intercambio'],
        ];
        [$tipoCls, $tipoLabel] = $tipoBadge[$producto['tipo_transaccion'] ?? '']
            ?? ['bg-secondary', ucfirst($producto['tipo_transaccion'] ?? '')];

        $this->render('detail_product.html.twig', [
            'producto'           => $producto,
            'imagenes'           => $imagenes,
            'imgPrincipal'       => $imgPrincipal,
            'precioStr'          => $precioStr,
            'tipoCls'            => $tipoCls,
            'tipoLabel'          => $tipoLabel,
            'vendedor'           => $vendedor,
            'vendedorId'         => $vendedorId,
            'yaSigue'            => $yaSigue,
            'usuarioLogueado'    => $usuarioLogueado,
            'productosSugeridos' => $productosSugeridos,
        ]);
    }

    public function upload(array $params = []): void
    {
        $this->requireAuth();

        require_once __DIR__ . '/../models/Notification.php';
        require_once __DIR__ . '/../config/bootstrap.php';

        $error = '';

        $stmt            = $this->conn->query("SELECT id, nombre FROM Categorias ORDER BY nombre");
        $categorias      = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt            = $this->conn->query("SELECT id, nombre FROM EstadoProducto ORDER BY id");
        $estados_producto = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $userId = $_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titulo      = trim($_POST['titulo']      ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio      = trim($_POST['precio']      ?? '');
            $categoria   = intval($_POST['categoria_id']         ?? 0);
            $estadoProd  = intval($_POST['estado_producto_id']   ?? 0);
            $tipoTrans   = trim($_POST['tipo_transaccion']       ?? '');
            $ubicacion   = trim($_POST['ubicacion']              ?? '');
            $lat         = is_numeric($_POST['lat'] ?? '') ? floatval($_POST['lat']) : null;
            $lon         = is_numeric($_POST['lon'] ?? '') ? floatval($_POST['lon']) : null;

            if (empty($titulo)) {
                $error = "El título es obligatorio.";
            } elseif ($categoria <= 0) {
                $error = "Debes seleccionar una categoría.";
            } elseif ($estadoProd <= 0) {
                $error = "Debes seleccionar un estado del producto.";
            } elseif (!in_array($tipoTrans, ['venta', 'intercambio', 'mixto'])) {
                $error = "Tipo de transacción no válido.";
            }

            if (empty($error)) {
                $stmt = $this->conn->prepare("
                    INSERT INTO Productos
                    (usuario_id, categoria_id, titulo, descripcion, precio, estado_producto_id, tipo_transaccion, estado_publicacion_id, ubicacion, lat, lon)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId, $categoria, $titulo, $descripcion,
                    $precio !== '' ? $precio : null,
                    $estadoProd, $tipoTrans, $ubicacion, $lat, $lon,
                ]);
                $productoId = $this->conn->lastInsertId();

                $ordenArray = array_map('intval', explode(',', $_POST['orden_imagenes'] ?? ''));

                if (!empty($_FILES['imagenes']['name'][0])) {
                    $targetDir = __DIR__ . '/../public/uploads/products/';
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }

                    foreach ($ordenArray as $pos => $originalIndex) {
                        $tmpName   = $_FILES['imagenes']['tmp_name'][$originalIndex] ?? '';
                        $fileError = $_FILES['imagenes']['error'][$originalIndex] ?? UPLOAD_ERR_NO_FILE;
                        if ($fileError !== UPLOAD_ERR_OK || empty($tmpName)) {
                            continue;
                        }

                        $info = getimagesize($tmpName);
                        if (!$info) {
                            continue;
                        }

                        $img = match($info['mime']) {
                            'image/jpeg', 'image/jpg', 'image/pjpeg' => imagecreatefromjpeg($tmpName),
                            'image/png'  => imagecreatefrompng($tmpName),
                            'image/gif'  => imagecreatefromgif($tmpName),
                            'image/webp' => imagecreatefromwebp($tmpName),
                            default      => null,
                        };
                        if (!$img) {
                            continue;
                        }

                        $maxWidth = 900;
                        $w        = imagesx($img);
                        $h        = imagesy($img);
                        if ($w > $maxWidth) {
                            $ratio     = $h / $w;
                            $newW      = $maxWidth;
                            $newH      = (int) ($maxWidth * $ratio);
                            $tmp       = imagecreatetruecolor($newW, $newH);
                            imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
                            $img = $tmp;
                        }

                        $fileName   = "Prod_{$productoId}_" . uniqid() . ".webp";
                        $targetFile = $targetDir . $fileName;
                        imagewebp($img, $targetFile, 80);

                        $stmtImg = $this->conn->prepare("INSERT INTO Imagenes_prod (id_producto, url, orden) VALUES (?, ?, ?)");
                        $stmtImg->execute([$productoId, "public/uploads/products/{$fileName}", $pos + 1]);
                    }
                }

                // Notificar coincidencias en deseos
                try {
                    $notifModel  = new Notification($this->conn);
                    $stmtDeseos  = $this->conn->query("SELECT d.id, d.usuario_id, d.etiquetas, d.categoria_id, d.estado_producto_id FROM Deseos d WHERE d.usuario_id != {$userId}");
                    $todosDeseos = $stmtDeseos->fetchAll(PDO::FETCH_ASSOC);
                    $notificados = [];

                    foreach ($todosDeseos as $deseo) {
                        $dest = intval($deseo['usuario_id']);
                        if (in_array($dest, $notificados)) {
                            continue;
                        }
                        if (!empty($deseo['categoria_id']) && intval($deseo['categoria_id']) !== $categoria) {
                            continue;
                        }
                        if (!empty($deseo['estado_producto_id']) && intval($deseo['estado_producto_id']) !== $estadoProd) {
                            continue;
                        }
                        $palabras = array_filter(
                            array_map('trim', preg_split('/[\s,;]+/', $deseo['etiquetas'] ?? '')),
                            fn($p) => mb_strlen($p) >= 2
                        );
                        if (empty($palabras)) {
                            continue;
                        }
                        foreach ($palabras as $p) {
                            if (mb_stripos($titulo, $p) !== false || mb_stripos($descripcion, $p) !== false) {
                                $notifModel->create(
                                    $dest,
                                    'coincidencia',
                                    "¡Hay un nuevo producto que coincide con tu deseo \"{$deseo['etiquetas']}\": {$titulo}.",
                                    "{$this->base}/product/{$productoId}"
                                );
                                $notificados[] = $dest;
                                break;
                            }
                        }
                    }
                } catch (Exception $eNotif) {
                    // silencioso
                }

                $this->redirect("{$this->base}/product/upload?success=1");
            }
        }

        $this->render('upload_product.html.twig', [
            'categorias'       => $categorias,
            'estados_producto' => $estados_producto,
            'post_ubicacion'   => $_POST['ubicacion'] ?? '',
            'post_lat'         => $_POST['lat']       ?? '',
            'post_lon'         => $_POST['lon']       ?? '',
        ]);
    }

    public function edit(array $params = []): void
    {
        $this->requireAuth();

        require_once __DIR__ . '/../models/Product.php';

        $productId    = intval($params['id'] ?? 0);
        $userId       = $_SESSION['user_id'];
        $fatalError   = '';
        $producto     = null;
        $categorias   = [];
        $estados_producto = [];
        $success      = '';
        $error        = '';

        if (!isset($params['id']) || !is_numeric($params['id'])) {
            $fatalError = 'ID de producto no válido';
        } else {
            $productModel = new Product($this->conn);
            $producto     = $productModel->getById($productId);

            if (!$producto) {
                $fatalError = 'Producto no encontrado';
            } elseif ($producto['usuario_id'] != $userId) {
                $fatalError = 'No tienes permiso para editar este producto';
            } else {
                $categorias       = $this->conn->query('SELECT * FROM Categorias ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
                $estados_producto = $this->conn->query('SELECT * FROM EstadoProducto ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $titulo             = trim($_POST['titulo']             ?? '');
                    $descripcion        = trim($_POST['descripcion']        ?? '');
                    $precio             = floatval($_POST['precio']         ?? 0);
                    $categoria_id       = intval($_POST['categoria_id']     ?? 0);
                    $estado_producto_id = intval($_POST['estado_producto_id'] ?? 0);
                    $tipo_transaccion   = trim($_POST['tipo_transaccion']   ?? '');
                    $ubicacion          = trim($_POST['ubicacion']          ?? '');

                    $ownerId = $this->conn->prepare("SELECT usuario_id FROM Productos WHERE id = ?");
                    $ownerId->execute([$productId]);
                    $owner = $ownerId->fetchColumn();

                    if (!$owner) {
                        $error = "El producto no existe";
                    } elseif ((int) $owner !== (int) $userId) {
                        $error = "No tienes permiso para editar este producto";
                    } elseif ($titulo === '' || $categoria_id === 0 || $estado_producto_id === 0) {
                        $error = "Faltan campos obligatorios";
                    } else {
                        $updateData = [
                            'categoria_id'         => $categoria_id,
                            'titulo'               => $titulo,
                            'descripcion'          => $descripcion,
                            'precio'               => $precio,
                            'estado_producto_id'   => $estado_producto_id,
                            'tipo_transaccion'     => $tipo_transaccion,
                            'estado_publicacion_id' => 1,
                            'ubicacion'            => $ubicacion,
                        ];

                        if (!$productModel->update($productId, $updateData)) {
                            $error = "Error al actualizar el producto";
                        } else {
                            if (!empty($_POST['delete_images'])) {
                                foreach ($_POST['delete_images'] as $url) {
                                    $ruta = __DIR__ . '/../' . $url;
                                    if (file_exists($ruta)) {
                                        unlink($ruta);
                                    }
                                    $productModel->deleteImage($productId, $url);
                                }
                            }

                            if (!empty($_FILES['imagenes']['name'][0])) {
                                $uploadDir = __DIR__ . '/../public/uploads/products/';
                                if (!is_dir($uploadDir)) {
                                    mkdir($uploadDir, 0777, true);
                                }
                                $ordenes = array_map('intval', explode(',', $_POST['orden_imagenes'] ?? ''));

                                foreach ($ordenes as $orden => $originalIndex) {
                                    $tmp  = $_FILES['imagenes']['tmp_name'][$originalIndex] ?? '';
                                    $ferr = $_FILES['imagenes']['error'][$originalIndex] ?? UPLOAD_ERR_NO_FILE;
                                    if ($ferr !== UPLOAD_ERR_OK || empty($tmp)) {
                                        continue;
                                    }
                                    $info = getimagesize($tmp);
                                    if (!$info) {
                                        continue;
                                    }
                                    $img = match($info['mime']) {
                                        'image/jpeg', 'image/jpg', 'image/pjpeg' => imagecreatefromjpeg($tmp),
                                        'image/png'  => imagecreatefrompng($tmp),
                                        'image/gif'  => imagecreatefromgif($tmp),
                                        'image/webp' => imagecreatefromwebp($tmp),
                                        default      => null,
                                    };
                                    if (!$img) {
                                        continue;
                                    }
                                    $maxWidth = 1200;
                                    $w        = imagesx($img);
                                    $h        = imagesy($img);
                                    if ($w > $maxWidth) {
                                        $ratio    = $h / $w;
                                        $newW     = $maxWidth;
                                        $newH     = (int) ($maxWidth * $ratio);
                                        $tmpImg   = imagecreatetruecolor($newW, $newH);
                                        imagecopyresampled($tmpImg, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
                                        $img = $tmpImg;
                                    }
                                    $fileName   = "Prod_{$productId}_" . uniqid() . ".webp";
                                    $rutaFinal  = $uploadDir . $fileName;
                                    imagewebp($img, $rutaFinal, 80);
                                    $productModel->insertImage($productId, "public/uploads/products/{$fileName}", $orden);
                                }
                            }

                            $success = "Producto actualizado correctamente";
                            $producto = $productModel->getById($productId);
                        }
                    }
                }
            }
        }

        $this->render('mod_product.html.twig', [
            'producto'         => $producto,
            'fatalError'       => $fatalError,
            'categorias'       => $categorias,
            'estados_producto' => $estados_producto,
            'success'          => $success,
            'error'            => $error,
        ]);
    }

    public function delete(array $params = []): void
    {
        $this->requireAuth();

        require_once __DIR__ . '/../models/Product.php';

        $userId    = $_SESSION['user_id'];
        $productId = intval($_GET['id'] ?? 0);

        if ($productId <= 0) {
            $this->redirect("{$this->base}/home");
        }

        $productModel = new Product($this->conn);
        $stmt         = $this->conn->prepare("SELECT usuario_id FROM Productos WHERE id = ?");
        $stmt->execute([$productId]);
        $owner = $stmt->fetchColumn();

        if ($owner != $userId) {
            $this->redirect("{$this->base}/home");
        }

        $productModel->deleteWithImages($productId);
        $this->redirect("{$this->base}/profile/{$userId}?deleted=1");
    }

    public function report(array $params = []): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect("{$this->base}/login");
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("{$this->base}/home");
        }

        require_once __DIR__ . '/../models/Report.php';

        $usuarioId  = intval($_SESSION['user_id']);
        $productoId = intval($_POST['producto_id'] ?? 0);
        $motivo     = trim($_POST['motivo'] ?? '');
        $redirectTo = trim($_POST['redirect_to'] ?? '');

        if (empty($redirectTo) || !str_starts_with($redirectTo, '/')) {
            $redirectTo = "{$this->base}/home";
        }

        if ($productoId <= 0 || $motivo === '') {
            header("Location: {$redirectTo}?reporte=error");
            exit;
        }

        $reportModel = new Report($this->conn);
        $ok          = $reportModel->create($usuarioId, $productoId, $motivo);

        header("Location: {$redirectTo}" . ($ok ? "?reporte=ok" : "?reporte=error"));
        exit;
    }
}
