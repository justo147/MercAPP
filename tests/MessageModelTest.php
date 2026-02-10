<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/Message.php';

/**
 * Clases fake específicas para este archivo de test.
 */
class FakePDOMessage extends PDO {}
class FakeStatementMessage extends PDOStatement {}

class MessageModelTest extends TestCase
{
    private $pdo;
    private $stmt;
    private $message;

    protected function setUp(): void
    {
        $this->stmt = $this->createMock(FakeStatementMessage::class);
        $this->pdo  = $this->createMock(FakePDOMessage::class);

        $this->message = new Message($this->pdo);
    }

    /* ============================================================
       getByChat()
       ============================================================ */

    public function testGetByChatDevuelveMensajes()
    {
        $fakeMessages = [
            [
                "id" => 1,
                "chat_id" => 5,
                "usuario_id" => 2,
                "contenido" => "Hola",
                "sender_name" => "Juan"
            ],
            [
                "id" => 2,
                "chat_id" => 5,
                "usuario_id" => 3,
                "contenido" => "Qué tal",
                "sender_name" => "Pedro"
            ]
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->with([":chat" => 5]);
        $this->stmt->method('fetchAll')->willReturn($fakeMessages);

        $resultado = $this->message->getByChat(5);

        $this->assertCount(2, $resultado);
        $this->assertEquals($fakeMessages, $resultado);
    }

    /* ============================================================
       send()
       ============================================================ */

    public function testSendDevuelveTrue()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([
                ":chat" => 5,
                ":uid" => 2,
                ":content" => "Hola mundo"
            ])
            ->willReturn(true);

        $resultado = $this->message->send(5, 2, "Hola mundo");

        $this->assertTrue($resultado);
    }

    public function testSendDevuelveFalseSiFalla()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->method('execute')->willReturn(false);

        $resultado = $this->message->send(5, 2, "Hola mundo");

        $this->assertFalse($resultado);
    }

    /* ============================================================
       markAsRead()
       ============================================================ */

    public function testMarkAsReadEjecutaUpdateCorrectamente()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([
                ":chat" => 10,
                ":uid"  => 3
            ]);

        $this->message->markAsRead(10, 3);

        $this->assertTrue(true); // Si no lanza excepción, pasa
    }

    /* ============================================================
       countUnread()
       ============================================================ */

    public function testCountUnreadDevuelveNumero()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([
                ":chat" => 10,
                ":uid"  => 3
            ]);

        $this->stmt->method('fetchColumn')->willReturn("4");

        $resultado = $this->message->countUnread(10, 3);

        $this->assertSame("4", $resultado);
    }

    public function testCountUnreadDevuelveCeroSiNoHayMensajes()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute');
        $this->stmt->method('fetchColumn')->willReturn("0");

        $resultado = $this->message->countUnread(10, 3);

        $this->assertSame("0", $resultado);
    }

    /* ============================================================
       countAllUnread()
       ============================================================ */

    public function testCountAllUnreadDevuelveNumero()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([":uid" => 7]);

        $this->stmt->method('fetchColumn')->willReturn("6");

        $resultado = $this->message->countAllUnread(7);

        $this->assertSame("6", $resultado);
    }

    /* ============================================================
       enviarMensajeSistema()
       ============================================================ */

    public function testEnviarMensajeSistemaInsertaCorrectamente()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([
                ":chat"      => 8,
                ":contenido" => "[SISTEMA] Mensaje automático"
            ])
            ->willReturn(true);

        $resultado = $this->message->enviarMensajeSistema(8, "Mensaje automático");

        $this->assertTrue($resultado);
    }

    public function testEnviarMensajeSistemaDevuelveFalseSiFalla()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->method('execute')->willReturn(false);

        $resultado = $this->message->enviarMensajeSistema(8, "Error");

        $this->assertFalse($resultado);
    }
}
