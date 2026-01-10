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
}
