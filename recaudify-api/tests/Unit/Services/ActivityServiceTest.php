<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\Seller;
use App\Services\ActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ActivityService();
    }

    public function test_filters_by_model(): void
    {
        Product::create(["name" => "Biblia", "value" => 1000]);
        Seller::create(["name" => "Fabiola"]);

        $onlyProducts = $this->service->getAll(["model" => "Product"]);

        $this->assertSame(1, $onlyProducts->total());
        $this->assertSame("App\\Models\\Product", $onlyProducts->getCollection()->first()->subject_type);
    }

    public function test_resolves_subject_label(): void
    {
        $product = Product::create(["name" => "Biblia Grande", "value" => 1000]);

        $activity = $this->service
            ->getAll(["model" => "Product"])
            ->getCollection()
            ->first();

        $this->assertSame("Biblia Grande", $activity->subject_label);
        $this->assertSame($product->id, $activity->subject_id);
    }

    public function test_resolves_label_even_for_soft_deleted_subject(): void
    {
        $product = Product::create(["name" => "Eliminable", "value" => 1000]);
        $product->delete(); // soft delete (genera además una actividad 'deleted')

        $latest = $this->service
            ->getAll(["model" => "Product"])
            ->getCollection()
            ->first();

        $this->assertSame("Eliminable", $latest->subject_label);
    }

    public function test_pagination_respects_per_page(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Product::create(["name" => "P{$i}", "value" => 100]);
        }

        $page = $this->service->getAll([], 2);

        $this->assertSame(2, $page->perPage());
        $this->assertSame(1, $page->currentPage());
        $this->assertCount(2, $page->getCollection());
    }
}
