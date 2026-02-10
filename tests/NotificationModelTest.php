<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/Notification.php';

/**
 * Clases fake para permitir mocks de PDO y PDOStatement
 */
class FakePDONotification extends PDO {}
class FakeStatementNotification extends PDOStatement {}

class NotificationModelTest extends TestCase
{
    private $pdo;
    private $stmt;
    private $notification;

    protected function setUp(): void
    {
        $this->stmt = $this->createMock(FakeStatementNotification::class);
        $this->pdo  = $this->createMock(FakePDONotification::class);

        $this->notification = new Notification($this->pdo);
    }

    /* ============================================================
       create()
       ============================================================ */

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
