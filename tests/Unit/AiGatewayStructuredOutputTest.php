<?php

namespace Tests\Unit;

use App\Services\AiGateway;
use Tests\TestCase;  // Laravel's, not PHPUnit's — the parser hits Log::warning on the fallback path

/**
 * Session 11 A-grade — structured output parsing.
 *
 * The gateway now demands {answer, sources, refused, refusal_reason} JSON
 * from the model and sanitizes server-side before the drawer renders. These
 * tests exercise the pure parsing path — no HTTP, no DB.
 *
 * Covers:
 *   - Well-formed JSON passes through + gets sanitized
 *   - Malformed JSON falls back to plain text (doesn't throw)
 *   - HTML in answer is stripped (defense in depth vs drawer's textContent)
 *   - Length caps enforced
 *   - Source array is bounded (≤20, each ≤200 chars)
 *   - Code-fence wrappers are stripped
 *   - Refusal state flows through
 */
class AiGatewayStructuredOutputTest extends TestCase
{
    private AiGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new AiGateway(
            provider: 'anthropic',
            apiKey: 'sk-test',
            modelRoutine: 'claude-haiku-4-5-20251001',
            modelComplex: 'claude-sonnet-4-6',
        );
    }

    private function parse(string $raw): array
    {
        $m = new \ReflectionMethod($this->gateway, 'parseStructuredAnswer');
        $m->setAccessible(true);
        return $m->invoke($this->gateway, $raw, 1);
    }

    public function test_well_formed_json_parses_cleanly(): void
    {
        $raw = json_encode([
            'answer' => 'Aisha is on leave Mon-Wed.',
            'sources' => ['leave:L-4021', 'employee:Aisha Rahman'],
            'refused' => false,
        ]);

        $out = $this->parse($raw);
        $this->assertSame('Aisha is on leave Mon-Wed.', $out['answer']);
        $this->assertSame(['leave:L-4021', 'employee:Aisha Rahman'], $out['sources']);
        $this->assertFalse($out['refused']);
        $this->assertSame('', $out['refusal_reason']);
    }

    public function test_malformed_json_falls_back_to_plain_text(): void
    {
        $raw = 'This is just a plain-text answer with no JSON wrapper.';
        $out = $this->parse($raw);
        $this->assertStringContainsString('plain-text answer', $out['answer']);
        $this->assertSame([], $out['sources']);
        $this->assertFalse($out['refused']);
    }

    public function test_html_in_answer_is_stripped(): void
    {
        $raw = json_encode([
            'answer' => 'Hello <script>alert(1)</script> and <b>bold</b>.',
            'sources' => [],
            'refused' => false,
        ]);

        $out = $this->parse($raw);
        $this->assertStringNotContainsString('<script>', $out['answer']);
        $this->assertStringNotContainsString('<b>', $out['answer']);
        $this->assertStringNotContainsString('</script>', $out['answer']);
        $this->assertStringContainsString('Hello', $out['answer']);
    }

    public function test_answer_length_capped_at_2000(): void
    {
        $longAnswer = str_repeat('x', 3000);
        $raw = json_encode(['answer' => $longAnswer, 'sources' => [], 'refused' => false]);

        $out = $this->parse($raw);
        $this->assertLessThanOrEqual(2000, mb_strlen($out['answer']));
        $this->assertStringEndsWith('…', $out['answer']);
    }

    public function test_sources_capped_at_20(): void
    {
        $many = array_map(fn ($i) => "record:{$i}", range(1, 50));
        $raw = json_encode(['answer' => 'Test', 'sources' => $many, 'refused' => false]);

        $out = $this->parse($raw);
        $this->assertCount(20, $out['sources']);
    }

    public function test_each_source_length_capped_at_200(): void
    {
        $longSource = str_repeat('y', 400);
        $raw = json_encode(['answer' => 'Test', 'sources' => [$longSource], 'refused' => false]);

        $out = $this->parse($raw);
        $this->assertLessThanOrEqual(200, mb_strlen($out['sources'][0]));
    }

    public function test_code_fence_wrapper_is_stripped(): void
    {
        $raw = "```json\n" . json_encode(['answer' => 'fenced', 'sources' => [], 'refused' => false]) . "\n```";
        $out = $this->parse($raw);
        $this->assertSame('fenced', $out['answer']);
    }

    public function test_refusal_flows_through(): void
    {
        $raw = json_encode([
            'answer' => 'I cannot help with that.',
            'sources' => [],
            'refused' => true,
            'refusal_reason' => 'Off-topic — user asked about weather.',
        ]);

        $out = $this->parse($raw);
        $this->assertTrue($out['refused']);
        $this->assertSame('Off-topic — user asked about weather.', $out['refusal_reason']);
    }

    public function test_non_string_source_entries_are_dropped(): void
    {
        $raw = json_encode([
            'answer' => 'Test',
            'sources' => ['valid:1', ['nested'], null, 42, 'valid:2'],
            'refused' => false,
        ]);

        $out = $this->parse($raw);
        // Strings + numerics kept; arrays/null dropped
        $this->assertSame(['valid:1', '42', 'valid:2'], $out['sources']);
    }
}
