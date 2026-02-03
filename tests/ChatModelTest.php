<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/Chat.php';

/**
 * Clases fake específicas para este archivo de test.
 * Se usan para evitar conflictos con otros tests que también
 * mockean PDO y PDOStatement.
 */
class FakePDOChat extends PDO {}
class FakeStatementChat extends PDOStatement {}

/**
 * Class ChatModelTest
 *
 * Pruebas unitarias del modelo Chat.
 * Se utilizan mocks de PDO y PDOStatement para simular la base de datos.
 */
class ChatModelTest extends TestCase
{
    /**
     * @var FakePDOChat|\PHPUnit\Framework\MockObject\MockObject
     * Mock de la conexión PDO.
     */
    private $pdo;

    /**
     * @var FakeStatementChat|\PHPUnit\Framework\MockObject\MockObject
     * Mock del statement PDO.
     */
    private $stmt;

    /**
     * @var Chat
     * Instancia del modelo a testear.
     */
    private $chat;

    /**
     * Configuración inicial antes de cada test.
     *
     * Se crean los mocks de PDO y PDOStatement y se instancia el modelo.
     */
    protected function setUp(): void
    {
        $this->stmt = $this->createMock(FakeStatementChat::class);
        $this->pdo = $this->createMock(FakePDOChat::class);

        $this->chat = new Chat($this->pdo);
    }

    /* ============================================================
       getById()
       ============================================================ */

    /**
     * Test: getById()
     *
     * Verifica que se obtiene correctamente un chat por su ID.
     * Se mockea fetch() para devolver un array simulado.
     */
    public function testGetById()
    {
        $fakeChat = [
            "id" => 1,
            "producto_id" => 10,
            "usuario_comprador" => 2,
            "usuario_vendedor" => 3,
            "producto_titulo" => "Producto X"
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetch')->willReturn($fakeChat);

        $resultado = $this->chat->getById(1);

        $this->assertEquals($fakeChat, $resultado);
    }

    /* ============================================================
       getExistingChat()
       ============================================================ */

    /**
     * Test: getExistingChat()
     *
     * Verifica que devuelve el ID del chat si existe.
     */
    public function testGetExistingChat()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(5);

        $resultado = $this->chat->getExistingChat(10, 2, 3);

        $this->assertEquals(5, $resultado);
    }

    /**
     * Test: getExistingChat() devuelve false si no existe
     *
     * Verifica que retorna false cuando no se encuentra un chat existente.
     */
    public function testGetExistingChatDevuelveNullSiNoExiste()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(false);

        $resultado = $this->chat->getExistingChat(10, 2, 3);

        $this->assertFalse($resultado);
    }

    /* ============================================================
       create()
       ============================================================ */

    /**
     * Test: create() devuelve ID
     *
     * Verifica que el método create retorna el ID generado
     * cuando la inserción es exitosa.
     */
    public function testCreateDevuelveId()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        // lastInsertId debe devolver string según la firma de PDO
        $this->pdo->method('lastInsertId')->willReturn("7");

        $resultado = $this->chat->create(10, 2, 3);

        $this->assertEquals("7", $resultado);
    }

    /* ============================================================
       getOrCreate()
       ============================================================ */

    /**
     * Test: getOrCreate() devuelve chat existente
     *
     * Verifica que si el chat ya existe, se devuelve su ID sin crear uno nuevo.
     */
    public function testGetOrCreateDevuelveChatExistente()
    {
        $mock = $this->getMockBuilder(Chat::class)
            ->setConstructorArgs([$this->pdo])
            ->onlyMethods(['getExistingChat', 'create'])
            ->getMock();

        $mock->method('getExistingChat')->willReturn(9);

        $resultado = $mock->getOrCreate(10, 2, 3);

        $this->assertEquals(9, $resultado);
    }

    /**
     * Test: getOrCreate() crea chat si no existe
     *
     * Verifica que si no existe un chat previo, se crea uno nuevo.
     */
    public function testGetOrCreateCreaChatSiNoExiste()
    {
        $mock = $this->getMockBuilder(Chat::class)
            ->setConstructorArgs([$this->pdo])
            ->onlyMethods(['getExistingChat', 'create'])
            ->getMock();

        $mock->method('getExistingChat')->willReturn(false);
        $mock->method('create')->willReturn("12");

        $resultado = $mock->getOrCreate(10, 2, 3);

        $this->assertEquals("12", $resultado);
    }

    /* ============================================================
       userBelongsToChat()
       ============================================================ */

    /**
     * Test: userBelongsToChat() devuelve true
     *
     * Verifica que retorna true cuando el usuario pertenece al chat.
     */
    public function testUserBelongsToChatDevuelveTrue()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(1);

        $resultado = $this->chat->userBelongsToChat(5, 2);

        $this->assertTrue($resultado);
    }

    /**
     * Test: userBelongsToChat() devuelve false
     *
     * Verifica que retorna false cuando el usuario NO pertenece al chat.
     */
    public function testUserBelongsToChatDevuelveFalse()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(0);

        $resultado = $this->chat->userBelongsToChat(5, 2);

        $this->assertFalse($resultado);
    }
}
