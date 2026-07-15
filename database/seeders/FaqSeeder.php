<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Seed the FAQ entries previously hardcoded on the home page.
     */
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'Vai ir iespējama piegāde uz pasākuma vietu?',
                'answer' => 'Jā, nodrošinām piegādi uz pasākuma norises vietu. Piegādes izmaksas un iespējas atkarīgas no atrašanās vietas un izvēlētā inventāra.',
            ],
            [
                'question' => 'Vai ir iespējama saziņa ārpus darba laika?',
                'answer' => 'Jā, sazinieties ar mums pa tālruni vai e-pastu, un mēs atbildēsim, tiklīdz tas būs iespējams.',
            ],
            [
                'question' => 'Ko darīt, ja esmu rezervējis, bet mani plāni mainās?',
                'answer' => 'Sazinieties ar mums pēc iespējas ātrāk, un mēs kopā atradīsim risinājumu - mainīsim rezervācijas datumu vai atcelsim to.',
            ],
        ];

        foreach ($faqs as $position => $faq) {
            Faq::query()->firstOrCreate(
                ['question' => $faq['question']],
                [...$faq, 'position' => $position + 1],
            );
        }
    }
}
