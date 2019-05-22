<?php

namespace MDS\Collivery\Orders;

interface ProcessOrder
{
    public function addAddress($params);
    public function addContactAddress($params);
    public function validateCollivery($params);
    public function addCollivery($params);
    public function acceptWaybill($params);
}