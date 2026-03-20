<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/Message.php';

/**
 * Clases fake específicas para permitir mocks de PDO y PDOStatement.
 *
 * Estas clases vacías extienden a PDO y PDOStatement únicamente
 * para que PHPUnit pueda generar mocks sobre ellas, ya que las
 * clases originales no permiten ser mockeadas directamente.
 */
class FakePDOMessage extends PDO {}
class FakeStatementMessage extends PDOStatement {}

/**
 * Test unitarios para el modelo Message.
 *
 * Se validan las operaciones principales del modelo: obtención de mensajes,
 * envío, marcado como leídos y conteo de mensajes no leídos. Se utilizan
 * mocks de PDO y PDOStatement para evitar dependencias reales con la base de datos.
 */
class MessageModelTest extends TestCase
{
    /**
     * @var PDO|FakePDOMessage Mock de la conexión PDO.
     */
    private $pdo;

    /**
     * @var PDOStatement|FakeStatementMessage Mock del statement.
     */
    private $stmt;

    /**
     * @var Message Instancia del modelo a probar.
     */
    private $message;

    /**
     * Configura los mocks antes de cada test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->stmt = $this->createMock(FakeStatementMessage::class);
        $this->pdo  = $this->createMock(FakePDOMessage::class);

        $this->message = new Message($this->pdo);
    }

    /* ============================================================
       getByChat()
       ============================================================ */

    /**
     * Verifica que getByChat() devuelve una lista de mensajes del chat.
     *
     * @return void
     */
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

    /**
     * Verifica que send() devuelve true cuando el mensaje se inserta correctamente.
     *
     * @return void
     */
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

    /**
     * Verifica que send() devuelve false si la inserción falla.
     *
     * @return void
     */
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

    /**
     * Verifica que markAsRead() ejecuta correctamente el UPDATE.
     *
     * @return void
     */
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

    /**
     * Verifica que countUnread() devuelve el número de mensajes no leídos.
     *
     * @return void
     */
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

    /**
     * Verifica que countUnread() devuelve "0" cuando no hay mensajes no leídos.
     *
     * @return void
     */
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

    /**
     * Verifica que countAllUnread() devuelve el total de mensajes no leídos del usuario.
     *
     * @return void
     */
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

    /**
     * Verifica que enviarMensajeSistema() inserta correctamente un mensaje del sistema.
     *
     * @return void
     */
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

    /**
     * Verifica que enviarMensajeSistema() devuelve false si la inserción falla.
     *
     * @return void
     */
    public function testEnviarMensajeSistemaDevuelveFalseSiFalla()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->method('execute')->willReturn(false);

        $resultado = $this->message->enviarMensajeSistema(8, "Error");

        $this->assertFalse($resultado);
    }
}
