<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/Product.php';

/**
 * Clases fake para este archivo de test.
 * Se usan para evitar conflictos con otros tests que también mockean PDO.
 */
class FakePDO extends PDO {}
class FakeStatement extends PDOStatement {}

/**
 * Class ProductModelTest
 *
 * Pruebas unitarias del modelo Product.
 * Se utilizan mocks de PDO y PDOStatement para simular la base de datos.
 */
class ProductModelTest extends TestCase
{
    /**
     * @var FakePDO|\PHPUnit\Framework\MockObject\MockObject
     * Mock de la conexión PDO.
     */
    private $pdo;

    /**
     * @var FakeStatement|\PHPUnit\Framework\MockObject\MockObject
     * Mock del statement PDO.
     */
    private $stmt;

    /**
     * @var Product
     * Instancia del modelo a testear.
     */
    private $product;

    /**
     * Configuración inicial antes de cada test.
     *
     * Se crean los mocks de PDO y PDOStatement y se instancia el modelo.
     */
    protected function setUp(): void
    {
        $this->stmt = $this->createMock(FakeStatement::class);
        $this->pdo = $this->createMock(FakePDO::class);

        $this->product = new Product($this->pdo);
    }

    /* ============================================================
       getPaginated()
       ============================================================ */

    /**
     * Test: getPaginated()
     *
     * Verifica que se obtienen productos paginados y que se adjuntan imágenes.
     */
   public function testGetPaginated()
{
    $fakeProducts = [
        ["id" => 1, "titulo" => "Producto 1"],
        ["id" => 2, "titulo" => "Producto 2"]
    ];

    $this->pdo->method('prepare')->willReturn($this->stmt);
    $this->stmt->method('fetchAll')->willReturn($fakeProducts);

    /** @var Product|\PHPUnit\Framework\MockObject\MockObject $productMock */
    $productMock = $this->getMockBuilder(Product::class)
        ->setConstructorArgs([$this->pdo])
        ->onlyMethods(['getImages'])
        ->getMock();

    $productMock->method('getImages')->willReturn([]);

    $resultado = $productMock->getPaginated(10, 0);

    $this->assertCount(2, $resultado);
}


    /* ============================================================
       getImages()
       ============================================================ */

    /**
     * Test: getImages()
     *
     * Verifica que se obtienen correctamente las imágenes de un producto.
     */
    public function testGetImages()
    {
        $fakeImages = [
            ["url" => "img1.jpg", "orden" => 1],
            ["url" => "img2.jpg", "orden" => 2]
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchAll')->willReturn($fakeImages);

        $resultado = $this->product->getImages(1);

        $this->assertEquals($fakeImages, $resultado);
    }

    /* ============================================================
       getById()
       ============================================================ */

    /**
     * Test: getById()
     *
     * Verifica que se obtiene un producto por ID y que incluye imágenes.
     */
    public function testGetById()
    {
        $fakeProduct = [
            "id" => 1,
            "titulo" => "Producto X"
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetch')->willReturn($fakeProduct);

        $mock = $this->getMockBuilder(Product::class)
            ->setConstructorArgs([$this->pdo])
            ->onlyMethods(['getImages'])
            ->getMock();

        $mock->method('getImages')->willReturn([]);

        $resultado = $mock->getById(1);

        $this->assertArrayHasKey("imagenes", $resultado);
    }

    /* ============================================================
       getByUserPaginated()
       ============================================================ */

    /**
     * Test: getByUserPaginated()
     *
     * Verifica que se obtienen productos de un usuario con paginación.
     */
    public function testGetByUserPaginated()
    {
        $fakeProducts = [
            ["id" => 1, "titulo" => "Prod 1"],
            ["id" => 2, "titulo" => "Prod 2"]
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchAll')->willReturn($fakeProducts);

        $mock = $this->getMockBuilder(Product::class)
            ->setConstructorArgs([$this->pdo])
            ->onlyMethods(['getImages'])
            ->getMock();

        $mock->method('getImages')->willReturn([]);

        $resultado = $mock->getByUserPaginated(1, 10, 0);

        $this->assertCount(2, $resultado);
    }

    /* ============================================================
       create()
       ============================================================ */

    /**
     * Test: create() devuelve ID
     *
     * Verifica que el método create retorna el ID generado.
     */
    public function testCreateDevuelveId()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);
        $this->pdo->method('lastInsertId')->willReturn("5");

        $data = [
            "usuario_id" => 1,
            "categoria_id" => 2,
            "titulo" => "Nuevo",
            "descripcion" => "Desc",
            "precio" => 10,
            "estado_producto_id" => 1,
            "tipo_transaccion" => "venta",
            "estado_publicacion_id" => 1,
            "ubicacion" => "Madrid"
        ];

        $resultado = $this->product->create($data);

        $this->assertEquals(5, $resultado);
    }

    /**
     * Test: create() devuelve false si falla
     *
     * Verifica que el método retorna false cuando ocurre una excepción.
     */
    public function testCreateDevuelveFalseSiFalla()
    {
        $this->pdo->method('prepare')->willThrowException(new PDOException("Error"));

        $resultado = $this->product->create([]);

        $this->assertFalse($resultado);
    }

    /* ============================================================
       update()
       ============================================================ */

    /**
     * Test: update() devuelve true
     *
     * Verifica que update retorna true cuando execute() es exitoso.
     */
    public function testUpdateDevuelveTrue()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->product->update(1, [
            "categoria_id" => 1,
            "titulo" => "Nuevo",
            "descripcion" => "Desc",
            "precio" => 10,
            "estado_producto_id" => 1,
            "tipo_transaccion" => "venta",
            "estado_publicacion_id" => 1,
            "ubicacion" => "Madrid"
        ]);

        $this->assertTrue($resultado);
    }

    /**
     * Test: update() devuelve false si falla
     *
     * Verifica que update retorna false cuando ocurre una excepción.
     */
    public function testUpdateDevuelveFalseSiFalla()
    {
        $this->pdo->method('prepare')->willThrowException(new PDOException("Error"));

        $resultado = $this->product->update(1, []);

        $this->assertFalse($resultado);
    }

    /* ============================================================
       delete()
       ============================================================ */

    /**
     * Test: delete() devuelve true
     *
     * Verifica que delete retorna true cuando execute() es exitoso.
     */
    public function testDeleteDevuelveTrue()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->product->delete(1);

        $this->assertTrue($resultado);
    }

    /* ============================================================
       countByUser()
       ============================================================ */

    /**
     * Test: countByUser()
     *
     * Verifica que retorna el número de productos del usuario.
     */
    public function testCountByUser()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(7);

        $resultado = $this->product->countByUser(1);

        $this->assertEquals(7, $resultado);
    }

    /* ============================================================
       search()
       ============================================================ */

    /**
     * Test: search() devuelve productos
     *
     * Verifica que search retorna una lista de productos filtrados.
     */
    public function testSearchDevuelveProductos()
    {
        $fakeProducts = [
            ["id" => 1, "titulo" => "Prod 1"],
            ["id" => 2, "titulo" => "Prod 2"]
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchAll')->willReturn($fakeProducts);

        $mock = $this->getMockBuilder(Product::class)
            ->setConstructorArgs([$this->pdo])
            ->onlyMethods(['getImages'])
            ->getMock();

        $mock->method('getImages')->willReturn([]);

        $resultado = $mock->search([
            "limit" => 10,
            "offset" => 0
        ]);

        $this->assertCount(2, $resultado);
    }

    /* ============================================================
       getImagesByProduct()
       ============================================================ */

    /**
     * Test: getImagesByProduct()
     *
     * Verifica que se obtienen las imágenes ordenadas de un producto.
     */
    public function testGetImagesByProduct()
    {
        $fakeImages = [
            ["id" => 1, "url" => "img1.jpg", "orden" => 1]
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchAll')->willReturn($fakeImages);

        $resultado = $this->product->getImagesByProduct(1);

        $this->assertEquals($fakeImages, $resultado);
    }

    /* ============================================================
       insertImage()
       ============================================================ */

    /**
     * Test: insertImage()
     *
     * Verifica que la inserción de una imagen retorna true.
     */
    public function testInsertImage()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->product->insertImage(1, "img.jpg", 1);

        $this->assertTrue($resultado);
    }

    /* ============================================================
       deleteImage()
       ============================================================ */

    /**
     * Test: deleteImage()
     *
     * Verifica que la eliminación de una imagen retorna true.
     */
    public function testDeleteImage()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->product->deleteImage(1, "img.jpg");

        $this->assertTrue($resultado);
    }

    /* ============================================================
       updateImageOrder()
       ============================================================ */

    /**
     * Test: updateImageOrder()
     *
     * Verifica que actualizar el orden de una imagen retorna true.
     */
    public function testUpdateImageOrder()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);

        $resultado = $this->product->updateImageOrder(1, 3);

        $this->assertTrue($resultado);
    }

        /* ============================================================
       getRandomProducts()
       ============================================================ */

    public function testGetRandomProducts()
    {
        $fakeProducts = [
            ["id" => 1, "titulo" => "Prod 1"],
            ["id" => 2, "titulo" => "Prod 2"]
        ];

        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchAll')->willReturn($fakeProducts);

        $mock = $this->getMockBuilder(Product::class)
            ->setConstructorArgs([$this->pdo])
            ->onlyMethods(['getImages'])
            ->getMock();

        $mock->method('getImages')->willReturn([]);

        $resultado = $mock->getRandomProducts(5, 10);

        $this->assertCount(2, $resultado);
    }

    /* ============================================================
       cambiarEstadoPublicacion()
       ============================================================ */

    public function testCambiarEstadoPublicacion()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([
                ':estado' => 'activo',
                ':id'     => 5
            ])
            ->willReturn(true);

        $resultado = $this->product->cambiarEstadoPublicacion(5, 'activo');

        $this->assertTrue($resultado);
    }

    /* ============================================================
       reservarProducto()
       ============================================================ */

    public function testReservarProducto()
    {
        $mock = $this->getMockBuilder(Product::class)
            ->setConstructorArgs([$this->pdo])
            ->onlyMethods(['cambiarEstadoPublicacion'])
            ->getMock();

        $mock->expects($this->once())
            ->method('cambiarEstadoPublicacion')
            ->with(7, 'pausado')
            ->willReturn(true);

        $resultado = $mock->reservarProducto(7);

        $this->assertTrue($resultado);
    }

    /* ============================================================
       marcarComoVendido()
       ============================================================ */

    public function testMarcarComoVendido()
    {
        $mock = $this->getMockBuilder(Product::class)
            ->setConstructorArgs([$this->pdo])
            ->onlyMethods(['cambiarEstadoPublicacion'])
            ->getMock();

        $mock->expects($this->once())
            ->method('cambiarEstadoPublicacion')
            ->with(3, 'vendido')
            ->willReturn(true);

        $resultado = $mock->marcarComoVendido(3);

        $this->assertTrue($resultado);
    }

    /* ============================================================
       reactivarPublicacion()
       ============================================================ */

    public function testReactivarPublicacion()
    {
        $mock = $this->getMockBuilder(Product::class)
            ->setConstructorArgs([$this->pdo])
            ->onlyMethods(['cambiarEstadoPublicacion'])
            ->getMock();

        $mock->expects($this->once())
            ->method('cambiarEstadoPublicacion')
            ->with(9, 'activo')
            ->willReturn(true);

        $resultado = $mock->reactivarPublicacion(9);

        $this->assertTrue($resultado);
    }

    /* ============================================================
       deleteWithImages()
       ============================================================ */

    public function testDeleteWithImages()
{
    $fakeImages = [
        ["url" => "uploads/img1.jpg"],
        ["url" => "uploads/img2.jpg"]
    ];

    $mock = $this->getMockBuilder(Product::class)
        ->setConstructorArgs([$this->pdo])
        ->onlyMethods(['getImages'])
        ->getMock();

    $mock->method('getImages')->willReturn($fakeImages);

    $this->pdo->method('prepare')->willReturn($this->stmt);
    $this->stmt->method('execute')->willReturn(true);

    $resultado = $mock->deleteWithImages(1);

    $this->assertTrue($resultado);
}


}
