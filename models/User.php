<?php
// models/Usuario.php

class Usuario
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Obtener usuario por ID (equivalente a findByPk)
    public function obtenerPorId($id)
    {
        $sql = "SELECT * FROM Usuario WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener usuario por email
    public function obtenerPorEmail($email)
    {
        $sql = "SELECT * FROM Usuario WHERE email = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crear usuario
    public function crear($data)
    {
        $sql = "INSERT INTO Usuario 
                (email, contraseña_hash, nombre, apellidos, telefono, foto_perfil)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['email'],
            $data['contraseña_hash'],
            $data['nombre'],
            $data['apellidos'] ?? null,
            $data['telefono'] ?? null,
            $data['foto_perfil'] ?? null
        ]);
    }


    // ============================
// ESTADÍSTICAS DEL USUARIO
// ============================

// 1) Número de productos publicados
public function contarProductos($userId)
{
    $sql = "SELECT COUNT(*) FROM Productos WHERE usuario_id = ?";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

// 2) Número de ventas completadas
public function contarVentas($userId)
{
    $sql = "SELECT COUNT(*) 
            FROM Transacciones 
            WHERE vendedor_id = ? AND estado = 'completada'";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

// 3) Valoración media (vista vw_usuario_reputacion)
public function obtenerValoracion($userId)
{
    $sql = "SELECT reputacion_media 
            FROM vw_usuario_reputacion 
            WHERE usuario_id = ?";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$userId]);
    $valor = $stmt->fetchColumn();

    return $valor ? round($valor, 1) : 0;
}

// 4) Paquete completo de estadísticas
public function obtenerEstadisticas($userId)
{
    return [
        "productos" => $this->contarProductos($userId),
        "ventas" => $this->contarVentas($userId),
        "valoracion" => $this->obtenerValoracion($userId)
    ];
}

}
