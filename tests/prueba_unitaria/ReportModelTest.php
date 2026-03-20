<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/Report.php';

/**
 * Clases fake para permitir mocks de PDO y PDOStatement
 */
class FakePDOReport extends PDO {}
class FakeStatementReport extends PDOStatement {}

class ReportModelTest extends TestCase
{
    private $pdo;
    private $stmt;
    private $report;

    protected function setUp(): void
    {
        $this->stmt   = $this->createMock(FakeStatementReport::class);
        $this->pdo    = $this->createMock(FakePDOReport::class);
        $this->report = new Report($this->pdo);
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
                ':usuario_reportador' => 5,
                ':producto_id'        => 10,
                ':motivo'             => 'Contenido inapropiado'
            ])
            ->willReturn(true);

        $resultado = $this->report->create(5, 10, 'Contenido inapropiado');

        $this->assertTrue($resultado);
    }

    public function testCreateDevuelveFalseSiFalla()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(false);

        $resultado = $this->report->create(5, 10, 'Error');

        $this->assertFalse($resultado);
    }

    /* ============================================================
       getAll()
       ============================================================ */

    public function testGetAllDevuelveLista()
    {
        $fakeReports = [
            ["id" => 1, "motivo" => "Spam"],
            ["id" => 2, "motivo" => "Estafa"]
        ];

        $this->pdo->method('query')->willReturn($this->stmt);
        $this->stmt->method('fetchAll')->willReturn($fakeReports);

        $resultado = $this->report->getAll();

        $this->assertCount(2, $resultado);
        $this->assertEquals($fakeReports, $resultado);
    }

    /* ============================================================
       getByProduct()
       ============================================================ */

    public function testGetByProduct()
    {
        $fakeReports = [
            ["id" => 1, "producto_id" => 10],
            ["id" => 2, "producto_id" => 10]
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':producto_id' => 10]);

        $this->stmt->method('fetchAll')->willReturn($fakeReports);

        $resultado = $this->report->getByProduct(10);

        $this->assertCount(2, $resultado);
    }

    /* ============================================================
       getByUser()
       ============================================================ */

    public function testGetByUser()
    {
        $fakeReports = [
            ["id" => 1, "usuario_reportador" => 7],
            ["id" => 2, "usuario_reportador" => 7]
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':usuario_id' => 7]);

        $this->stmt->method('fetchAll')->willReturn($fakeReports);

        $resultado = $this->report->getByUser(7);

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
                ':estado'   => 'revisado',
                ':admin_id' => 3,
                ':id'       => 15
            ])
            ->willReturn(true);

        $resultado = $this->report->updateStatus(15, 'revisado', 3);

        $this->assertTrue($resultado);
    }

    public function testUpdateStatusSinAdmin()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([
                ':estado'   => 'pendiente',
                ':admin_id' => null,
                ':id'       => 20
            ])
            ->willReturn(true);

        $resultado = $this->report->updateStatus(20, 'pendiente');

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

        $resultado = $this->report->delete(9);

        $this->assertTrue($resultado);
    }
}
