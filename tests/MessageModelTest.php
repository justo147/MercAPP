<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/Message.php';

/**
 * Clases fake específicas para este archivo de test.
 * Se usan para evitar conflictos con otros tests que también
 * mockean PDO y PDOStatement.
 */
class FakePDOMessage extends PDO {}
class FakeStatementMessage extends PDOStatement {}

/**
 * Class MessageModelTest
 *
 * Pruebas unitarias del modelo Message.
 * Se utilizan mocks de PDO y PDOStatement para simular la base de datos.
 */
class MessageModelTest extends TestCase
{
    /**
     * @var FakePDOMessage|\PHPUnit\Framework\MockObject\MockObject
     * Mock de la conexión PDO.
     */
    private $pdo;

    /**
     * @var FakeStatementMessage|\PHPUnit\Framework\MockObject\MockObject
     * Mock del statement PDO.
     */
    private $stmt;

    /**
     * @var Message
     * Instancia del modelo a testear.
     */
    private $message;

    /**
     * Configuración inicial antes de cada test.
     *
     * Se crean los mocks de PDO y PDOStatement y se instancia el modelo.
     */
    protected function setUp(): void
    {
        $this->stmt = $this->createMock(FakeStatementMessage::class);
        $this->pdo = $this->createMock(FakePDOMessage::class);

        $this->message = new Message($this->pdo);
    }

    /* ============================================================
       getByChat()
       ============================================================ */

    /**
     * Test: getByChat()
     *
     * Verifica que se obtienen correctamente los mensajes de un chat.
     * Se mockea fetchAll() para devolver un array simulado.
     */
    public function testGetByChat()
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
        $this->stmt->method('fetchAll')->willReturn($fakeMessages);

        $resultado = $this->message->getByChat(5);

        $this->assertCount(2, $resultado);
        $this->assertEquals($fakeMessages, $resultado);
    }

    /* ============================================================
       send()
       ============================================================ */

    /**
     * Test: send() devuelve true
     *
     * Verifica que el método send retorna true cuando execute()
     * se ejecuta correctamente.
     */
    public function testSendDevuelveTrue()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->message->send(5, 2, "Hola mundo");

        $this->assertTrue($resultado);
    }

    /**
     * Test: send() devuelve false si falla
     *
     * Verifica que el método send retorna false cuando execute()
     * devuelve false, simulando un fallo en la inserción.
     */
    public function testSendDevuelveFalseSiFalla()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(false);

        $resultado = $this->message->send(5, 2, "Hola mundo");

        $this->assertFalse($resultado);
    }
}
