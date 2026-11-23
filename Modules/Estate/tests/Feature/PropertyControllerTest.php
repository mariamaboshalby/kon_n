<?php

declare(strict_types=1);

namespace Modules\Estate\Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Estate\Enums\ListingType;
use Modules\Estate\Enums\PaymentMethod;
use Modules\Estate\Enums\PropertyStatus;
use Modules\Estate\Models\Property;
use Tests\TestCase;

/**
 * PropertyControllerTest - اختبارات متحكم العقارات
 * 
 * Tests CRUD operations and filtering for properties
 */
class PropertyControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Client $owner;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->owner = Client::factory()->create();
    }

    public function test_can_view_properties_index(): void
    {
        Property::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('properties.index'));

        $response->assertStatus(200);
        $response->assertViewIs('estate::properties.index');
        $response->assertViewHas('properties');
    }

    public function test_can_create_property(): void
    {
        $propertyData = [
            'title' => 'Test Property',
            'sku' => 'PROP-12345',
            'listing_type' => ListingType::Sale->value,
            'status' => PropertyStatus::Available->value,
            'owner_id' => $this->owner->id,
            'city' => 'الرياض',
            'district' => 'النخيل',
            'area' => 250.50,
            'rooms_count' => 4,
            'bathrooms_count' => 3,
            'price' => 500000.00,
            'payment_method' => PaymentMethod::Cash->value,
            'is_furnished' => false,
            'furnishing_percentage' => 0,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('properties.store'), $propertyData);

        $response->assertRedirect();
        $this->assertDatabaseHas('properties', [
            'title' => 'Test Property',
            'sku' => 'PROP-12345',
        ]);
    }

    public function test_can_view_single_property(): void
    {
        $property = Property::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('properties.show', $property));

        $response->assertStatus(200);
        $response->assertViewIs('estate::properties.show');
        $response->assertViewHas('property');
    }

    public function test_can_update_property(): void
    {
        $property = Property::factory()->create();

        $updateData = [
            'title' => 'Updated Property Title',
            'sku' => $property->sku,
            'listing_type' => $property->listing_type->value,
            'status' => $property->status->value,
            'owner_id' => $property->owner_id,
            'city' => $property->city,
            'district' => $property->district,
            'area' => $property->area,
            'rooms_count' => $property->rooms_count,
            'bathrooms_count' => $property->bathrooms_count,
            'price' => 600000.00,
            'payment_method' => $property->payment_method->value,
            'is_furnished' => $property->is_furnished,
            'furnishing_percentage' => $property->furnishing_percentage,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('properties.update', $property), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'title' => 'Updated Property Title',
            'price' => 600000.00,
        ]);
    }

    public function test_can_delete_property(): void
    {
        $property = Property::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('properties.destroy', $property));

        $response->assertRedirect(route('properties.index'));
        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
    }

    public function test_can_filter_properties_by_price_range(): void
    {
        Property::factory()->create(['price' => 100000]);
        Property::factory()->create(['price' => 500000]);
        Property::factory()->create(['price' => 1000000]);

        $response = $this->actingAs($this->user)
            ->get(route('properties.index', [
                'min_price' => 200000,
                'max_price' => 800000,
            ]));

        $response->assertStatus(200);
    }

    public function test_can_filter_properties_by_city(): void
    {
        Property::factory()->create(['city' => 'الرياض']);
        Property::factory()->create(['city' => 'جدة']);

        $response = $this->actingAs($this->user)
            ->get(route('properties.index', ['city' => 'الرياض']));

        $response->assertStatus(200);
    }

    public function test_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('properties.store'), []);

        $response->assertSessionHasErrors([
            'title',
            'sku',
            'listing_type',
            'status',
            'owner_id',
            'city',
            'district',
            'area',
            'rooms_count',
            'bathrooms_count',
            'price',
            'payment_method',
        ]);
    }

    public function test_sku_must_be_unique(): void
    {
        $existingProperty = Property::factory()->create(['sku' => 'PROP-UNIQUE']);

        $response = $this->actingAs($this->user)
            ->post(route('properties.store'), [
                'title' => 'New Property',
                'sku' => 'PROP-UNIQUE',
                'listing_type' => ListingType::Sale->value,
                'status' => PropertyStatus::Available->value,
                'owner_id' => $this->owner->id,
                'city' => 'الرياض',
                'district' => 'النخيل',
                'area' => 200,
                'rooms_count' => 3,
                'bathrooms_count' => 2,
                'price' => 400000,
                'payment_method' => PaymentMethod::Cash->value,
                'is_furnished' => false,
                'furnishing_percentage' => 0,
            ]);

        $response->assertSessionHasErrors('sku');
    }
}
