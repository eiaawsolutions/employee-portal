<?php

namespace Tests\Feature;

use App\Services\MarketingChatbotService;
use Tests\TestCase;

/**
 * The website assistant's facts must match what checkout charges — the
 * pricing block is rendered from config('eiaaw.pricing.tiers'), and no
 * trial or USD wording may survive in the prompt.
 */
class MarketingChatbotPromptTest extends TestCase
{
    public function test_prompt_quotes_the_configured_ringgit_prices(): void
    {
        $prompt = app(MarketingChatbotService::class)->systemPrompt();

        foreach (['starter', 'growth', 'scale'] as $tier) {
            $price = config("eiaaw.pricing.tiers.{$tier}.monthly_myr");
            $this->assertStringContainsString("RM {$price}/employee/mo", $prompt, "{$tier} price missing from chatbot facts");
        }
    }

    public function test_prompt_offers_no_trial_and_no_dollar_prices(): void
    {
        $prompt = app(MarketingChatbotService::class)->systemPrompt();

        foreach (['14-day', 'no credit card', 'Start the trial', '$6', '$14', '$29', 'USD per active'] as $stale) {
            $this->assertStringNotContainsStringIgnoringCase($stale, $prompt, "chatbot prompt still says: {$stale}");
        }
        $this->assertStringContainsString('There is NO free trial', $prompt);
    }

    public function test_refusal_marker_is_stripped_without_eating_the_first_sentence(): void
    {
        $this->assertSame(
            "That's outside what I can help with here. Try Talk to us.",
            MarketingChatbotService::stripRefusalMarker("[REFUSED] That's outside what I can help with here. Try Talk to us."),
        );
        $this->assertSame('No space after the marker.', MarketingChatbotService::stripRefusalMarker('[REFUSED]No space after the marker.'));
        $this->assertSame('', MarketingChatbotService::stripRefusalMarker('[REFUSED]'));
    }

    public function test_prompt_acknowledges_every_sibling_product(): void
    {
        $prompt = app(MarketingChatbotService::class)->systemPrompt();

        foreach (['sa.eiaawsolutions.com', 'ads.eiaawsolutions.com', 'smt.eiaawsolutions.com'] as $site) {
            $this->assertStringContainsString($site, $prompt);
        }
    }
}
