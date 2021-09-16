<?php

namespace Prometheus;

class Sample
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $labelNames;

    /**
     * @var mixed[]
     */
    private $labelValues;

    /**
     * @var int|double
     */
    private $value;

    /**
     * Sample constructor.
     * @param mixed[] $data
     */
    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->labelNames = (array) $data['labelNames'];
        $this->labelValues = (array) $data['labelValues'];
        $this->value = $data['value'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed[]
     */
    public function getLabelNames()
    {
        return $this->labelNames;
    }

    /**
     * @return mixed[]
     */
    public function getLabelValues()
    {
        return $this->labelValues;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return (string) $this->value;
    }

    /**
     * @return bool
     */
    public function hasLabelNames()
    {
        return $this->labelNames !== [];
    }
}
