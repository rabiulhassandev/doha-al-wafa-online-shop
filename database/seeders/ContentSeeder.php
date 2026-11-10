<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Starter pages and FAQ. Pages are hidden drafts with a section outline for the owner to finish;
 * the FAQ answers describe how the store already works, so they are shown. Safe to run again:
 * nothing the owner edited is overwritten.
 */
class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->pages() as $position => $page) {
            Page::query()->firstOrCreate(['slug' => $page['slug']], [...$page, 'sort_order' => ($position + 1) * 10, 'is_published' => false]);
        }

        foreach ($this->questions() as $position => $faq) {
            Faq::query()->firstOrCreate(['question_en' => $faq['question_en']], [...$faq, 'sort_order' => ($position + 1) * 10, 'is_published' => true]);
        }
    }

    /**
     * @return list<array{slug: string, title_en: string, title_ar: string, body_en: string, body_ar: string}>
     */
    private function pages(): array
    {
        return [
            [
                'slug' => 'about',
                'title_en' => 'About us',
                'title_ar' => 'من نحن',
                'body_en' => "## Our story\n\n[Tell customers who you are, when the shop started and what makes your jelabiyas special.]\n\n## Our showroom\n\n[Address, opening days and hours.]",
                'body_ar' => "## قصتنا\n\n[عرّف العملاء بالمتجر: من أنتم، ومتى بدأتم، وما الذي يميز جلابياتكم.]\n\n## صالة العرض\n\n[العنوان وأيام وساعات العمل.]",
            ],
            [
                'slug' => 'delivery',
                'title_en' => 'Delivery',
                'title_ar' => 'التوصيل',
                'body_en' => "## Where we deliver\n\nWe deliver to every area of Qatar. The fee for your area is shown in your bag and at checkout.\n\n## How long it takes\n\n[Usual delivery times, and the days with no delivery.]\n\n## Paying\n\nYou pay in cash when your order arrives.",
                'body_ar' => "## مناطق التوصيل\n\nنوصل إلى جميع مناطق قطر. تظهر رسوم منطقتك في حقيبة التسوق وعند إتمام الطلب.\n\n## مدة التوصيل\n\n[مدة التوصيل المعتادة، والأيام التي لا يتم فيها التوصيل.]\n\n## الدفع\n\nتدفع نقداً عند وصول طلبك.",
            ],
            [
                'slug' => 'exchange-and-returns',
                'title_en' => 'Exchange and returns',
                'title_ar' => 'الاستبدال والإرجاع',
                'body_en' => "## Exchanges\n\n[How many days customers have, and the condition items must be in, such as unworn with tags.]\n\n## Returns and refunds\n\n[Whether you accept returns, and how refunds are paid.]\n\n## How to ask\n\nMessage us on WhatsApp with your order number.",
                'body_ar' => "## الاستبدال\n\n[عدد الأيام المتاحة للاستبدال، وحالة القطعة المطلوبة، مثل أن تكون غير ملبوسة ومع البطاقة.]\n\n## الإرجاع واسترداد المبلغ\n\n[هل تقبلون الإرجاع، وكيف يُسترد المبلغ.]\n\n## طريقة الطلب\n\nراسلنا عبر واتساب مع ذكر رقم طلبك.",
            ],
            [
                'slug' => 'privacy',
                'title_en' => 'Privacy',
                'title_ar' => 'الخصوصية',
                'body_en' => "## What we keep\n\nWhen you order, we keep your name, mobile number, address and the items you ordered, to deliver your order and contact you about it.\n\n## Offers\n\nIf you join our community, we send news and offers to your WhatsApp number or email. Ask us to stop at any time.\n\n## Cookies\n\n[Explain any cookies used to measure ads.]\n\n## Contact\n\n[Who to contact about your data.]",
                'body_ar' => "## البيانات التي نحتفظ بها\n\nعند الطلب نحتفظ باسمك ورقم جوالك وعنوانك والمنتجات التي طلبتها، لتوصيل طلبك والتواصل معك بشأنه.\n\n## العروض\n\nإذا انضممت إلى مجتمعنا، نرسل الأخبار والعروض إلى رقم واتساب أو بريدك الإلكتروني. يمكنك طلب إيقافها في أي وقت.\n\n## ملفات تعريف الارتباط\n\n[اشرح ملفات تعريف الارتباط المستخدمة لقياس الإعلانات، إن وجدت.]\n\n## التواصل\n\n[جهة التواصل بخصوص بياناتك.]",
            ],
            [
                'slug' => 'terms',
                'title_en' => 'Terms',
                'title_ar' => 'الشروط والأحكام',
                'body_en' => "## Orders\n\n[When an order is confirmed, and how you contact customers to confirm it.]\n\n## Prices and payment\n\nPrices are in Qatari riyals. You pay in cash on delivery.\n\n## Delivery\n\n[Delivery times, and what happens when nobody is home.]",
                'body_ar' => "## الطلبات\n\n[متى يتم تأكيد الطلب، وكيف تتواصلون مع العميل لتأكيده.]\n\n## الأسعار والدفع\n\nالأسعار بالريال القطري، والدفع نقداً عند الاستلام.\n\n## التوصيل\n\n[مدة التوصيل، وما يحدث عند عدم وجود أحد في المنزل.]",
            ],
        ];
    }

    /**
     * @return list<array{question_en: string, question_ar: string, answer_en: string, answer_ar: string}>
     */
    private function questions(): array
    {
        return [
            [
                'question_en' => 'How do I pay?',
                'question_ar' => 'كيف أدفع؟',
                'answer_en' => 'You pay in cash when your order arrives. There is nothing to pay online.',
                'answer_ar' => 'تدفع نقداً عند وصول طلبك، ولا حاجة للدفع عبر الإنترنت.',
            ],
            [
                'question_en' => 'How much is delivery?',
                'question_ar' => 'كم رسوم التوصيل؟',
                'answer_en' => 'The fee depends on your area. Choose your area in your bag or at checkout to see it straight away.',
                'answer_ar' => 'تختلف الرسوم حسب منطقتك. اختر منطقتك في حقيبة التسوق أو عند إتمام الطلب لتظهر لك مباشرة.',
            ],
            [
                'question_en' => 'How can I follow my order?',
                'question_ar' => 'كيف أتابع طلبي؟',
                'answer_en' => 'Use **Track your order** at the bottom of every page, with your order number and mobile number.',
                'answer_ar' => 'استخدم **تتبّع طلبك** في أسفل كل صفحة، مع رقم طلبك ورقم جوالك.',
            ],
            [
                'question_en' => 'Can I change my order?',
                'question_ar' => 'هل يمكنني تعديل طلبي؟',
                'answer_en' => 'Yes, until it leaves for delivery. Message us on WhatsApp with your order number.',
                'answer_ar' => 'نعم، حتى يخرج للتوصيل. راسلنا عبر واتساب مع ذكر رقم طلبك.',
            ],
            [
                'question_en' => 'How do I choose my size?',
                'question_ar' => 'كيف أختار المقاس المناسب؟',
                'answer_en' => 'Open the size guide on the product page. If you are unsure, ask us on WhatsApp.',
                'answer_ar' => 'افتح دليل المقاسات في صفحة المنتج. وإن لم تكن متأكداً، اسألنا عبر واتساب.',
            ],
        ];
    }
}
