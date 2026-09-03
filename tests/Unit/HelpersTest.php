<?php

// config_path() and public_path() are defined in src/helpers.php as fallback stubs
// for environments where the native Laravel functions are absent.
// In the Testbench environment the Laravel versions are already loaded (same contract),
// so we test the expected behaviour of both the stubs and the native implementations.

it('config_path() returns the application config directory', function () {
    $path = config_path();

    expect($path)->toBeString()
        ->and($path)->toEndWith('config');
});

it('config_path() with a segment appends the segment', function () {
    $path = config_path('app.php');

    expect($path)->toEndWith('app.php')
        ->and($path)->toContain('config');
});

it('config_path() with a nested segment contains the full segment', function () {
    $path = config_path('modules/alpha/config.php');

    expect($path)->toContain('modules/alpha/config.php');
});

it('public_path() returns the application public directory', function () {
    $path = public_path();

    expect($path)->toBeString()
        ->and($path)->toEndWith('public');
});

it('public_path() with a segment appends the segment', function () {
    $path = public_path('index.php');

    expect($path)->toEndWith('index.php')
        ->and($path)->toContain('public');
});
