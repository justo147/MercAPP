<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../models/Transaction.php';

/**
 * Clases fake para permitir mocks de PDO y PDOStatement
 */
class FakePDOTransaction extends PDO {}
class FakeStatementTransaction extends PDOStatement {}

class TransactionModelTest extends TestCase
{
    private $pdo;
    private $stmt;
    private $transaction;

    protected function setUp(): void
    {
        $this->stmt        = $this->createMock(FakeStatementTransaction::class);
        $this->pdo         = $this->createMock(FakePDOTransaction::class);
        $this->transaction = new Transaction($this->pdo);
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
                ':producto_id'  => 10,
                ':comprador_id' => 2,
                ':vendedor_id'  => 3,
                ':tipo'         => 'venta',
                ':estado'       => 'pendiente',
                ':precio_final' => 50.0,
                ':dinero_extra' => 0.0
            ])
            ->willReturn(true);

        $resultado = $this->transaction->create(10, 2, 3, 'venta', 'pendiente', 50.0, 0.0);

        $this->assertTrue($resultado);
    }

    public function testCreateDevuelveFalseSiFalla()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(false);

        $resultado = $this->transaction->create(10, 2, 3, 'venta', 'pendiente', 50.0, 0.0);

        $this->assertFalse($resultado);
    }

    /* ============================================================
       getAll()
       ============================================================ */

    public function testGetAllDevuelveLista()
    {
        $fakeTransactions = [
            ["id" => 1, "producto_id" => 10],
            ["id" => 2, "producto_id" => 11]
        ];

        $this->pdo->method('query')->willReturn($this->stmt);
        $this->stmt->method('fetchAll')->willReturn($fakeTransactions);

        $resultado = $this->transaction->getAll();

        $this->assertCount(2, $resultado);
        $this->assertEquals($fakeTransactions, $resultado);
    }

    /* ============================================================
       getByUser()
       ============================================================ */

    public function testGetByUser()
    {
        $fakeTransactions = [
            ["id" => 1, "comprador_id" => 5],
            ["id" => 2, "vendedor_id" => 5]
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':id' => 5]);

        $this->stmt->method('fetchAll')->willReturn($fakeTransactions);

        $resultado = $this->transaction->getByUser(5);

        $this->assertCount(2, $resultado);
    }

    /* ============================================================
       getByProduct()
       ============================================================ */

    public function testGetByProduct()
    {
        $fakeTransactions = [
            ["id" => 1, "producto_id" => 10],
            ["id" => 2, "producto_id" => 10]
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':producto_id' => 10]);

        $this->stmt->method('fetchAll')->willReturn($fakeTransactions);

        $resultado = $this->transaction->getByProduct(10);

        $this->assertCount(2, $resultado);
    }

    /* ============================================================
       updateStatus()
       ============================================================ */

    public function testUpdateStatus()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([
                ':estado' => 'completada',
                ':id'     => 7
            ])
            ->willReturn(true);

        $resultado = $this->transaction->updateStatus(7, 'completada');

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
            ->with([':id' => 9])
            ->willReturn(true);

        $resultado = $this->transaction->delete(9);

        $this->assertTrue($resultado);
    }

    /* ============================================================
       getActiveTransactionByProduct()
       ============================================================ */

    public function testGetActiveTransactionByProduct()
    {
        $fakeTransaction = [
            "id" => 1,
            "producto_id" => 10,
            "estado" => "pendiente"
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':producto_id' => 10]);

        $this->stmt->method('fetch')->willReturn($fakeTransaction);

        $resultado = $this->transaction->getActiveTransactionByProduct(10);

        $this->assertEquals($fakeTransaction, $resultado);
    }

    /* ============================================================
       createFromChat()
       ============================================================ */

    public function testCreateFromChatDevuelveId()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([
                ':producto_id'  => 10,
                ':comprador_id' => 2,
                ':vendedor_id'  => 3
            ]);

        $this->pdo->method('lastInsertId')->willReturn("15");

        $resultado = $this->transaction->createFromChat(10, 2, 3);

        $this->assertSame("15", $resultado);
    }

    /* ============================================================
       getById()
       ============================================================ */

    public function testGetById()
    {
        $fakeTransaction = [
            "id" => 1,
            "producto_id" => 10,
            "estado" => "pendiente"
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':id' => 1]);

        $this->stmt->method('fetch')->willReturn($fakeTransaction);

        $resultado = $this->transaction->getById(1);

        $this->assertEquals($fakeTransaction, $resultado);
    }

    /* ============================================================
       getLastTransactionByProduct()
       ============================================================ */

    public function testGetLastTransactionByProduct()
    {
        $fakeTransaction = [
            "id" => 5,
            "producto_id" => 10,
            "estado" => "completada"
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':producto_id' => 10]);

        $this->stmt->method('fetch')->willReturn($fakeTransaction);

        $resultado = $this->transaction->getLastTransactionByProduct(10);

        $this->assertEquals($fakeTransaction, $resultado);
    }
}
