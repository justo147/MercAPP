<?php
require_once __DIR__ . '/../config/db.php';

class RateLimiter
{
    private PDO $conn;

    private int $maxIntentos      = 5;
    private int $ventanaSegundos  = 900; // 15 minutos

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /** Registra un intento fallido para la IP dada. */
    public function registrarIntento(string $ip, string $email = ''): void
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO LoginIntentos (ip, email) VALUES (:ip, :email)"
        );
        $stmt->execute([':ip' => $ip, ':email' => $email ?: null]);
    }

    /** Cuenta los intentos fallidos recientes de una IP. */
    public function contarIntentos(string $ip): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM LoginIntentos
             WHERE ip = :ip AND fecha > DATE_SUB(NOW(), INTERVAL :seg SECOND)"
        );
        $stmt->bindValue(':ip',  $ip);
        $stmt->bindValue(':seg', $this->ventanaSegundos, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /** Indica si la IP está bloqueada por exceso de intentos. */
    public function estaBloqueado(string $ip): bool
    {
        return $this->contarIntentos($ip) >= $this->maxIntentos;
    }

    /**
     * Devuelve los segundos que restan de bloqueo.
     * 0 si no está bloqueada.
     */
    public function segundosRestantes(string $ip): int
    {
        if (!$this->estaBloqueado($ip)) return 0;

        $stmt = $this->conn->prepare(
            "SELECT MIN(fecha) FROM LoginIntentos
             WHERE ip = :ip AND fecha > DATE_SUB(NOW(), INTERVAL :seg SECOND)"
        );
        $stmt->bindValue(':ip',  $ip);
        $stmt->bindValue(':seg', $this->ventanaSegundos, PDO::PARAM_INT);
        $stmt->execute();
        $primeraFecha = $stmt->fetchColumn();

        if (!$primeraFecha) return 0;

        $expira = strtotime($primeraFecha) + $this->ventanaSegundos;
        return max(0, $expira - time());
    }

    /** Elimina los intentos de la IP (al hacer login correcto). */
    public function limpiarIntentos(string $ip): void
    {
        $stmt = $this->conn->prepare("DELETE FROM LoginIntentos WHERE ip = :ip");
        $stmt->execute([':ip' => $ip]);
    }

    /** Obtiene la IP real del cliente (considera proxies). */
    public static function getClientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '0.0.0.0';
    }
}
