<?php
    use function Laravel\Folio\{name};
    name('home');
?>

<x-layouts.marketing
    :seo="[
        'title'         => setting('site.title', 'Laravel Wave'),
        'description'   => setting('site.description', 'Software as a Service Starter Kit'),
        'image'         => url('/og_image.png'),
        'type'          => 'website'
    ]"
>
        
    <x-marketing.sections.hero />
    
    <x-marketing.sections.features />

    <x-marketing.sections.partners />

    <x-marketing.sections.cta />

</x-layouts.marketing>
