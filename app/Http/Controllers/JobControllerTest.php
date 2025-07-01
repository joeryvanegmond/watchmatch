use App\Http\Controllers\JobController;
use App\Models\Watch;
use App\Services\SerpApiService;
use App\Services\WatchImageService;
use App\Services\WatchSimilarityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;
use RefreshDatabase;

<?php

namespace Tests\Unit;

use App\Http\Controllers\JobController;
use App\Models\Watch;
use App\Services\SerpApiService;
use App\Services\WatchImageService;
use App\Services\WatchSimilarityService;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;

class JobControllerTest extends TestCase
{

    private $searchServiceMock;
    private $imageServiceMock;
    private $similarityServiceMock;
    private $jobController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchServiceMock = Mockery::mock(SerpApiService::class);
        $this->imageServiceMock = Mockery::mock(WatchImageService::class);
        $this->similarityServiceMock = Mockery::mock(WatchSimilarityService::class);

        $this->jobController = new JobController(
            $this->searchServiceMock,
            $this->imageServiceMock,
            $this->similarityServiceMock
        );
    }

    public function testSimilizatorReturns404WhenNoWatchesAvailable()
    {
        Watch::factory()->count(0)->create();

        $response = $this->jobController->similizator();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->status());
        $this->assertEquals(['message' => 'No watches available for comparison'], $response->getData(true));
    }

    public function testSimilizatorProcessesAndStoresSimilarities()
    {
        $watch = Watch::factory()->create([
            'brand' => 'Rolex',
            'model' => 'Submariner',
        ]);

        $this->searchServiceMock
            ->shouldReceive('search')
            ->once()
            ->with('Rolex', 'Submariner')
            ->andReturn(['similar_watch_data']);

        $this->similarityServiceMock
            ->shouldReceive('processAndStoreSimilarities')
            ->once()
            ->with(['similar_watch_data'], $watch)
            ->andReturn(['processed_results']);

        $response = $this->jobController->similizator();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertEquals(['processed_results'], $response->getData(true));
    }
}