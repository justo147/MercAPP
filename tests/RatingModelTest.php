<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/Rating.php';

/**
 * Clases fake para permitir mocks de PDO y PDOStatement
 */
class FakePDORating extends PDO {}
class FakeStatementRating extends PDOStatement {}

class RatingModelTest extends TestCase
{
    private $pdo;
    private $stmt;
    private $rating;

    protected function setUp(): void
    {
        $this->stmt   = $this->createMock(FakeStatementRating::class);
        $this->pdo    = $this->createMock(FakePDORating::class);
        $this->rating = new Rating($this->pdo);
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
                ':transaccion_id'    => 10,
                ':usuario_valorador' => 2,
                ':usuario_valorado'  => 3,
                ':puntuacion'        => 5,
                ':comentario'        => 'Perfecto',
                ':fiabilidad'        => 5,
                ':comunicacion'      => 4,
                ':puntualidad'       => 5
            ])
            ->willReturn(true);

        $resultado = $this->rating->create(10, 2, 3, 5, 'Perfecto', 5, 4, 5);

        $this->assertTrue($resultado);
    }

    public function testCreateDevuelveFalseSiFalla()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(false);

        $resultado = $this->rating->create(10, 2, 3, 5);

        $this->assertFalse($resultado);
    }

    /* ============================================================
       getAll()
       ============================================================ */

    public function testGetAllDevuelveLista()
    {
        $fakeRatings = [
            ["id" => 1, "puntuacion" => 5],
            ["id" => 2, "puntuacion" => 4]
        ];

        $this->pdo->method('query')->willReturn($this->stmt);
        $this->stmt->method('fetchAll')->willReturn($fakeRatings);

        $resultado = $this->rating->getAll();

        $this->assertCount(2, $resultado);
        $this->assertEquals($fakeRatings, $resultado);
    }

    /* ============================================================
       getReceivedByUser()
       ============================================================ */

    public function testGetReceivedByUser()
    {
        $fakeRatings = [
            ["id" => 1, "usuario_valorado" => 5],
            ["id" => 2, "usuario_valorado" => 5]
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':id' => 5]);

        $this->stmt->method('fetchAll')->willReturn($fakeRatings);

        $resultado = $this->rating->getReceivedByUser(5);

        $this->assertCount(2, $resultado);
    }

    /* ============================================================
       getGivenByUser()
       ============================================================ */

    public function testGetGivenByUser()
    {
        $fakeRatings = [
            ["id" => 1, "usuario_valorador" => 7]
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':id' => 7]);

        $this->stmt->method('fetchAll')->willReturn($fakeRatings);

        $resultado = $this->rating->getGivenByUser(7);

        $this->assertCount(1, $resultado);
    }

    /* ============================================================
       getByTransaction()
       ============================================================ */

    public function testGetByTransaction()
    {
        $fakeRating = [
            "id" => 1,
            "transaccion_id" => 10,
            "puntuacion" => 5
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':id' => 10]);

        $this->stmt->method('fetch')->willReturn($fakeRating);

        $resultado = $this->rating->getByTransaction(10);

        $this->assertEquals($fakeRating, $resultado);
    }

    /* ============================================================
       getAverageScore()
       ============================================================ */

    public function testGetAverageScore()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':id' => 3]);

        $this->stmt->method('fetch')->willReturn(['promedio' => 4.5]);

        $resultado = $this->rating->getAverageScore(3);

        $this->assertEquals(4.5, $resultado);
    }

    public function testGetAverageScoreDevuelveNullSiNoHayDatos()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetch')->willReturn(['promedio' => null]);

        $resultado = $this->rating->getAverageScore(3);

        $this->assertNull($resultado);
    }

    /* ============================================================
       getDetailedAverages()
       ============================================================ */

    public function testGetDetailedAverages()
    {
        $fakeAverages = [
            "promedio_fiabilidad"   => 4.2,
            "promedio_comunicacion" => 4.8,
            "promedio_puntualidad"  => 4.9
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':id' => 9]);

        $this->stmt->method('fetch')->willReturn($fakeAverages);

        $resultado = $this->rating->getDetailedAverages(9);

        $this->assertEquals($fakeAverages, $resultado);
    }

    /* ============================================================
       delete()
       ============================================================ */

    public function testDeleteDevuelveTrue()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':id' => 15])
            ->willReturn(true);

        $resultado = $this->rating->delete(15);

        $this->assertTrue($resultado);
    }
}
