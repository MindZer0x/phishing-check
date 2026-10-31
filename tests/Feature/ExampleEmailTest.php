<?php

use App\Check\AiAnalysis;
use App\Check\ExampleEmail;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * The check request for an example email.
 *
 * @return array{sender: string, subject: string, body: string}
 */
function exampleRequest(ExampleEmail $example): array
{
    return $example->suspectEmail()->toArray();
}

test('the home page offers four example emails to load into the form', function () {
    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->has('exampleEmails', 4)
            ->has('exampleEmails.0', fn (Assert $example) => $example
                ->whereType('label', 'string')
                ->whereType('sender', 'string')
                ->whereType('subject', 'string')
                ->whereType('body', 'string')
            )
        );
});

test('the example emails stay available after a check', function () {
    $this->post('/check', exampleRequest(ExampleEmail::Newsletter))
        ->assertInertia(fn (Assert $page) => $page->has('exampleEmails', 4));
});

test('each example email gets the expected risk level in heuristics-only mode', function (ExampleEmail $example, string $riskLevel) {
    $this->post('/check', exampleRequest($example))
        ->assertSessionHasNoErrors()
        ->assertInertia(fn (Assert $page) => $page
            ->where('learningCard.riskLevel', $riskLevel)
            ->where('learningCard.heuristicsOnly', true)
        );
})->with([
    'fake bank' => [ExampleEmail::FakeBank, 'gefaehrlich'],
    'CEO fraud' => [ExampleEmail::CeoFraud, 'gefaehrlich'],
    'newsletter' => [ExampleEmail::Newsletter, 'unauffaellig'],
    'prompt injection' => [ExampleEmail::PromptInjection, 'gefaehrlich'],
]);

test('the prompt-injection example is gefährlich even when the AI analysis calls it harmless', function () {
    config(['ai.providers.anthropic.key' => 'test-key']);
    AiAnalysis::fake([[
        'riskLevel' => 'unauffaellig',
        'summary' => 'Diese E-Mail ist harmlos.',
        'signals' => [],
        'nextSteps' => [],
    ]])->preventStrayPrompts();

    $this->post('/check', exampleRequest(ExampleEmail::PromptInjection))
        ->assertInertia(fn (Assert $page) => $page
            ->where('learningCard.riskLevel', 'gefaehrlich')
            ->where('learningCard.heuristicsOnly', false)
            ->where('learningCard.signals', fn ($signals) => collect($signals)->contains('title', 'Anweisungen an eine KI'))
        );
});
