<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;
use Monolog\Handler\TestHandler;

/**
 * @return array{sender: string, subject: string, body: string}
 */
function suspectEmail(array $overrides = []): array
{
    return [
        'sender' => 'PayPal Service <service@paypal-konto.example.ru>',
        'subject' => 'Ihr Konto wurde eingeschränkt',
        'body' => 'Bitte bestätigen Sie Ihre Daten: <a href="https://paypal-konto.example.ru/login">https://www.paypal.com</a>',
        ...$overrides,
    ];
}

test('a check shows a learning card with the risk level, signals and next steps', function () {
    $this->post('/check', suspectEmail())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('learningCard.riskLevel', 'gefaehrlich')
            ->whereType('learningCard.summary', 'string')
            ->has('learningCard.signals', 3)
            ->where('learningCard.signals.0.title', 'Linktext und Linkziel passen nicht zusammen')
            ->where('learningCard.signals.0.weight', 'strong')
            ->where('learningCard.signals.0.source', 'rules')
            ->whereType('learningCard.signals.0.explanation', 'string')
            ->where('learningCard.signals.1.title', 'Markenname in fremder Domain')
            ->where('learningCard.signals.1.weight', 'weak')
            ->where('learningCard.signals.2.title', 'Zeitdruck oder Drohung')
            ->where('learningCard.nextSteps', fn ($steps) => collect($steps)->contains(
                'Klicken Sie auf keine Links und öffnen Sie keine Anhänge.',
            ))
            ->where('learningCard.heuristicsOnly', true)
        );
});

test('a suspect email without signals is unauffällig', function () {
    $this->post('/check', suspectEmail([
        'sender' => 'kollegin@example.com',
        'subject' => 'Unterlagen für morgen',
        'body' => 'Hallo, anbei wie besprochen die Unterlagen für das Meeting morgen.',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('learningCard.riskLevel', 'unauffaellig')
            ->has('learningCard.signals', 0)
            ->has('learningCard.nextSteps')
            ->where('learningCard.heuristicsOnly', true)
        );
});

test('the check form is shown without a learning card before a check', function () {
    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->missing('learningCard')
        );
});

test('sender, subject and body are required', function (string $field) {
    $this->post('/check', suspectEmail([$field => '']))
        ->assertSessionHasErrors($field);
})->with(['sender', 'subject', 'body']);

test('the sender must be an email address or a name with an address', function (string $sender, bool $valid) {
    $response = $this->post('/check', suspectEmail(['sender' => $sender]));

    $valid ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors('sender');
})->with([
    'plain address' => ['service@paypal.com', true],
    'name with address' => ['PayPal Service <service@paypal.com>', true],
    'quoted name with address' => ['"Service, PayPal" <service@paypal.com>', true],
    'bare address in angle brackets' => ['<service@paypal.com>', true],
    'name only' => ['PayPal Service', false],
    'address without a domain dot' => ['service@localhost', false],
    'stray angle bracket in the name' => ['PayPal <<service@paypal.com>', false],
    'two addresses' => ['PayPal <a@paypal.com> <b@paypal.com>', false],
    'line break in the name' => ["PayPal\nService <service@paypal.com>", false],
    'name with invalid address' => ['PayPal Service <service-at-paypal.com>', false],
]);

test('the body may be at most 20,000 characters long', function () {
    $this->post('/check', suspectEmail(['body' => str_repeat('a', 20000)]))
        ->assertSessionHasNoErrors();

    $this->post('/check', suspectEmail(['body' => str_repeat('a', 20001)]))
        ->assertSessionHasErrors('body');
});

test('validation errors are in German', function () {
    $this->post('/check', suspectEmail(['subject' => '']))
        ->assertSessionHasErrors(['subject' => 'Bitte geben Sie den Betreff an.']);
});

/**
 * Everything the app could have kept after a request: the session (on the
 * database driver used in production), every database row and the log.
 */
function storedAndLogged(TestHandler $logs): string
{
    $tables = collect(DB::select("select name from sqlite_master where type = 'table'"))
        ->mapWithKeys(fn (object $table) => [$table->name => DB::table($table->name)->get()]);

    return serialize(session()->all()).serialize($tables).serialize($logs->getRecords());
}

describe('privacy', function () {
    uses(RefreshDatabase::class);

    beforeEach(function () {
        config([
            'session.driver' => 'database',
            'logging.default' => 'capture',
            'logging.channels.capture' => ['driver' => 'monolog', 'handler' => TestHandler::class, 'level' => 'debug'],
        ]);
    });

    test('a check neither stores nor logs the suspect email', function (array $email) {
        $this->post('/check', $email);

        /** @var TestHandler $logs */
        $logs = Log::channel('capture')->getLogger()->getHandlers()[0];

        expect(DB::table('sessions')->count())->toBe(1)
            ->and(storedAndLogged($logs))
            ->not->toContain($email['sender'])
            ->not->toContain($email['subject'])
            ->not->toContain($email['body']);
    })->with([
        'successful check' => [suspectEmail()],
        'failed validation' => [suspectEmail(['sender' => 'kein-absender'])],
    ]);
});

test('a client can run at most 10 checks per minute', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->post('/check', suspectEmail())->assertOk();
    }

    $this->post('/check', suspectEmail())->assertTooManyRequests();

    $this->travel(61)->seconds();

    $this->post('/check', suspectEmail())->assertOk();
});
