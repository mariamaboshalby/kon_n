<?php

declare(strict_types=1);

namespace Modules\Currencies\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\Settings\Models\Currency;
use Modules\Settings\Models\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CurrencyConversionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Currency $defaultCurrency;
    protected Currency $usdCurrency;
    protected Currency $eurCurrency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        // Create currencies
        $this->defaultCurrency = Currency::factory()->create([
            'code' => 'EGP',
            'is_default' => true,
        ]);

        $this->usdCurrency = Currency::factory()->create([
            'code' => 'USD',
            'is_default' => false,
        ]);

        $this->eurCurrency = Currency::factory()->create([
            'code' => 'EUR',
            'is_default' => false,
        ]);

        // Create exchange rates
        ExchangeRate::create([
            'currency_id' => $this->usdCurrency->id,
            'rate' => 30.5,
            'rate_date' => now()->format('Y-m-d'),
        ]);

        ExchangeRate::create([
            'currency_id' => $this->eurCurrency->id,
            'rate' => 33.5,
            'rate_date' => now()->format('Y-m-d'),
        ]);
    }

    public function test_can_convert_currency(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('currencies.convert'), [
                'amount' => 100,
                'from_currency_id' => $this->usdCurrency->id,
                'to_currency_id' => $this->eurCurrency->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'original_amount',
                    'converted_amount',
                    'from_currency',
                    'to_currency',
                    'rate',
                    'rate_date',
                ],
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_can_convert_same_currency(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('currencies.convert'), [
                'amount' => 100,
                'from_currency_id' => $this->usdCurrency->id,
                'to_currency_id' => $this->usdCurrency->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'original_amount' => 100,
                    'converted_amount' => 100,
                    'rate' => 1.0,
                ],
            ]);
    }

    public function test_can_get_exchange_rate(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('currencies.get-rate'), [
                'from_currency_id' => $this->usdCurrency->id,
                'to_currency_id' => $this->eurCurrency->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'rate',
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_can_get_active_currencies(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('currencies.active'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'symbol',
                        'is_default',
                        'rate',
                    ],
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_update_exchange_rate(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('currencies.update-rate'), [
                'currency_id' => $this->usdCurrency->id,
                'rate' => 31.5,
                'rate_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('exchange_rates', [
            'currency_id' => $this->usdCurrency->id,
            'rate' => 31.5,
        ]);
    }

    public function test_can_get_rate_history(): void
    {
        // Create multiple rates
        for ($i = 1; $i <= 5; $i++) {
            ExchangeRate::create([
                'currency_id' => $this->usdCurrency->id,
                'rate' => 30 + $i,
                'rate_date' => now()->subDays($i)->format('Y-m-d'),
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson(route('currencies.rate-history', $this->usdCurrency), [
                'days' => 30,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'currency',
                'history' => [
                    '*' => [
                        'date',
                        'rate',
                    ],
                ],
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_unauthenticated_user_cannot_convert_currency(): void
    {
        $this->postJson(route('currencies.convert'), [
            'amount' => 100,
            'from_currency_id' => $this->usdCurrency->id,
            'to_currency_id' => $this->eurCurrency->id,
        ])->assertStatus(401);
    }
}
