<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../models/Notification.php';

/**
 * Clases fake para permitir mocks de PDO y PDOStatement.
 * 
 * Estas clases vacías extienden a PDO y PDOStatement únicamente
 * para que PHPUnit pueda generar mocks sobre ellas, ya que las
 * clases originales no permiten ser mockeadas directamente.
 */
class FakePDONotification extends PDO {}
class FakeStatementNotification extends PDOStatement {}

/**
 * Test unitarios para el modelo Notification.
 *
 * Se prueban los métodos CRUD y de consulta utilizando mocks
 * de PDO y PDOStatement para evitar dependencias reales con la base de datos.
 */
class NotificationModelTest extends TestCase
{
    /**
     * @var PDO|FakePDONotification Mock de la conexión PDO.
     */
    private $pdo;

    /**
     * @var PDOStatement|FakeStatementNotification Mock del statement.
     */
    private $stmt;

    /**
     * @var Notification Instancia del modelo a probar.
     */
    private $notification;

    /**
     * Configura los mocks antes de cada test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->stmt = $this->createMock(FakeStatementNotification::class);
        $this->pdo  = $this->createMock(FakePDONotification::class);

        $this->notification = new Notification($this->pdo);
    }

    /* ============================================================
       create()
       ============================================================ */

    /**
     * Verifica que create() devuelve true cuando execute() funciona correctamente.
     *
     * @return void
     */
    public function testCreateDevuelveTrue()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([
                ':usuario_id' => 5,
                ':tipo'       => 'mensaje',
                ':contenido'  => 'Tienes un nuevo mensaje'
            ])
            ->willReturn(true);

        $resultado = $this->notification->create(5, 'mensaje', 'Tienes un nuevo mensaje');

        $this->assertTrue($resultado);
    }

    /**
     * Verifica que create() devuelve false si execute() falla.
     *
     * @return void
     */
    public function testCreateDevuelveFalseSiFalla()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->method('execute')->willReturn(false);

        $resultado = $this->notification->create(5, 'mensaje', 'Error');

        $this->assertFalse($resultado);
    }

    /* ============================================================
       getByUser()
       ============================================================ */

    /**
     * Verifica que getByUser() devuelve una lista de notificaciones.
     *
     * @return void
     */
    public function testGetByUserDevuelveLista()
    {
        $fakeNotifications = [
            ["id" => 1, "usuario_id" => 5, "contenido" => "Hola"],
            ["id" => 2, "usuario_id" => 5, "contenido" => "Nueva oferta"]
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':usuario_id' => 5]);

        $this->stmt->method('fetchAll')->willReturn($fakeNotifications);

        $resultado = $this->notification->getByUser(5);

        $this->assertCount(2, $resultado);
        $this->assertEquals($fakeNotifications, $resultado);
    }

    /* ============================================================
       getUnread()
       ============================================================ */

    /**
     * Verifica que getUnread() devuelve solo las notificaciones no leídas.
     *
     * @return void
     */
    public function testGetUnreadDevuelveSoloNoLeidas()
    {
        $fakeUnread = [
            ["id" => 1, "usuario_id" => 5, "leida" => 0],
            ["id" => 3, "usuario_id" => 5, "leida" => 0]
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':usuario_id' => 5]);

        $this->stmt->method('fetchAll')->willReturn($fakeUnread);

        $resultado = $this->notification->getUnread(5);

        $this->assertCount(2, $resultado);
        $this->assertEquals($fakeUnread, $resultado);
    }

    /* ============================================================
       markAsRead()
       ============================================================ */

    /**
     * Verifica que markAsRead() devuelve true cuando execute() funciona.
     *
     * @return void
     */
    public function testMarkAsReadDevuelveTrue()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':id' => 10])
            ->willReturn(true);

        $resultado = $this->notification->markAsRead(10);

        $this->assertTrue($resultado);
    }

    /* ============================================================
       markAllAsRead()
       ============================================================ */

    /**
     * Verifica que markAllAsRead() devuelve true cuando execute() funciona.
     *
     * @return void
     */
    public function testMarkAllAsReadDevuelveTrue()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':usuario_id' => 5])
            ->willReturn(true);

        $resultado = $this->notification->markAllAsRead(5);

        $this->assertTrue($resultado);
    }

    /* ============================================================
       delete()
       ============================================================ */

    /**
     * Verifica que delete() devuelve true cuando execute() funciona.
     *
     * @return void
     */
    public function testDeleteDevuelveTrue()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':id' => 7])
            ->willReturn(true);

        $resultado = $this->notification->delete(7);

        $this->assertTrue($resultado);
    }
}
