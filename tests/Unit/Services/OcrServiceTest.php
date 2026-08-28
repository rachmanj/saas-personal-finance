<?php

namespace Tests\Unit\Services;

use App\Services\OcrService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OcrServiceTest extends TestCase
{
    private string $imagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->imagePath = sys_get_temp_dir() . '/test_receipt_' . uniqid() . '.jpg';
        $img = imagecreatetruecolor(10, 10);
        imagejpeg($img, $this->imagePath);
        imagedestroy($img);
    }

    protected function tearDown(): void
    {
        @unlink($this->imagePath);
        parent::tearDown();
    }

    public function test_parse_with_vision_returns_structured_result(): void
    {
        config([
            'services.openrouter.api_key' => 'test-key',
            'services.openrouter.vision_model' => 'openai/gpt-4o-mini',
            'services.openrouter.vision_enabled' => true,
        ]);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'merchant' => 'Fore Coffee',
                                'items' => 'Americano, Croissant',
                                'amount' => 85000,
                                'date' => '2026-08-10',
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OcrService;
        $result = $service->parseWithVision($this->imagePath);

        $this->assertEquals('Fore Coffee', $result['merchant']);
        $this->assertEquals(85000, $result['amount']);
        $this->assertEquals('2026-08-10', $result['date']);
        $this->assertStringContainsString('Fore Coffee', $result['raw_text']);
        $this->assertStringContainsString('Americano', $result['raw_text']);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
                && $body['model'] === 'openai/gpt-4o-mini'
                && $body['messages'][1]['content'][1]['image_url']['detail'] === 'low'
                && str_starts_with($body['messages'][1]['content'][1]['image_url']['url'], 'data:image/jpeg;base64,');
        });
    }

    public function test_parse_with_vision_throws_on_api_error(): void
    {
        config(['services.openrouter.api_key' => 'test-key']);

        Http::fake([
            'openrouter.ai/*' => Http::response(['error' => 'server error'], 500),
        ]);

        $service = new OcrService;

        $this->expectException(\RuntimeException::class);
        $service->parseWithVision($this->imagePath);
    }

    public function test_parse_falls_back_to_tesseract_when_vision_fails(): void
    {
        config([
            'services.openrouter.api_key' => 'test-key',
            'services.openrouter.vision_enabled' => true,
        ]);

        Http::fake([
            'openrouter.ai/*' => Http::response([], 500),
        ]);

        $service = $this->getMockBuilder(OcrService::class)
            ->onlyMethods(['parseImage'])
            ->getMock();

        $service->expects($this->once())
            ->method('parseImage')
            ->with($this->imagePath)
            ->willReturn([
                'merchant' => 'Tesseract Shop',
                'amount' => 50000.0,
                'date' => '01/01/2026',
                'raw_text' => "Tesseract Shop\nTotal 50000",
            ]);

        $result = $service->parse($this->imagePath);

        $this->assertEquals('Tesseract Shop', $result['merchant']);
        $this->assertEquals(50000.0, $result['amount']);
    }

    public function test_parse_uses_vision_for_images_when_configured(): void
    {
        config([
            'services.openrouter.api_key' => 'test-key',
            'services.openrouter.vision_model' => 'openai/gpt-4o-mini',
            'services.openrouter.vision_enabled' => true,
        ]);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => '{"merchant":"AI Shop","items":"Item","amount":10000,"date":null}',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OcrService;
        $result = $service->parse($this->imagePath);

        $this->assertEquals('AI Shop', $result['merchant']);
        $this->assertEquals(10000, $result['amount']);
        Http::assertSentCount(1);
    }

    public function test_parse_skips_vision_when_disabled(): void
    {
        config([
            'services.openrouter.api_key' => 'test-key',
            'services.openrouter.vision_enabled' => false,
        ]);

        Http::fake();

        $service = $this->getMockBuilder(OcrService::class)
            ->onlyMethods(['parseImage'])
            ->getMock();

        $service->expects($this->once())
            ->method('parseImage')
            ->with($this->imagePath)
            ->willReturn([
                'merchant' => null,
                'amount' => null,
                'date' => null,
                'raw_text' => '',
            ]);

        $service->parse($this->imagePath);

        Http::assertNothingSent();
    }
}
