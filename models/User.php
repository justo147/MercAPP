<?php

/**
 * Clase User
 *
 * Gestiona todas las operaciones relacionadas con los usuarios:
 * - Registro
 * - Autenticación
 * - Verificación de email
 * - Recuperación de contraseña
 * - Edición de perfil
 * - Administración (roles y estados)
 * - Estadísticas del usuario
 */
class User
{
    /**
     * Conexión a la base de datos.
     *
     * @var PDO
     */
    private $db;

    /**
     * Constructor del modelo User.
     *
     * @param PDO $db Conexión PDO inyectada.
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /* ============================================================
       CREACIÓN DE USUARIO
       ============================================================ */

    /**
     * Crea un nuevo usuario en la base de datos.
     *
     * @param string $email Email del usuario.
     * @param string $password Contraseña en texto plano.
     * @param string $nombre Nombre del usuario.
     * @return bool True si se insertó correctamente.
     */
    public function create($email, $password, $nombre)
    {
        $sql = "INSERT INTO usuario (email, contraseña_hash, nombre) 
                VALUES (:email, :password, :nombre)";

        $stmt = $this->db->prepare($sql);
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':nombre', $nombre);

        return $stmt->execute();
    }

    /* ============================================================
       VERIFICACIÓN DE EMAIL
       ============================================================ */

    /**
     * Guarda un token temporal para verificar el email del usuario.
     *
     * @param string $email Email del usuario.
     * @param string $token Token generado.
     * @return bool True si se actualizó correctamente.
     */
    public function setVerifyToken($email, $token)
    {
        $sql = "UPDATE usuario SET verify_token = :token WHERE email = :email";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':email', $email);

        return $stmt->execute();
    }

    /**
     * Verifica el email del usuario si el token coincide.
     *
     * @param string $email Email del usuario.
     * @param string $token Token enviado al correo.
     * @return bool True si se verificó correctamente.
     */
    public function verifyEmail($email, $token)
    {
        $sql = "UPDATE usuario 
                SET email_verificado = 1, verify_token = NULL 
                WHERE email = :email AND verify_token = :token";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':token', $token);

        return $stmt->execute();
    }

    /* ============================================================
       LOGIN Y AUTENTICACIÓN
       ============================================================ */

    /**
     * Obtiene un usuario por su email.
     *
     * @param string $email Email del usuario.
     * @return array|null Datos del usuario o null si no existe.
     */
    public function getByEmail($email)
    {
        $sql = "SELECT * FROM usuario WHERE email = :email LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si las credenciales del usuario son correctas.
     *
     * @param string $email Email del usuario.
     * @param string $password Contraseña en texto plano.
     * @return array|false Datos del usuario o false si falla.
     */
    public function verifyCredentials($email, $password)
    {
        $user = $this->getByEmail($email);
        if (!$user)
            return false;

        if (!password_verify($password, $user['contraseña_hash'])) {
            return false;
        }

        return $user;
    }

    /* ============================================================
       RECUPERACIÓN DE CONTRASEÑA
       ============================================================ */

    /**
     * Guarda un token de recuperación de contraseña.
     *
     * @param string $email Email del usuario.
     * @param string $token Token generado.
     * @param string $expires Fecha de expiración.
     * @return bool True si se actualizó correctamente.
     */
    public function setResetToken($email, $token, $expires)
    {
        $sql = "UPDATE usuario 
                SET reset_token = :token, reset_expires = :expires 
                WHERE email = :email";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires', $expires);
        $stmt->bindParam(':email', $email);

        return $stmt->execute();
    }

    /**
     * Valida un token de recuperación.
     *
     * @param string $email Email del usuario.
     * @param string $token Token recibido.
     * @return array|null Datos del usuario o null si no es válido.
     */
    public function validateResetToken($email, $token)
    {
        $sql = "SELECT * FROM usuario 
                WHERE email = :email 
                AND reset_token = :token 
                AND reset_expires > NOW()";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza la contraseña del usuario.
     *
     * @param string $email Email del usuario.
     * @param string $newPassword Nueva contraseña.
     * @return bool True si se actualizó correctamente.
     */
    public function updatePassword($email, $newPassword)
    {
        $sql = "UPDATE usuario 
                SET contraseña_hash = :password, reset_token = NULL, reset_expires = NULL 
                WHERE email = :email";

        $stmt = $this->db->prepare($sql);
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':email', $email);

        return $stmt->execute();
    }

    /* ============================================================
       PERFIL DEL USUARIO
       ============================================================ */

    /**
     * Actualiza los datos del perfil del usuario.
     *
     * @param int $id ID del usuario.
     * @param string $nombre Nombre.
     * @param string $apellidos Apellidos.
     * @param string $telefono Teléfono.
     * @param string $foto Ruta de la foto.
     * @return bool True si se actualizó correctamente.
     */
    public function updateProfile($id, $nombre, $apellidos, $telefono, $foto)
    {
        $sql = "UPDATE usuario 
                SET nombre = :nombre, apellidos = :apellidos, telefono = :telefono, foto_perfil = :foto 
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellidos', $apellidos);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':foto', $foto);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    /* ============================================================
       ADMINISTRACIÓN
       ============================================================ */

    /**
     * Cambia el estado del usuario (activo, suspendido, etc.).
     *
     * @param int $id ID del usuario.
     * @param int $estado Nuevo estado.
     * @return bool True si se actualizó correctamente.
     */
    public function changeStatus($id, $estado)
    {
        $sql = "UPDATE usuario SET estado = :estado WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    /**
     * Cambia el rol del usuario (admin, usuario, etc.).
     *
     * @param int $id ID del usuario.
     * @param string $rol Nuevo rol.
     * @return bool True si se actualizó correctamente.
     */
    public function changeRole($id, $rol)
    {
        $sql = "UPDATE usuario SET rol = :rol WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    /**
     * Obtiene una lista paginada de todos los usuarios (para panel admin).
     * Opcionalmente filtra por búsqueda (nombre o email).
     *
     * @param int $limit Número de resultados por página.
     * @param int $offset Desplazamiento.
     * @param string $search Término de búsqueda opcional.
     * @return array Lista de usuarios.
     */
    public function getAllUsersPaginated($limit = 20, $offset = 0, $search = '')
    {
        $sql = "SELECT id, nombre, apellidos, email, telefono, fecha_registro, estado, rol 
                FROM usuario 
                WHERE 1=1";

        if (!empty($search)) {
            $sql .= " AND (nombre LIKE :search OR email LIKE :search OR apellidos LIKE :search)";
        }

        $sql .= " ORDER BY fecha_registro DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        if (!empty($search)) {
            $searchTerm = "%$search%";
            $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta el total de usuarios (para la paginación del panel admin).
     *
     * @param string $search Término de búsqueda opcional.
     * @return int Total de usuarios.
     */
    public function countAllUsers($search = '')
    {
        $sql = "SELECT COUNT(*) FROM usuario WHERE 1=1";

        if (!empty($search)) {
            $sql .= " AND (nombre LIKE :search OR email LIKE :search OR apellidos LIKE :search)";
        }

        $stmt = $this->db->prepare($sql);

        if (!empty($search)) {
            $searchTerm = "%$search%";
            $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }

        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }


    /* ============================================================
       UTILIDADES
       ============================================================ */

    /**
     * Comprueba si un email ya está registrado.
     *
     * @param string $email Email a comprobar.
     * @return bool True si existe.
     */
    public function emailExists($email)
    {
        $sql = "SELECT id FROM usuario WHERE email = :email";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /**
     * Obtiene un usuario por su ID.
     *
     * @param int $id ID del usuario.
     * @return array|null Datos del usuario o null.
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM usuario WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ============================================================
       ESTADÍSTICAS DEL USUARIO
       ============================================================ */

    /**
     * Cuenta los productos publicados por un usuario.
     *
     * @param int $userId ID del usuario.
     * @return int Número de productos.
     */
    public function contarProductos($userId)
    {
        $sql = "SELECT COUNT(*) FROM Productos WHERE usuario_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Cuenta las ventas completadas del usuario.
     *
     * @param int $userId ID del usuario.
     * @return int Número de ventas.
     */
    public function contarVentas($userId)
    {
        $sql = "SELECT COUNT(*) FROM Transacciones 
                WHERE vendedor_id = ? AND estado = 'completada'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Obtiene la valoración media del usuario.
     *
     * @param int $userId ID del usuario.
     * @return float Valoración media.
     */
    public function obtenerValoracion($userId)
    {
        $sql = "SELECT reputacion_media FROM vw_usuario_reputacion 
                WHERE usuario_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $valor = $stmt->fetchColumn();
        return $valor ? round($valor, 1) : 0;
    }

    /**
     * Devuelve un paquete completo de estadísticas del usuario.
     *
     * @param int $userId ID del usuario.
     * @return array Estadísticas completas.
     */
    public function obtenerEstadisticas($userId)
    {
        return [
            "productos" => $this->contarProductos($userId),
            "activos" => $this->contarActivos($userId),
            "vendidos" => $this->contarVendidos($userId),
            "ventas" => $this->contarVentas($userId),
            "valoracion" => $this->obtenerValoracion($userId),
            "fecha_registro" => $this->obtenerFechaRegistro($userId),
            "ultima_publicacion" => $this->obtenerUltimaPublicacion($userId)
        ];
    }

    /**
     * Cuenta los productos activos del usuario.
     *
     * @param int $userId ID del usuario.
     * @return int Número de productos activos.
     */
    public function contarActivos($userId)
    {
        $sql = "SELECT COUNT(*) FROM Productos 
                WHERE usuario_id = ? AND estado_publicacion_id = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Cuenta los productos vendidos del usuario.
     *
     * @param int $userId ID del usuario.
     * @return int Número de productos vendidos.
     */
    public function contarVendidos($userId)
    {
        $sql = "SELECT COUNT(*) FROM Productos 
                WHERE usuario_id = ? AND estado_publicacion_id = 3";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Obtiene la fecha de registro del usuario.
     *
     * @param int $userId ID del usuario.
     * @return string|null Fecha de registro.
     */
    public function obtenerFechaRegistro($userId)
    {
        $sql = "SELECT fecha_registro FROM Usuario WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchColumn();
    }

    /**
     * Obtiene la fecha de la última publicación del usuario.
     *
     * @param int $userId ID del usuario.
     * @return string|null Fecha de última publicación.
     */
    public function obtenerUltimaPublicacion($userId)
    {
        $sql = "SELECT fecha_publicacion FROM Productos 
                WHERE usuario_id = ? 
                ORDER BY fecha_publicacion DESC 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchColumn();
    }
}
