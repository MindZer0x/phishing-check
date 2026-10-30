<?php

use Inertia\Testing\AssertableInertia as Assert;

test('the home page renders via Inertia', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Home'));
});

test('the home page is German and titled Phishing-Check', function () {
    $this->get('/')
        ->assertSee('<html lang="de"', escape: false)
        ->assertSee('Phishing-Check</title>', escape: false);
});

test('the starter kit authentication pages do not exist', function (string $uri) {
    $this->get($uri)->assertNotFound();
})->with([
    '/login',
    '/register',
    '/forgot-password',
    '/dashboard',
    '/settings/profile',
]);
