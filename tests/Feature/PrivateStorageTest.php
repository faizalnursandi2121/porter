<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Subtask 1.7 / FR-48/49: private files unreachable without the app's audited endpoint.

test('local disk root is outside the public root', function () {
    $root = realpath(Storage::disk('local')->path(''));
    $public = realpath(public_path());

    expect($root)->toStartWith(realpath(storage_path()))
        ->and($root)->not->toStartWith($public);
});

test('no serve routes exist for the private disk', function () {
    $serveRoutes = collect(Route::getRoutes())->filter(
        fn ($route) => str_starts_with($route->uri, 'storage/'),
    );

    expect($serveRoutes)->toBeEmpty();
});

test('no public storage symlink exists', function () {
    expect(is_link(public_path('storage')))->toBeFalse()
        ->and(file_exists(public_path('storage')))->toBeFalse();
});

test('private files are not served over http', function () {
    Storage::disk('local')->put('smoke/private-probe.txt', 'secret');

    try {
        $this->get('/storage/smoke/private-probe.txt')->assertNotFound();
    } finally {
        Storage::disk('local')->delete('smoke/private-probe.txt');
    }
});

test('private files read and write through the disk api', function () {
    try {
        Storage::disk('local')->put('smoke/api-probe.txt', 'porter');

        expect(Storage::disk('local')->get('smoke/api-probe.txt'))->toBe('porter');
    } finally {
        Storage::disk('local')->delete('smoke/api-probe.txt');
    }

    expect(Storage::disk('local')->exists('smoke/api-probe.txt'))->toBeFalse();
});
