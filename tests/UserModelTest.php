<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/User.php';

/**
 * Class UserModelTest
 *
 * Pruebas unitarias para el modelo User.
 * Se utilizan mocks de PDO y PDOStatement para simular la base de datos.
 *
 * @package Tests
 */
class UserModelTest extends TestCase
{
    /**
     * Mock de la conexión PDO.
     *
     * @var PDO|\PHPUnit\Framework\MockObject\MockObject
     */
    private $pdo;

    /**
     * Mock de PDOStatement.
     *
     * @var PDOStatement|\PHPUnit\Framework\MockObject\MockObject
     */
    private $stmt;

    /**
     * Instancia del modelo User a testear.
     *
     * @var User
     */
    private $usuario;

    /**
     * Configuración inicial antes de cada test.
     *
     * Crea mocks de PDO y PDOStatement
     * e instancia el modelo User.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->stmt = $this->createMock(PDOStatement::class);
        $this->pdo = $this->createMock(PDO::class);
        $this->usuario = new User($this->pdo);
    }

    /**
     * Test: create()
     *
     * Verifica que el método create devuelve true
     * cuando la ejecución del statement es exitosa.
     *
     * @return void
     */
    public function testCreateDevuelveTrue()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->usuario->create("test@test.com", "1234", "Juan");

        $this->assertTrue($resultado);
    }

    /**
     * Test: setVerifyToken()
     *
     * Verifica que el token de verificación se guarda correctamente.
     *
     * @return void
     */
    public function testSetVerifyToken()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->usuario->setVerifyToken("test@test.com", "TOKEN123");

        $this->assertTrue($resultado);
    }

    /**
     * Test: verifyEmail()
     *
     * Verifica que el email se confirma correctamente.
     *
     * @return void
     */
    public function testVerifyEmail()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->usuario->verifyEmail("test@test.com", "TOKEN123");

        $this->assertTrue($resultado);
    }

    /**
     * Test: getByEmail()
     *
     * Verifica que se obtiene correctamente un usuario por email.
     *
     * @return void
     */
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

    /**
     * Test: verifyCredentials() - credenciales correctas
     *
     * Verifica que devuelve el usuario cuando la contraseña es válida.
     *
     * @return void
     */
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

    /**
     * Test: verifyCredentials() - credenciales incorrectas
     *
     * Verifica que devuelve false cuando la contraseña no coincide.
     *
     * @return void
     */
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

    /**
     * Test: emailExists()
     *
     * Verifica que retorna true cuando el email existe.
     *
     * @return void
     */
    public function testEmailExists()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetch')->willReturn(["id" => 1]);

        $resultado = $this->usuario->emailExists("test@test.com");

        $this->assertTrue($resultado);
    }

    /**
     * Test: emailExists() - email no existe
     *
     * Verifica que retorna false cuando el email no existe.
     *
     * @return void
     */
    public function testEmailDoesNotExist()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetch')->willReturn(false);

        $resultado = $this->usuario->emailExists("noexiste@test.com");

        $this->assertFalse($resultado);
    }
}
