<?php

use App\Services\LogFormatDetectionService;

beforeEach(function () {
    $this->service = new LogFormatDetectionService;
});

test('detects apache nginx combined format', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'test_log');
    file_put_contents($tempFile, '192.168.1.1 - - [25/Dec/2023:10:00:00 +0000] "GET /test HTTP/1.1" 200 1234 "-" "Mozilla/5.0"');

    $format = $this->service->detectFormat($tempFile);

    expect($format)->toBe('Apache/Nginx Combined');

    unlink($tempFile);
});

test('detects iso timestamp format', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'test_log');
    file_put_contents($tempFile, '2023-12-25T10:00:00+00:00 INFO: Test log message');

    $format = $this->service->detectFormat($tempFile);

    expect($format)->toBe('ISO Timestamp');

    unlink($tempFile);
});

test('detects json format', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'test_log');
    file_put_contents($tempFile, '{"timestamp":"2023-12-25T10:00:00Z","level":"info","message":"test"}');

    $format = $this->service->detectFormat($tempFile);

    expect($format)->toBe('JSON');

    unlink($tempFile);
});

test('returns unknown for unrecognized format', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'test_log');
    file_put_contents($tempFile, 'Some random log format that does not match any pattern');

    $format = $this->service->detectFormat($tempFile);

    expect($format)->toBe('Unknown');

    unlink($tempFile);
});
