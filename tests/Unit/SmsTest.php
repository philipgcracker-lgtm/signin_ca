<?php

namespace Tests\Unit;

use App\Models\Agent;
use App\Models\Event;
use App\Services\CheckinMessage;
use App\Services\PhoneNumber;
use App\Sms\SmsException;
use App\Sms\TwilioSmsProvider;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SmsTest extends TestCase
{
    #[DataProvider('phones')]
    public function test_phone_normalization(string $raw, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::toE164($raw));
    }

    public static function phones(): array
    {
        return [
            'formatted' => ['(613) 555-0123', '+16135550123'],
            'dashes' => ['613-555-0123', '+16135550123'],
            'spaces' => ['613 555 0123', '+16135550123'],
            'leading 1' => ['1 613 555 0123', '+16135550123'],
            'plus 1' => ['+1 (613) 555-0123', '+16135550123'],
            'too short' => ['555-0123', null],
            'too long' => ['6135550123999', null],
            'area code starts with 1' => ['113 555 0123', null],
            'exchange starts with 0' => ['613 055 0123', null],
            'foreign' => ['+44 20 7946 0958', null],
            'letters' => ['call me', null],
            'empty' => ['', null],
        ];
    }

    public function test_message_includes_sender_and_stop_line(): void
    {
        $event = new Event(['address_street' => '74 Pinehurst Drive', 'city' => 'Welland']);
        $agent = new Agent(['full_name' => 'Derek Breton', 'brokerage_name' => 'Coldwell Banker Advantage']);

        $body = CheckinMessage::build($event, $agent, 'https://scanin.ca/s/Ab3dEf');

        $this->assertSame(
            'Thanks for visiting 74 Pinehurst Drive, Welland. Listing details: https://scanin.ca/s/Ab3dEf Reply STOP to opt out. -Derek Breton, Coldwell Banker Advantage',
            $body,
        );
        $this->assertLessThanOrEqual(160, mb_strlen($body));
    }

    public function test_long_sender_drops_the_city_but_never_the_brokerage(): void
    {
        $event = new Event(['address_street' => '74 Pinehurst Drive', 'city' => 'Welland']);
        $agent = new Agent([
            'full_name' => 'Derek Breton',
            'brokerage_name' => 'Coldwell Banker Advantage Real Estate, Brokerage',
        ]);

        $body = CheckinMessage::build($event, $agent, 'https://scanin.ca/s/Ab3dEf');

        $this->assertStringNotContainsString('Welland', $body);
        $this->assertStringContainsString('Coldwell Banker Advantage Real Estate, Brokerage', $body);
        $this->assertStringContainsString('Reply STOP to opt out', $body);
    }

    public function test_twilio_provider_posts_to_the_messages_api(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201)]);

        $result = (new TwilioSmsProvider('ACtest', 'secret', '+16135550000'))->send('+16135550123', 'hello');

        $this->assertSame('sent', $result->status);
        $this->assertSame('SM123', $result->providerMessageId);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/ACtest/Messages.json'
                && $request['To'] === '+16135550123'
                && $request['From'] === '+16135550000'
                && $request['Body'] === 'hello'
                && str_starts_with($request->header('Authorization')[0], 'Basic ');
        });
    }

    public function test_twilio_error_becomes_an_sms_exception_without_leaking_the_token(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['code' => 21608, 'message' => 'Unverified number'], 400)]);

        try {
            (new TwilioSmsProvider('ACtest', 'secret-token', '+16135550000'))->send('+16135550123', 'hi');
            $this->fail('expected SmsException');
        } catch (SmsException $e) {
            $this->assertStringContainsString('21608', $e->getMessage());
            $this->assertStringContainsString('Unverified number', $e->getMessage());
            $this->assertStringNotContainsString('secret-token', $e->getMessage());
        }
    }

    public function test_twilio_requires_configuration(): void
    {
        $this->expectException(SmsException::class);

        (new TwilioSmsProvider(null, null, null))->send('+16135550123', 'hi');
    }
}
