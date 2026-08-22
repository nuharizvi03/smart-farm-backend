<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\Expense;
use App\Models\Farm;
use App\Models\Harvest;
use App\Models\Plot;
use App\Models\PostHarvestLoss;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Farm $farm1;
    protected Farm $farm2;
    protected Plot $plot1;
    protected Plot $plot2;
    protected Crop $crop1;
    protected Crop $crop2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'full_name' => 'John Doe',
            'mobile' => '0712345678',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
            'role' => 'farmer',
            'district' => 'Kandy',
            'province' => 'Central',
        ]);
        Sanctum::actingAs($this->user);

        // Create Farm 1 & Plot 1
        $this->farm1 = Farm::create([
            'user_id' => $this->user->id,
            'farm_name' => 'Green Valley Farm',
            'location' => 'North Valley',
            'district' => 'Kandy',
            'province' => 'Central',
            'total_area' => 50.0,
            'area_unit' => 'acres',
        ]);

        $this->plot1 = Plot::create([
            'farm_id' => $this->farm1->id,
            'plot_name' => 'Plot A',
            'area' => 20.0,
            'area_unit' => 'acres',
            'soil_type' => 'Loamy',
        ]);

        // Create Farm 2 & Plot 2
        $this->farm2 = Farm::create([
            'user_id' => $this->user->id,
            'farm_name' => 'Sunrise Farm',
            'location' => 'South Hill',
            'district' => 'Kandy',
            'province' => 'Central',
            'total_area' => 30.0,
            'area_unit' => 'acres',
        ]);

        $this->plot2 = Plot::create([
            'farm_id' => $this->farm2->id,
            'plot_name' => 'Plot B',
            'area' => 15.0,
            'area_unit' => 'acres',
            'soil_type' => 'Clay',
        ]);

        // Create Crop 1 on Farm 1 (Tomato, Season: Maha)
        $this->crop1 = Crop::create([
            'plot_id' => $this->plot1->id,
            'crop_name' => 'Tomato',
            'variety' => 'Roma',
            'planting_date' => now()->startOfMonth()->toDateString(),
            'expected_harvest_date' => now()->addMonths(2)->toDateString(),
            'season' => 'Maha',
            'status' => 'growing',
        ]);

        // Create Crop 2 on Farm 2 (Carrot, Season: Yala)
        $this->crop2 = Crop::create([
            'plot_id' => $this->plot2->id,
            'crop_name' => 'Carrot',
            'variety' => 'Nantes',
            'planting_date' => now()->startOfMonth()->toDateString(),
            'expected_harvest_date' => now()->addMonths(3)->toDateString(),
            'season' => 'Yala',
            'status' => 'planned',
        ]);

        // Farm 1 expenses
        Expense::create([
            'farm_id' => $this->farm1->id,
            'crop_id' => $this->crop1->id,
            'category' => 'Seeds',
            'amount' => 500,
            'expense_date' => now()->startOfMonth()->toDateString(),
        ]);

        // Farm 1 farm-wide expense
        Expense::create([
            'farm_id' => $this->farm1->id,
            'crop_id' => null,
            'category' => 'Fuel',
            'amount' => 200,
            'expense_date' => now()->startOfMonth()->toDateString(),
        ]);

        // Farm 2 expense
        Expense::create([
            'farm_id' => $this->farm2->id,
            'crop_id' => $this->crop2->id,
            'category' => 'Fertilizer',
            'amount' => 300,
            'expense_date' => now()->startOfMonth()->toDateString(),
        ]);

        // Harvest & Sale for Crop 1
        $harvest1 = Harvest::create([
            'crop_id' => $this->crop1->id,
            'harvest_date' => now()->startOfMonth()->toDateString(),
            'quantity_harvested' => 1000,
            'unit' => 'kg',
        ]);

        Sale::create([
            'harvest_id' => $harvest1->id,
            'buyer_name' => 'Market Trader',
            'sale_date' => now()->startOfMonth()->toDateString(),
            'quantity_sold' => 800,
            'price_per_unit' => 2.5,
        ]);

        PostHarvestLoss::create([
            'harvest_id' => $harvest1->id,
            'loss_date' => now()->startOfMonth()->toDateString(),
            'quantity_lost' => 50,
            'unit' => 'kg',
            'reason' => 'Spoilage',
            'loss_amount' => 100,
        ]);
    }

    public function test_dashboard_summary_with_no_filters(): void
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Dashboard summary retrieved successfully.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'filters' => [
                        'farm_id',
                        'plot_id',
                        'crop_name',
                        'season',
                        'start_date',
                        'end_date',
                    ],
                    'kpis' => [
                        'active_crop_plans',
                        'regular_expenses',
                        'post_harvest_loss_amount',
                        'total_expenses',
                        'total_revenue',
                        'net_profit_loss',
                        'profit_status',
                        'pending_notification_count',
                    ],
                ],
            ]);
    }

    public function test_dashboard_filtering_by_farm_id(): void
    {
        $response = $this->getJson('/api/dashboard?farm_id=' . $this->farm1->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'filters' => [
                        'farm_id' => $this->farm1->id,
                    ],
                    'kpis' => [
                        'active_crop_plans' => 1,
                        'regular_expenses' => 700.0, // 500 seeds + 200 fuel (farm-wide)
                        'post_harvest_loss_amount' => 100.0,
                        'total_expenses' => 800.0,
                        'total_revenue' => 2000.0, // 800 * 2.5
                        'net_profit_loss' => 1200.0,
                        'profit_status' => 'profit',
                    ],
                ],
            ]);
    }

    public function test_dashboard_filtering_by_crop_name(): void
    {
        $response = $this->getJson('/api/dashboard?crop_name=Tomato');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'filters' => [
                        'crop_name' => 'Tomato',
                    ],
                    'kpis' => [
                        'active_crop_plans' => 1,
                        'regular_expenses' => 500.0, // only Tomato crop expenses, excluding farm-wide fuel
                        'post_harvest_loss_amount' => 100.0,
                        'total_expenses' => 600.0,
                        'total_revenue' => 2000.0,
                        'net_profit_loss' => 1400.0,
                    ],
                ],
            ]);
    }

    public function test_dashboard_filtering_by_season(): void
    {
        $response = $this->getJson('/api/dashboard?season=Yala');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'filters' => [
                        'season' => 'Yala',
                    ],
                    'kpis' => [
                        'active_crop_plans' => 1,
                        'regular_expenses' => 300.0,
                        'total_revenue' => 0.0,
                        'total_expenses' => 300.0,
                        'net_profit_loss' => -300.0,
                        'profit_status' => 'loss',
                    ],
                ],
            ]);
    }

    public function test_dashboard_filtering_by_plot_id(): void
    {
        $response = $this->getJson('/api/dashboard?plot_id=' . $this->plot1->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'filters' => [
                        'plot_id' => $this->plot1->id,
                    ],
                    'kpis' => [
                        'active_crop_plans' => 1,
                        'regular_expenses' => 500.0,
                    ],
                ],
            ]);
    }

    public function test_dashboard_filtering_by_date_range(): void
    {
        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->endOfMonth()->toDateString();

        $response = $this->getJson("/api/dashboard?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                ],
            ]);
    }

    public function test_dashboard_filtering_combined_farm_and_season(): void
    {
        $response = $this->getJson('/api/dashboard?farm_id=' . $this->farm1->id . '&season=Maha');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'filters' => [
                        'farm_id' => $this->farm1->id,
                        'season' => 'Maha',
                    ],
                    'kpis' => [
                        'active_crop_plans' => 1,
                        'regular_expenses' => 500.0, // only Tomato on Farm 1 (fuel excluded because season is specified)
                        'post_harvest_loss_amount' => 100.0,
                        'total_expenses' => 600.0,
                        'total_revenue' => 2000.0,
                        'net_profit_loss' => 1400.0,
                        'profit_status' => 'profit',
                    ],
                ],
            ]);
    }

    public function test_profit_trend_endpoint(): void
    {
        $response = $this->getJson('/api/dashboard/profit-trend?farm_id=' . $this->farm1->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'filters' => [
                        'farm_id' => $this->farm1->id,
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'filters',
                    'period' => ['start_date', 'end_date'],
                    'trend',
                ],
            ]);
    }

    public function test_expense_distribution_endpoint(): void
    {
        $response = $this->getJson('/api/dashboard/expense-distribution?farm_id=' . $this->farm1->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'filters' => [
                        'farm_id' => $this->farm1->id,
                    ],
                    'total_expenses' => 800.0,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'filters',
                    'period',
                    'distribution' => [
                        '*' => ['category', 'amount', 'percentage'],
                    ],
                    'total_expenses',
                ],
            ]);
    }

    public function test_revenue_expenses_endpoint(): void
    {
        $response = $this->getJson('/api/dashboard/revenue-vs-expenses?crop_name=Tomato');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'filters' => [
                        'crop_name' => 'Tomato',
                    ],
                    'summary' => [
                        'total_revenue' => 2000.0,
                        'total_expenses' => 600.0, // 500 seeds + 100 post harvest loss
                    ],
                ],
            ]);

        // Test alias endpoint
        $aliasResponse = $this->getJson('/api/dashboard/revenue-expenses?crop_name=Tomato');
        $aliasResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_crop_performance_endpoint(): void
    {
        $response = $this->getJson('/api/dashboard/crop-performance?season=Maha');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'filters' => [
                        'season' => 'Maha',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'filters',
                    'crop_types',
                    'chart' => ['labels', 'datasets'],
                ],
            ]);
    }

    public function test_validation_invalid_farm_id(): void
    {
        $response = $this->getJson('/api/dashboard?farm_id=99999');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['farm_id']);
    }

    public function test_validation_invalid_plot_id(): void
    {
        $response = $this->getJson('/api/dashboard?plot_id=99999');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plot_id']);
    }

    public function test_validation_end_date_before_start_date(): void
    {
        $response = $this->getJson('/api/dashboard?start_date=2026-06-30&end_date=2026-06-01');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }
}
