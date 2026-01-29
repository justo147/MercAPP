<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/User.php';

class UserModelTest extends TestCase
{
    private $pdo;
    private $stmt;
    private $usuario;

    protected function setUp(): void
    {
        // Mock de PDOStatement
        $this->stmt = $this->createMock(PDOStatement::class);

        // Mock de PDO
        $this->pdo = $this->createMock(PDO::class);

        // Instancia del modelo
        $this->usuario = new User($this->pdo);
    }

    /** ============================
     *  TEST: create()
     * ============================ */
    public function testCreateDevuelveTrue()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->usuario->create("test@test.com", "1234", "Juan");

        $this->assertTrue($resultado);
    }

    /** ============================
     *  TEST: setVerifyToken()
     * ============================ */
    public function testSetVerifyToken()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->usuario->setVerifyToken("test@test.com", "TOKEN123");

        $this->assertTrue($resultado);
    }

    /** ============================
     *  TEST: verifyEmail()
     * ============================ */
    public function testVerifyEmail()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->usuario->verifyEmail("test@test.com", "TOKEN123");

        $this->assertTrue($resultado);
    }

    /** ============================
     *  TEST: getByEmail()
     * ============================ */
    public function testGetByEmail()
    {
        $fakeUser = [
            "id" => 1,
            "email" => "test@test.com",
            "contraseña_hash" => password_hash("1234", PASSWORD_DEFAULT)
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetch')->willReturn($fakeUser);

        $resultado = $this->usuario->getByEmail("test@test.com");

        $this->assertEquals($fakeUser, $resultado);
    }

    /** ============================
     *  TEST: verifyCredentials()
     * ============================ */
    public function testVerifyCredentialsCorrectas()
    {
        $fakeUser = [
            "id" => 1,
            "email" => "test@test.com",
            "contraseña_hash" => password_hash("1234", PASSWORD_DEFAULT)
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetch')->willReturn($fakeUser);

        $resultado = $this->usuario->verifyCredentials("test@test.com", "1234");

        $this->assertEquals($fakeUser, $resultado);
    }

    public function testVerifyCredentialsIncorrectas()
    {
        $fakeUser = [
            "id" => 1,
            "email" => "test@test.com",
            "contraseña_hash" => password_hash("1234", PASSWORD_DEFAULT)
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetch')->willReturn($fakeUser);

        $resultado = $this->usuario->verifyCredentials("test@test.com", "malaClave");

        $this->assertFalse($resultado);
    }

    /** ============================
     *  TEST: emailExists()
     * ============================ */
    public function testEmailExists()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetch')->willReturn(["id" => 1]);

        $resultado = $this->usuario->emailExists("test@test.com");

        $this->assertTrue($resultado);
    }

    public function testEmailDoesNotExist()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetch')->willReturn(false);

        $resultado = $this->usuario->emailExists("noexiste@test.com");

        $this->assertFalse($resultado);
    }
}
