<?php

namespace Database\Seeders;

use App\Models\BioPage;
use Illuminate\Database\Seeder;

class BioPageSeeder extends Seeder
{
    public function run(): void
    {
        $theme = BioPage::defaultTheme();

        $blocks = [
            [
                'id' => 'blk-sec-info',
                'type' => 'section',
                'data' => [
                    'id' => 'blk-sec-info',
                    'title' => 'Informasi & Pendaftaran',
                ],
            ],
            [
                'id' => 'blk-link-web',
                'type' => 'link',
                'data' => [
                    'id' => 'blk-link-web',
                    'label' => 'Website Resmi DQWS',
                    'url' => 'https://dqws.id',
                    'highlight' => true,
                ],
            ],
            [
                'id' => 'blk-wa-admin',
                'type' => 'whatsapp',
                'data' => [
                    'id' => 'blk-wa-admin',
                    'label' => 'Hubungi Admin via WhatsApp',
                    'phone' => '6281234567890',
                    'message' => 'Assalamualaikum Admin DQWS, saya ingin bertanya informasi pendaftaran.',
                ],
            ],
            [
                'id' => 'blk-banner-psb',
                'type' => 'banner',
                'data' => [
                    'id' => 'blk-banner-psb',
                    'title' => 'Penerimaan Santri Baru (PSB)',
                    'description' => 'Pendaftaran tahun ajaran baru telah dibuka. Klik untuk info lengkap & syarat pendaftaran.',
                    'url' => 'https://dqws.id/psb',
                ],
            ],
        ];

        BioPage::updateOrCreate(
            ['slug' => 'dqws'],
            [
                'title' => 'Darul Quran Was Sunnah (DQWS)',
                'bio' => 'Lembaga Pendidikan & Dakwah Islam Berbasis Al-Quran dan As-Sunnah sesuai Pemahaman Salafush Shalih.',
                'avatar' => null,
                'footer_text' => '© 2026 DQWS Link-in-Bio',
                'draft_theme' => $theme,
                'published_theme' => $theme,
                'draft_blocks' => $blocks,
                'published_blocks' => $blocks,
                'is_published' => true,
            ]
        );
    }
}
