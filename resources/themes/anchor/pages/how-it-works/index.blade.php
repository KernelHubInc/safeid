<?php
    use function Laravel\Folio\{name};
    name('how-it-works');
?>

<x-layouts.marketing
    :seo="[
        'title'         => setting('site.title', 'Emerion'),
        'description'   => setting('site.description', 'Emerion'),
        'image'         => url('/og_image.png'),
        'type'          => 'website'
    ]"
>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .editorial-shadow {
            shadow-[0_12px_32px_rgba(27,28,28,0.06)];
        }
        .hero-gradient {
            background: linear-gradient(135deg, #BC0100 0%, #EB0000 100%);
        }
        .glass-nav {
            backdrop-filter: blur(20px);
        }
    </style>
    <x-marketing.sections.how-it-works.how-it-works />
    
    <x-marketing.sections.how-it-works.setup-protocol />

    <x-marketing.sections.how-it-works.technical-details />

    <x-marketing.sections.how-it-works.cta-red />

</x-layouts.marketing>
