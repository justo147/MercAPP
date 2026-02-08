<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/Chat.php';

/**
 * Clases fake para permitir mocks de PDO y PDOStatement
 */
class FakePDOChat extends PDO {}
class FakeStatementChat extends PDOStatement {}

class ChatModelTest extends TestCase
{
    private $pdo;
    private $stmt;
    private $chat;

    protected function setUp(): void
    {
        $this->stmt = $this->createMock(FakeStatementChat::class);
        $this->pdo  = $this->createMock(FakePDOChat::class);

        $this->chat = new Chat($this->pdo);
    }

    /* ============================================================
       getById()
       ============================================================ */

    public function testGetByIdDevuelveChat()
    {
        $chatId = 1;

        $fakeChat = [
            "id" => "1",
            "producto_id" => "10",
            "usuario_comprador" => "2",
            "usuario_vendedor" => "3",
            "producto_titulo" => "Producto X",
            "producto_imagen" => "img.jpg"
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("WHERE c.id = :id"))
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([":id" => $chatId]);

        $this->stmt->method('fetch')->willReturn($fakeChat);

        $resultado = $this->chat->getById($chatId);

        $this->assertEquals($fakeChat, $resultado);
    }

    public function testGetByIdDevuelveFalseSiNoExiste()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute');
        $this->stmt->method('fetch')->willReturn(false);

        $resultado = $this->chat->getById(999);

        $this->assertFalse($resultado);
    }

    /* ============================================================
       getOrCreate()
       ============================================================ */

    public function testGetOrCreateCreaChatSiNoExiste()
    {
        $chatMock = $this->getMockBuilder(Chat::class)
            ->setConstructorArgs([$this->pdo])
            ->onlyMethods(['getExistingChat', 'create'])
            ->getMock();

        $chatMock->expects($this->once())
            ->method('getExistingChat')
            ->with(10, 2, 3)
            ->willReturn(false);

        $chatMock->expects($this->once())
            ->method('create')
            ->with(10, 2, 3)
            ->willReturn("12");

        $resultado = $chatMock->getOrCreate(10, 2, 3);

        $this->assertSame("12", $resultado);
    }

    public function testGetOrCreateDevuelveExistente()
    {
        $chatMock = $this->getMockBuilder(Chat::class)
            ->setConstructorArgs([$this->pdo])
            ->onlyMethods(['getExistingChat'])
            ->getMock();

        $chatMock->expects($this->once())
            ->method('getExistingChat')
            ->with(10, 2, 3)
            ->willReturn("7");

        $resultado = $chatMock->getOrCreate(10, 2, 3);

        $this->assertSame("7", $resultado);
    }

    /* ============================================================
       userBelongsToChat()
       ============================================================ */

    public function testUserBelongsToChatDevuelveTrue()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn("1");

        $resultado = $this->chat->userBelongsToChat(5, 2);

        $this->assertTrue($resultado);
    }

    public function testUserBelongsToChatDevuelveFalse()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn("0");

        $resultado = $this->chat->userBelongsToChat(5, 2);

        $this->assertFalse($resultado);
    }

    /* ============================================================
       getChatsByUser()
       ============================================================ */

    public function testGetChatsByUserDevuelveLista()
    {
        $userId = 99;

        $fakeChats = [
            [
                "chat_id" => "1",
                "producto_id" => "10",
                "producto_titulo" => "Bicicleta",
                "producto_imagen" => "img.jpg",
                "ultimo_mensaje" => "Hola",
                "fecha_ultimo_mensaje" => "2024-01-01 10:00:00",
                "no_leidos" => "2"
            ]
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("c.usuario_comprador = :uid OR c.usuario_vendedor = :uid"))
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([":uid" => $userId]);

        $this->stmt->method('fetchAll')->willReturn($fakeChats);

        $resultado = $this->chat->getChatsByUser($userId);

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
        $this->assertSame("Bicicleta", $resultado[0]["producto_titulo"]);
    }

    public function testGetChatsByUserDevuelveArrayVacio()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute');
        $this->stmt->method('fetchAll')->willReturn([]);

        $resultado = $this->chat->getChatsByUser(99);

        $this->assertIsArray($resultado);
        $this->assertCount(0, $resultado);
    }

    /* ============================================================
       create()
       ============================================================ */

    public function testCreateRetornaNuevoId()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([
                ":pid"       => 10,
                ":comprador" => 2,
                ":vendedor"  => 3
            ]);

        $this->pdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("15");

        $resultado = $this->chat->create(10, 2, 3);

        $this->assertSame("15", $resultado);
    }
}
