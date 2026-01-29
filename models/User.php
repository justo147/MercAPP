<?php
// models/Usuario.php

class User
{
    private $db;
    public function __construct($db)
    {
        $this->db = $db;
    }


    // Crear usuario
    public function create($email, $password, $nombre)
    {
        $sql = "INSERT INTO usuario (email, contraseña_hash, nombre) VALUES (:email, :password, :nombre)";
        $stmt = $this->db->prepare($sql);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':nombre', $nombre);
        return $stmt->execute();
    }


    /* ====== VERIFICACIÓN DE EMAIL =================== */
    public function setVerifyToken($email, $token)
    {
        $sql = "UPDATE usuario SET verify_token = :token WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':email', $email);
        return $stmt->execute();
    }

    public function verifyEmail($email, $token)
    {
        $sql = "UPDATE usuario SET email_verificado = 1, verify_token = NULL WHERE email = :email AND verify_token = :token";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':token', $token);
        return $stmt->execute();
    }


    /* ======= LOGIN ================ */
    public function getByEmail($email)
    {
        $sql = "SELECT * FROM usuario WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function verifyCredentials($email, $password)
    {
        $user = $this->getByEmail($email);
        if (!$user) return false;
        if (!password_verify($password, $user['contraseña_hash'])) {
            return false;
        }
        return $user;
    }


    /* ======== RECUPERACIÓN DE CONTRASEÑA ========== */
    public function setResetToken($email, $token, $expires)
    {
        $sql = "UPDATE usuario SET reset_token = :token, reset_expires = :expires WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires', $expires);
        $stmt->bindParam(':email', $email);
        return $stmt->execute();
    }
    public function validateResetToken($email, $token)
    {
        $sql = "SELECT * FROM usuario WHERE email = :email AND reset_token = :token AND reset_expires > NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function updatePassword($email, $newPassword)
    {
        $sql = "UPDATE usuario SET contraseña_hash = :password, reset_token = NULL, reset_expires = NULL WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':email', $email);
        return $stmt->execute();
    }


    /* ==== PERFIL ======== */
    public function updateProfile($id, $nombre, $apellidos, $telefono, $foto)
    {
        $sql = "UPDATE usuario SET nombre = :nombre, apellidos = :apellidos, telefono = :telefono, foto_perfil = :foto WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellidos', $apellidos);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':foto', $foto);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /* ==== ADMIN: ESTADO Y ROL ==== */
    public function changeStatus($id, $estado)
    {
        $sql = "UPDATE usuario SET estado = :estado WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    public function changeRole($id, $rol)
    {
        $sql = "UPDATE usuario SET rol = :rol WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /* ============================================================ UTILIDADES ============================================================ */
    public function emailExists($email)
    {
        $sql = "SELECT id FROM usuario WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
    public function getById($id)
    {
        $sql = "SELECT * FROM usuario WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    // ============================
    // ESTADÍSTICAS DEL USUARIO
    // ============================

    // 1) Número de productos publicados
    public function contarProductos($userId)
    {
        $sql = "SELECT COUNT(*) FROM Productos WHERE usuario_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    // 2) Número de ventas completadas
    public function contarVentas($userId)
    {
        $sql = "SELECT COUNT(*) 
            FROM Transacciones 
            WHERE vendedor_id = ? AND estado = 'completada'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    // 3) Valoración media (vista vw_usuario_reputacion)
    public function obtenerValoracion($userId)
    {
        $sql = "SELECT reputacion_media 
            FROM vw_usuario_reputacion 
            WHERE usuario_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $valor = $stmt->fetchColumn();

        return $valor ? round($valor, 1) : 0;
    }

    // 4) Paquete completo de estadísticas
    public function obtenerEstadisticas($userId)
    {
        return [
            "productos"        => $this->contarProductos($userId),
            "activos"          => $this->contarActivos($userId),
            "vendidos"         => $this->contarVendidos($userId),
            "ventas"           => $this->contarVentas($userId),
            "valoracion"       => $this->obtenerValoracion($userId),
            "fecha_registro"   => $this->obtenerFechaRegistro($userId),
            "ultima_publicacion" => $this->obtenerUltimaPublicacion($userId)
        ];
    }

    public function contarActivos($userId)
    {
        $sql = "SELECT COUNT(*) 
            FROM Productos 
            WHERE usuario_id = ? AND estado_publicacion_id = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
    public function contarVendidos($userId)
    {
        $sql = "SELECT COUNT(*) 
            FROM Productos 
            WHERE usuario_id = ? AND estado_publicacion_id = 3";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
    public function obtenerFechaRegistro($userId)
    {
        $sql = "SELECT fecha_registro FROM Usuario WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    public function obtenerUltimaPublicacion($userId)
    {
        $sql = "SELECT fecha_publicacion 
            FROM Productos 
            WHERE usuario_id = ? 
            ORDER BY fecha_publicacion DESC 
            LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
}
