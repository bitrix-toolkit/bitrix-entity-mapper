<?php

namespace Sheerockoff\BitrixEntityMapper\Query;

use InvalidArgumentException;

class RawResult
{
    /** @var int */
    protected $id;

    /** @var int */
    protected $infoBlockId;

    /** @var array */
    protected $data = [];

    /**
     * RawResult constructor.
     * @param int $id
     * @param int $infoBlockId
     * @param array $data
     */
    public function __construct($id, $infoBlockId, array $data)
    {
        $this->id = $id;
        $this->infoBlockId = $infoBlockId;
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getInfoBlockId()
    {
        return $this->infoBlockId;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $code
     * @return mixed
     */
    public function getField($code)
    {
        if (!array_key_exists($code, $this->data)) {
            throw new InvalidArgumentException("Поле $code не найдено в массиве данных.");
        }

        return $this->data[$code];
    }
}