<?php

use App\Services\LogParserService;

beforeEach(function () {
    $this->service = new LogParserService;
});

test('parses nginx log format correctly', function () {
    $logLine = '192.168.1.1 - - [25/Dec/2023:10:00:00 +0000] "GET /test HTTP/1.1" 200 1234 "http://example.com" "Mozilla/5.0"';

    $parsed = $this->service->parse($logLine, 'Apache/Nginx Combined');

    expect($parsed)->not->toBeNull();
    expect($parsed['ip'])->toBe('192.168.1.1');
    expect($parsed['timestamp'])->toBe('25/Dec/2023:10:00:00 +0000');
    expect($parsed['request'])->toBe('GET /test HTTP/1.1');
    expect($parsed['status_code'])->toBe(200);
    expect($parsed['size'])->toBe('1234');
    expect($parsed['referer'])->toBe('http://example.com');
    expect($parsed['user_agent'])->toBe('Mozilla/5.0');
    expect($parsed['raw_line'])->toBe($logLine);
});

test('parses json log format correctly', function () {
    $logLine = '{"timestamp":"2023-12-25T10:00:00Z","level":"info","message":"test","ip":"192.168.1.1"}';

    $parsed = $this->service->parse($logLine, 'JSON');

    expect($parsed)->not->toBeNull();
    expect($parsed['timestamp'])->toBe('2023-12-25T10:00:00Z');
    expect($parsed['level'])->toBe('info');
    expect($parsed['message'])->toBe('test');
    expect($parsed['ip'])->toBe('192.168.1.1');
    expect($parsed['raw_line'])->toBe($logLine);
});

test('parses iso timestamp log correctly', function () {
    $logLine = '2023-12-25T10:00:00+00:00 ERROR: Something went wrong';

    $parsed = $this->service->parse($logLine, 'ISO Timestamp');

    expect($parsed)->not->toBeNull();
    expect($parsed['timestamp'])->toBe('2023-12-25T10:00:00+00:00');
    expect($parsed['message'])->toBe('ERROR: Something went wrong');
    expect($parsed['raw_line'])->toBe($logLine);
});

test('returns null for unknown format', function () {
    $logLine = 'Some random log line';

    $parsed = $this->service->parse($logLine, 'Unknown Format');

    expect($parsed)->toBeNull();
});

test('returns null for malformed nginx log', function () {
    $logLine = 'Not a valid nginx log format';

    $parsed = $this->service->parse($logLine, 'Apache/Nginx Combined');

    expect($parsed)->toBeNull();
});

test('returns null for malformed json', function () {
    $logLine = '{"invalid":"json"';

    $parsed = $this->service->parse($logLine, 'JSON');

    expect($parsed)->toBeNull();
});
