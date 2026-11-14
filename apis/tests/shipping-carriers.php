<?php

require __DIR__ . '/../src/Repositories/ShippingCarrierRepository.php';
require __DIR__ . '/../src/Services/ShippingCarrierService.php';

class ShippingCarrierFixture extends \App\Repositories\ShippingCarrierRepository
{
    public function __construct() {}
    public function listActive()
    {
        return array(
            array('id' => '4', 'nombre' => ' Entrega personal ', 'envio' => '0', 'costo' => '100.00'),
            array('id' => '8', 'nombre' => 'CASTORES', 'envio' => '1', 'vendemos' => '0'),
        );
    }
}
$result = (new \App\Services\ShippingCarrierService(new ShippingCarrierFixture()))->listing();
$expected = array('metodos_entrega' => array(
    array('id' => 4, 'nombre' => 'Entrega personal', 'envio' => 0),
    array('id' => 8, 'nombre' => 'CASTORES', 'envio' => 1),
));
if ($result !== $expected) throw new \RuntimeException('Carrier IDs, types and public fields must be preserved');
echo "Delivery method service checks passed.\n";
