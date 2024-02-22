<?php

namespace Haoa\CacheHub;

use Haoa\CacheHub\Driver\BaseDriver;
use Haoa\CacheHub\Exception\Exception;
use Haoa\CacheHub\Serializer\SerializerInterface;

class Container
{

    protected $driverObjs = [];


    protected $serializerObjs = [];

    protected ?LoggerInterface $logger = null;


    public function getDriver($class, $serializerClass, $handler = null): BaseDriver
    {
        if (!isset($this->driverObjs[$class])) {
            $this->driverObjs[$class] = new $class;
            if (!$this->driverObjs[$class] instanceof BaseDriver) {
                throw new Exception("driver[{$class}] must be of type " . BaseDriver::class);
            }
            $this->driverObjs[$class]->setHandler($handler);
            $this->driverObjs[$class]->setSerializer($this->getSerializer($serializerClass));
        }
        // throw new Exception("driver[$class] is not exists");
        return $this->driverObjs[$class];
    }


    public function getSerializer($class): SerializerInterface
    {
        if (!isset($this->serializerObjs[$class])) {
            $this->serializerObjs[$class] = new $class;
            if (!$this->serializerObjs[$class] instanceof SerializerInterface) {
                throw new Exception("serializer[{$class}] must be of type " . SerializerInterface::class);
            }
        }
        return $this->serializerObjs[$class];
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

}