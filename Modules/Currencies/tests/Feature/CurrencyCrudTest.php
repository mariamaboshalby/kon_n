<?php

declare(strict_types=1);

namespace Modules\Currencies\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\Settings\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CurrencyCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_view_currencies_list(): void
    {
        $this->actingAs($this->user)
            ->get(route('currencies.index'))
            ->assertStatus(200)
            ->assertViewIs('currencies::index');
    }

    public function test_can_view_create_currency_form(): void
    {
        $this->actingAs($this->user)
            ->get(route('currencies.create'))
            ->assertStatus(200)
            ->assertViewIs('currencies::create');
    }

    public function test_can_create_currency(): void
    {
        $data = [
            'name' => 'Euro',
            'code' => 'EUR',
            'symbol' => '€',
            'decimal_places' => 2,
            'rate_mode' => 'manual',
            'is_active' => true,
            'is_default' => false,
        ];

        $this->actingAs($this->user)
            ->post(route('currencies.store'), $data)
            ->assertRedirect(route('currencies.index'));

        $this->assertDatabaseHas('currencies', [
            'name' => 'Euro',
            'code' => 'EUR',
        ]);
    }

    public function test_cannot_create_currency_with_duplicate_code(): void
    {
        Currency::factory()->create(['code' => 'USD']);

        $data = [
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'decimal_places' => 2,
            'rate_mode' => 'manual',
        ];

        $this->actingAs($this->user)
            ->post(route('currencies.store'), $data)
            ->assertSessionHasErrors('code');
    }

    public function test_can_view_currency_details(): void
    {
        $currency = Currency::factory()->create();

        $this->actingAs($this->user)
            ->get(route('currencies.show', $currency))
            ->assertStatus(200)
            ->assertViewIs('currencies::show')
            ->assertViewHas('currency', $currency);
    }

    public function test_can_view_edit_currency_form(): void
    {
        $currency = Currency::factory()->create();

        $this->actingAs($this->user)
            ->get(route('currencies.edit', $currency))
            ->assertStatus(200)
            ->assertViewIs('currencies::edit')
            ->assertViewHas('currency', $currency);
    }

    public function test_can_update_currency(): void
    {
        $currency = Currency::factory()->create();

        $data = [
            'name' => 'Updated Name',
            'code' => $currency->code,
            'symbol' => '€',
            'decimal_places' => 3,
            'rate_mode' => 'automatic',
            'is_active' => false,
        ];

        $this->actingAs($this->user)
            ->put(route('currencies.update', $currency), $data)
            ->assertRedirect(route('currencies.index'));

        $this->assertDatabaseHas('currencies', [
            'id' => $currency->id,
            'name' => 'Updated Name',
            'decimal_places' => 3,
        ]);
    }

    public function test_cannot_delete_default_currency(): void
    {
        $currency = Currency::factory()->create(['is_default' => true]);

        $this->actingAs($this->user)
            ->delete(route('currencies.destroy', $currency))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('currencies', ['id' => $currency->id]);
    }

    public function test_can_delete_non_default_currency(): void
    {
        $currency = Currency::factory()->create(['is_default' => false]);

        $this->actingAs($this->user)
            ->delete(route('currencies.destroy', $currency))
            ->assertRedirect(route('currencies.index'));

        $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
    }

    public function test_unauthenticated_user_cannot_access_currencies(): void
    {
        $this->get(route('currencies.index'))
            ->assertRedirect(route('login'));
    }
}
