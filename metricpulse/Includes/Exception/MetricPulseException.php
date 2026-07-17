<?php
declare(strict_types=1);

namespace MetricPulse\Includes\Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Exception class for MetricPulse domain-specific exceptions.
 */
class MetricPulseException extends \Exception {}
