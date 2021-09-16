<?php

namespace Prometheus;

class MetricFamilySamples
{
    /**
     * @var mixed
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $help;

    /**
     * @var string[]
     */
    private $labelNames;

    /**
     * @var Sample[]
     */
    private $samples = [];

    /**
     * @param mixed[] $data
     */
    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->type = $data['type'];
        $this->help = $data['help'];
        $this->labelNames = $data['labelNames'];
        foreach ($data['samples'] as $sampleData) {
            $this->samples[] = new Sample($sampleData);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        return $this->help;
    }

    /**
     * @return mixed[]
     */
    public function getSamples()
    {
        return $this->samples;
    }

    /**
     * @return mixed[]
     */
    public function getLabelNames()
    {
        return $this->labelNames;
    }

    /**
     * @return bool
     */
    public function hasLabelNames()
    {
        return $this->labelNames !== [];
    }
}
