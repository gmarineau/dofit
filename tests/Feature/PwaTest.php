<?php

it('serves the web app manifest', function () {
    $response = $this->get('/manifest.json')->assertOk();

    $manifest = $response->json();

    expect($manifest['short_name'])->toBe('DoFit')
        ->and($manifest['start_url'])->toBe(url('/'))
        ->and($manifest['display'])->toBe('standalone')
        ->and($manifest['icons'])->not->toBeEmpty();
});

it('serves the offline page to guests', function () {
    $this->get('/offline')
        ->assertOk()
        ->assertSee(__('You are offline'));
});

it('ships every icon the manifest points at', function () {
    $icons = $this->get('/manifest.json')->json('icons');

    foreach ($icons as $icon) {
        expect(public_path($icon['src']))->toBeFile();
    }
});

it('precaches only files that exist in the service worker', function () {
    $serviceWorker = file_get_contents(public_path('serviceworker.js'));

    preg_match('/const PRECACHE_URLS = \[(.*?)\];/s', $serviceWorker, $matches);

    preg_match_all("/'([^']+)'/", $matches[1], $urls);

    // cache.addAll() rejects as a whole if one entry 404s, which would stop
    // the worker from installing at all.
    foreach ($urls[1] as $url) {
        if ($url === '/offline') {
            $this->get($url)->assertOk();

            continue;
        }

        expect(public_path($url))->toBeFile();
    }
});
