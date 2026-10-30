<?php

namespace App\Http\Requests;

use App\Check\SuspectEmail;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class CheckRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sender' => ['required', 'string', 'max:320', $this->senderAddress(...)],
            'subject' => ['required', 'string', 'max:998'],
            'body' => ['required', 'string', 'max:20000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Bitte geben Sie :attribute an.',
            'string' => ':attribute muss Text sein.',
            'max' => ':attribute darf höchstens :max Zeichen lang sein.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sender' => 'den Absender',
            'subject' => 'den Betreff',
            'body' => 'den Text der E-Mail',
        ];
    }

    public function suspectEmail(): SuspectEmail
    {
        return new SuspectEmail(
            sender: $this->string('sender')->toString(),
            subject: $this->string('subject')->toString(),
            body: $this->string('body')->toString(),
        );
    }

    /**
     * Accepts a plain address or the "Name <address>" form of a From header,
     * where the name may be quoted. Addresses need a dotted domain.
     */
    private function senderAddress(string $attribute, mixed $value, Closure $fail): void
    {
        $address = match (true) {
            ! is_string($value) => null,
            preg_match('/^[^\s<>"]+$/', $value) === 1 => $value,
            preg_match('/^\s*(?:"[^"\r\n]*"|[^<>"\r\n]*)\s*<([^<>\s]+)>\s*$/', $value, $matches) === 1 => $matches[1],
            default => null,
        };

        if ($address === null || Validator::make(['address' => $address], ['address' => 'email:filter'])->fails()) {
            $fail('Bitte geben Sie den Absender als E-Mail-Adresse an, zum Beispiel name@example.com oder Name <name@example.com>.');
        }
    }
}
