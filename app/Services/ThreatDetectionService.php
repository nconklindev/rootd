<?php

namespace App\Services;

use App\Services\ThreatDetectors\BruteForceDetector;
use App\Services\ThreatDetectors\DirectoryTraversalDetector;
use App\Services\ThreatDetectors\ErrorPatternDetector;
use App\Services\ThreatDetectors\ScannerActivityDetector;
use App\Services\ThreatDetectors\SqlInjectionDetector;

class ThreatDetectionService
{
    public function __construct(
        private SqlInjectionDetector $sqlInjectionDetector,
        private DirectoryTraversalDetector $directoryTraversalDetector,
        private BruteForceDetector $bruteForceDetector,
        private ScannerActivityDetector $scannerActivityDetector,
        private ErrorPatternDetector $errorPatternDetector
    ) {}

    public function detectThreats(array $parsed, int $lineNumber, array &$bruteForceState = []): array
    {
        $threats = [];

        $threats = array_merge($threats, $this->sqlInjectionDetector->detect($parsed, $lineNumber));
        $threats = array_merge($threats, $this->directoryTraversalDetector->detect($parsed, $lineNumber));
        $threats = array_merge($threats, $this->bruteForceDetector->detect($parsed, $lineNumber, $bruteForceState));
        $threats = array_merge($threats, $this->scannerActivityDetector->detect($parsed, $lineNumber));
        $threats = array_merge($threats, $this->errorPatternDetector->detect($parsed, $lineNumber));

        return $threats;
    }
}
