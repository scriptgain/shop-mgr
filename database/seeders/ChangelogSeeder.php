<?php

namespace Database\Seeders;

use App\Models\ChangelogEntry;
use Illuminate\Database\Seeder;

/**
 * Seeds the public release-notes timeline with the alpha's shipped milestones.
 *
 * Idempotent: each entry is keyed on its version with updateOrCreate, so
 * running this again never duplicates a row and leaves merchant edits to
 * existing versions in place. Dates are literal strings on purpose so a
 * re-run never shifts an entry's published date.
 */
class ChangelogSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            [
                'version' => '0.2.0',
                'released_on' => '2026-07-08',
                'title' => 'A Refreshed Storefront',
                'position' => 1,
                'summary' => 'Cleaner product, cart, account and collection pages across the shop.',
                'body' => "We gave the storefront a top-to-bottom polish so your shoppers have a calmer, clearer place to buy.\n\n- **Product pages** have a tidier layout, clearer pricing and easier variant selection.\n- **Cart** is simpler to scan, with quantities and totals that are easy to adjust.\n- **Account** pages group your profile, addresses and order history in one place.\n- **Collection** pages present your groups of products with more room to breathe.\n\nEverything is faster to read and works well on a phone.",
            ],
            [
                'version' => '0.3.0',
                'released_on' => '2026-07-11',
                'title' => 'Secure Card Checkout',
                'position' => 2,
                'summary' => 'Accept credit and debit cards at checkout, powered by Stripe.',
                'body' => "You can now take card payments at checkout. Card details are handled securely by Stripe, so full card numbers never touch your store.\n\nCheckout ships in **test mode** so you can place practice orders end to end without charging a real card. When you are ready to go live, add your own payment keys and switch it on.\n\n> New to the alpha? Leave checkout in test mode while you set up your catalog.",
            ],
            [
                'version' => '0.3.1',
                'released_on' => '2026-07-14',
                'title' => 'Spam Protection You Control',
                'position' => 3,
                'summary' => 'Choose the anti-spam challenge that fits your store.',
                'body' => "Keep bots out of your sign-up and checkout forms with protection you can pick and switch at any time. Choose from:\n\n- **Google reCAPTCHA**\n- **hCaptcha**\n- **Cloudflare Turnstile**\n- A **built-in challenge** that needs no third-party account\n\nSelect a provider, add its keys if it needs any, and you are protected. No provider locks you in.",
            ],
            [
                'version' => '0.4.0',
                'released_on' => '2026-07-17',
                'title' => 'Themes And A Template Editor',
                'position' => 4,
                'summary' => 'Restyle your storefront and fine-tune it with the template editor.',
                'body' => "Make the shop feel like yours.\n\n- Pick a **store theme** to set your overall look, colours and wordmark in a couple of clicks.\n- Open the **template editor** to fine-tune the storefront when you want more control, with your changes saved as versions you can roll back.\n\nStart from a theme, adjust to taste, and preview before you publish.",
            ],
            [
                'version' => '0.4.1',
                'released_on' => '2026-07-20',
                'title' => 'Help Center, FAQ And Policy Pages',
                'position' => 5,
                'summary' => 'Answer common questions and publish your store policies yourself.',
                'body' => "Give shoppers answers without waiting on you.\n\n- Build a **Help Center** and **FAQ** you manage yourself, organised into topics.\n- Publish your **Shipping**, **Refund**, **Terms** and **Privacy** pages, linked right from the footer.\n- Everything is written in plain Markdown and renders cleanly on your storefront.\n\nThis is the current alpha release. Thanks for building your store with us while ShopMGR is in active development.",
            ],
        ];

        foreach ($entries as $data) {
            ChangelogEntry::updateOrCreate(
                ['version' => $data['version']],
                array_merge($data, ['is_published' => true]),
            );
        }
    }
}
