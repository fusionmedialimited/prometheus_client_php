<?php

namespace Prometheus;

use Prometheus\Storage\Adapter;

class Counter extends Collector
{
    const TYPE = 'counter';

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * @param string[] $labels e.g. ['status', 'opcode']
     * @return void
     */
    public function inc(array $labels = [])
    {
        $this->incBy(1, $labels);
    }

    /**
     * @param int|float $count e.g. 2
     * @param mixed[] $labels e.g. ['status', 'opcode']
     * @return void
     */
    public function incBy($count, array $labels = [])
    {
        $this->assertLabelsAreDefinedCorrectly($labels);

        $this->storageAdapter->updateCounter(
            [
                'name' => $this->getName(),
                'help' => $this->getHelp(),
                'type' => $this->getType(),
                'labelNames' => $this->getLabelNames(),
                'labelValues' => $labels,
                'value' => $count,
                'command' => is_float($count) ? Adapter::COMMAND_INCREMENT_FLOAT : Adapter::COMMAND_INCREMENT_INTEGER,
            ]
        );
    }
}
