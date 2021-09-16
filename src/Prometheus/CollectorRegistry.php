<?php

namespace Prometheus;

use Prometheus\Exception\MetricNotFoundException;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\Redis;

class CollectorRegistry implements RegistryInterface
{
    /**
     * @var CollectorRegistry
     */
    private static $defaultRegistry;

    /**
     * @var Adapter
     */
    private $storageAdapter;

    /**
     * @var Gauge[]
     */
    private $gauges = [];

    /**
     * @var Counter[]
     */
    private $counters = [];

    /**
     * @var Histogram[]
     */
    private $histograms = [];

    /**
     * @var Summary[]
     */
    private $summaries = [];

    /**
     * @var Gauge[]
     */
    private $defaultGauges = [];

    /**
     * CollectorRegistry constructor.
     *
     * @param Adapter $storageAdapter
     * @param bool $registerDefaultMetrics
     */
    public function __construct(Adapter $storageAdapter, $registerDefaultMetrics = true)
    {
        $registerDefaultMetrics = (bool) $registerDefaultMetrics;
        $this->storageAdapter = $storageAdapter;
        if ($registerDefaultMetrics) {
            $this->registerDefaultMetrics();
        }
    }

    /**
     * @return CollectorRegistry
     */
    public static function getDefault()
    {
        return isset(self::$defaultRegistry) ? self::$defaultRegistry : (self::$defaultRegistry = new self(new Redis()));
    }

    /**
     * @return mixed[]
     */
    public function getMetricFamilySamples()
    {
        return $this->storageAdapter->collect();
    }

    /**
     * @param string $namespace e.g. cms
     * @param string $name e.g. duration_seconds
     * @param string $help e.g. The duration something took in seconds.
     * @param string[] $labels e.g. ['controller', 'action']
     *
     * @return Gauge
     * @throws MetricsRegistrationException
     */
    public function registerGauge($namespace, $name, $help, $labels = [])
    {
        $namespace = (string) $namespace;
        $name = (string) $name;
        $help = (string) $help;
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (isset($this->gauges[$metricIdentifier])) {
            throw new MetricsRegistrationException("Metric already registered");
        }
        $this->gauges[$metricIdentifier] = new Gauge(
            $this->storageAdapter,
            $namespace,
            $name,
            $help,
            $labels
        );
        return $this->gauges[$metricIdentifier];
    }

    /**
     * @param string $namespace
     * @param string $name
     *
     * @return Gauge
     * @throws MetricNotFoundException
     */
    public function getGauge($namespace, $name)
    {
        $namespace = (string) $namespace;
        $name = (string) $name;
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (!isset($this->gauges[$metricIdentifier])) {
            throw new MetricNotFoundException("Metric not found:" . $metricIdentifier);
        }
        return $this->gauges[$metricIdentifier];
    }

    /**
     * @param string $namespace e.g. cms
     * @param string $name e.g. duration_seconds
     * @param string $help e.g. The duration something took in seconds.
     * @param string[] $labels e.g. ['controller', 'action']
     *
     * @return Gauge
     * @throws MetricsRegistrationException
     */
    public function getOrRegisterGauge($namespace, $name, $help, $labels = [])
    {
        $namespace = (string) $namespace;
        $name = (string) $name;
        $help = (string) $help;
        try {
            $gauge = $this->getGauge($namespace, $name);
        } catch (MetricNotFoundException $e) {
            $gauge = $this->registerGauge($namespace, $name, $help, $labels);
        }
        return $gauge;
    }

    /**
     * @param string $namespace e.g. cms
     * @param string $name e.g. requests
     * @param string $help e.g. The number of requests made.
     * @param string[] $labels e.g. ['controller', 'action']
     *
     * @return Counter
     * @throws MetricsRegistrationException
     */
    public function registerCounter($namespace, $name, $help, $labels = [])
    {
        $namespace = (string) $namespace;
        $name = (string) $name;
        $help = (string) $help;
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (isset($this->counters[$metricIdentifier])) {
            throw new MetricsRegistrationException("Metric already registered");
        }
        $this->counters[$metricIdentifier] = new Counter(
            $this->storageAdapter,
            $namespace,
            $name,
            $help,
            $labels
        );
        return $this->counters[self::metricIdentifier($namespace, $name)];
    }

    /**
     * @param string $namespace
     * @param string $name
     *
     * @return Counter
     * @throws MetricNotFoundException
     */
    public function getCounter($namespace, $name)
    {
        $namespace = (string) $namespace;
        $name = (string) $name;
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (!isset($this->counters[$metricIdentifier])) {
            throw new MetricNotFoundException("Metric not found:" . $metricIdentifier);
        }
        return $this->counters[self::metricIdentifier($namespace, $name)];
    }

    /**
     * @param string $namespace e.g. cms
     * @param string $name e.g. requests
     * @param string $help e.g. The number of requests made.
     * @param string[] $labels e.g. ['controller', 'action']
     *
     * @return Counter
     * @throws MetricsRegistrationException
     */
    public function getOrRegisterCounter($namespace, $name, $help, $labels = [])
    {
        $namespace = (string) $namespace;
        $name = (string) $name;
        $help = (string) $help;
        try {
            $counter = $this->getCounter($namespace, $name);
        } catch (MetricNotFoundException $e) {
            $counter = $this->registerCounter($namespace, $name, $help, $labels);
        }
        return $counter;
    }

    /**
     * @param string $namespace e.g. cms
     * @param string $name e.g. duration_seconds
     * @param string $help e.g. A histogram of the duration in seconds.
     * @param string[] $labels e.g. ['controller', 'action']
     * @param mixed[]|null $buckets e.g. [100, 200, 300]
     *
     * @return Histogram
     * @throws MetricsRegistrationException
     */
    public function registerHistogram(
        $namespace,
        $name,
        $help,
        array $labels = [],
        array $buckets = null
    ) {
        $namespace = (string) $namespace;
        $name = (string) $name;
        $help = (string) $help;
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (isset($this->histograms[$metricIdentifier])) {
            throw new MetricsRegistrationException("Metric already registered");
        }
        $this->histograms[$metricIdentifier] = new Histogram(
            $this->storageAdapter,
            $namespace,
            $name,
            $help,
            $labels,
            $buckets
        );
        return $this->histograms[$metricIdentifier];
    }

    /**
     * @param string $namespace
     * @param string $name
     *
     * @return Histogram
     * @throws MetricNotFoundException
     */
    public function getHistogram($namespace, $name)
    {
        $namespace = (string) $namespace;
        $name = (string) $name;
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (!isset($this->histograms[$metricIdentifier])) {
            throw new MetricNotFoundException("Metric not found:" . $metricIdentifier);
        }
        return $this->histograms[self::metricIdentifier($namespace, $name)];
    }

    /**
     * @param string $namespace e.g. cms
     * @param string $name e.g. duration_seconds
     * @param string $help e.g. A histogram of the duration in seconds.
     * @param string[] $labels e.g. ['controller', 'action']
     * @param float[]|null $buckets e.g. [100, 200, 300]
     *
     * @return Histogram
     * @throws MetricsRegistrationException
     */
    public function getOrRegisterHistogram(
        $namespace,
        $name,
        $help,
        array $labels = [],
        array $buckets = null
    ) {
        $namespace = (string) $namespace;
        $name = (string) $name;
        $help = (string) $help;
        try {
            $histogram = $this->getHistogram($namespace, $name);
        } catch (MetricNotFoundException $e) {
            $histogram = $this->registerHistogram($namespace, $name, $help, $labels, $buckets);
        }
        return $histogram;
    }


    /**
     * @param string $namespace e.g. cms
     * @param string $name e.g. duration_seconds
     * @param string $help e.g. A summary of the duration in seconds.
     * @param string[] $labels e.g. ['controller', 'action']
     * @param int $maxAgeSeconds e.g. 604800
     * @param float[]|null $quantiles e.g. [0.01, 0.5, 0.99]
     *
     * @return Summary
     * @throws MetricsRegistrationException
     */
    public function registerSummary(
        $namespace,
        $name,
        $help,
        array $labels = [],
        $maxAgeSeconds = 600,
        array $quantiles = null
    ) {
        $namespace = (string) $namespace;
        $name = (string) $name;
        $help = (string) $help;
        $maxAgeSeconds = (int) $maxAgeSeconds;
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (isset($this->summaries[$metricIdentifier])) {
            throw new MetricsRegistrationException("Metric already registered");
        }
        $this->summaries[$metricIdentifier] = new Summary(
            $this->storageAdapter,
            $namespace,
            $name,
            $help,
            $labels,
            $maxAgeSeconds,
            $quantiles
        );
        return $this->summaries[$metricIdentifier];
    }

    /**
     * @param string $namespace
     * @param string $name
     *
     * @return Summary
     * @throws MetricNotFoundException
     */
    public function getSummary($namespace, $name)
    {
        $namespace = (string) $namespace;
        $name = (string) $name;
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (!isset($this->summaries[$metricIdentifier])) {
            throw new MetricNotFoundException("Metric not found:" . $metricIdentifier);
        }
        return $this->summaries[self::metricIdentifier($namespace, $name)];
    }

    /**
     * @param string $namespace e.g. cms
     * @param string $name e.g. duration_seconds
     * @param string $help e.g. A summary of the duration in seconds.
     * @param string[] $labels e.g. ['controller', 'action']
     * @param int $maxAgeSeconds e.g. 604800
     * @param float[]|null $quantiles e.g. [0.01, 0.5, 0.99]
     *
     * @return Summary
     * @throws MetricsRegistrationException
     */
    public function getOrRegisterSummary(
        $namespace,
        $name,
        $help,
        array $labels = [],
        $maxAgeSeconds = 600,
        array $quantiles = null
    ) {
        $namespace = (string) $namespace;
        $name = (string) $name;
        $help = (string) $help;
        $maxAgeSeconds = (int) $maxAgeSeconds;
        try {
            $summary = $this->getSummary($namespace, $name);
        } catch (MetricNotFoundException $e) {
            $summary = $this->registerSummary($namespace, $name, $help, $labels, $maxAgeSeconds, $quantiles);
        }
        return $summary;
    }

    /**
     * @param string $namespace
     * @param string $name
     *
     * @return string
     */
    private static function metricIdentifier($namespace, $name)
    {
        $namespace = (string) $namespace;
        $name = (string) $name;
        return $namespace . ":" . $name;
    }

    /**
     * @return void
     */
    private function registerDefaultMetrics()
    {
        $this->defaultGauges['php_info_gauge'] = $this->getOrRegisterGauge(
            "",
            "php_info",
            "Information about the PHP environment.",
            ["version"]
        );
        $this->defaultGauges['php_info_gauge']->set(1, [PHP_VERSION]);
    }
}
