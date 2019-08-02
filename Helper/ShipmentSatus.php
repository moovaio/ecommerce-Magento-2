<?php

namespace Improntus\Moova\Helper;

/**
 * Class ShipmentSatus
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Helper
 */
class ShipmentSatus
{
    /**
     * @var array
     */
    public static $shipmentMessage = [
        'DRAFT'     => 'El envío fue creado.',
        'READY'     => 'El envío se encuentra listo para ser procesado',
        'CONFIRMED' => 'Envío asignado a un Moover.',
        'PICKEDUP'  => 'Envío recogido por el Moover.',
        'INTRANSIT' => 'El envío está en viaje.',
        'DELIVERED' => 'Envío entregado satisfactoriamente.',
        'CANCELED'  => 'Envío cancelado por el usuario.',
        'INCIDENCE' => 'Incidencia inesperada.',
        'RETURNED'  => 'El envío fue devuelto a su lugar de origen.',
    ];

    /**
     * @param $code
     * @return mixed|null
     */
    public static function getShipmentMessage($code)
    {
        return isset(self::$shipmentMessage[$code]) ? __(self::$shipmentMessage[$code]) : null;
    }
}
