<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../models/User.php';

/**
 * Class UserModelTest
 *
 * Pruebas unitarias para el modelo User.
 * Se utilizan mocks de PDO y PDOStatement para simular la base de datos.
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
     */
    public function testEmailDoesNotExist()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetch')->willReturn(false);

        $resultado = $this->usuario->emailExists("noexiste@test.com");

        $this->assertFalse($resultado);
    }

    /**
     * Test: setResetToken()
     *
     * Verifica que se guarda correctamente el token de recuperación.
     */
    public function testSetResetToken()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->usuario->setResetToken("test@test.com", "TOKEN123", "2026-01-01 12:00:00");

        $this->assertTrue($resultado);
    }

    /**
     * Test: validateResetToken()
     *
     * Verifica que se obtiene el usuario si el token es válido.
     */
    public function testValidateResetToken()
    {
        $fakeUser = [
            "id" => 1,
            "email" => "test@test.com",
            "reset_token" => "TOKEN123",
            "reset_expires" => "2099-01-01 00:00:00"
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetch')->willReturn($fakeUser);

        $resultado = $this->usuario->validateResetToken("test@test.com", "TOKEN123");

        $this->assertEquals($fakeUser, $resultado);
    }

    /**
     * Test: updatePassword()
     *
     * Verifica que la contraseña se actualiza correctamente.
     */
    public function testUpdatePassword()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->usuario->updatePassword("test@test.com", "nuevaClave");

        $this->assertTrue($resultado);
    }

    /**
     * Test: updateProfile()
     *
     * Verifica que el perfil del usuario se actualiza correctamente.
     */
    public function testUpdateProfile()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->usuario->updateProfile(1, "Juan", "Pérez", "600123123", "foto.jpg");

        $this->assertTrue($resultado);
    }

    /**
     * Test: changeStatus()
     *
     * Verifica que el estado del usuario se actualiza correctamente.
     */
    public function testChangeStatus()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->usuario->changeStatus(1, 2);

        $this->assertTrue($resultado);
    }

    /**
     * Test: changeRole()
     *
     * Verifica que el rol del usuario se actualiza correctamente.
     */
    public function testChangeRole()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->usuario->changeRole(1, "admin");

        $this->assertTrue($resultado);
    }

    /**
     * Test: contarProductos()
     *
     * Verifica que retorna el número de productos del usuario.
     */
    public function testContarProductos()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(5);

        $resultado = $this->usuario->contarProductos(1);

        $this->assertEquals(5, $resultado);
    }

    /**
     * Test: contarActivos()
     *
     * Verifica que retorna el número de productos activos.
     */
    public function testContarActivos()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(3);

        $resultado = $this->usuario->contarActivos(1);

        $this->assertEquals(3, $resultado);
    }

    /**
     * Test: contarVendidos()
     *
     * Verifica que retorna el número de productos vendidos.
     */
    public function testContarVendidos()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(2);

        $resultado = $this->usuario->contarVendidos(1);

        $this->assertEquals(2, $resultado);
    }

    /**
     * Test: contarVentas()
     *
     * Verifica que retorna el número de ventas realizadas.
     */
    public function testContarVentas()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(7);

        $resultado = $this->usuario->contarVentas(1);

        $this->assertEquals(7, $resultado);
    }

    /**
     * Test: obtenerValoracion()
     *
     * Verifica que retorna la valoración media del usuario.
     */
    public function testObtenerValoracion()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(4.3);

        $resultado = $this->usuario->obtenerValoracion(1);

        $this->assertEquals(4.3, $resultado);
    }

    /**
     * Test: obtenerFechaRegistro()
     *
     * Verifica que retorna la fecha de registro del usuario.
     */
    public function testObtenerFechaRegistro()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn("2024-01-01");

        $resultado = $this->usuario->obtenerFechaRegistro(1);

        $this->assertEquals("2024-01-01", $resultado);
    }

    /**
     * Test: obtenerUltimaPublicacion()
     *
     * Verifica que retorna la fecha de la última publicación del usuario.
     */
    public function testObtenerUltimaPublicacion()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn("2024-05-10");

        $resultado = $this->usuario->obtenerUltimaPublicacion(1);

        $this->assertEquals("2024-05-10", $resultado);
    }

    /**
     * Test: obtenerEstadisticas()
     *
     * Verifica que el método combina correctamente
     * todas las estadísticas del usuario.
     */
    public function testObtenerEstadisticas()
    {
        $mock = $this->getMockBuilder(User::class)
            ->setConstructorArgs([$this->pdo])
            ->onlyMethods([
                'contarProductos',
                'contarActivos',
                'contarVendidos',
                'contarVentas',
                'obtenerValoracion',
                'obtenerFechaRegistro',
                'obtenerUltimaPublicacion'
            ])
            ->getMock();

        $mock->method('contarProductos')->willReturn(10);
        $mock->method('contarActivos')->willReturn(5);
        $mock->method('contarVendidos')->willReturn(3);
        $mock->method('contarVentas')->willReturn(7);
        $mock->method('obtenerValoracion')->willReturn(4.5);
        $mock->method('obtenerFechaRegistro')->willReturn("2024-01-01");
        $mock->method('obtenerUltimaPublicacion')->willReturn("2024-05-10");

        $resultado = $mock->obtenerEstadisticas(1);

        $this->assertEquals([
            "productos" => 10,
            "activos" => 5,
            "vendidos" => 3,
            "ventas" => 7,
            "valoracion" => 4.5,
            "fecha_registro" => "2024-01-01",
            "ultima_publicacion" => "2024-05-10"
        ], $resultado);
    }
}
