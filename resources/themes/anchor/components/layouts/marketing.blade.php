<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" style="overflow-x:hidden">
<head>
    @include('theme::partials.head', ['seo' => ($seo ?? null) ])
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
    <!-- Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "tertiary-fixed": "#f2dede",
              "inverse-surface": "#303031",
              "on-surface": "#1b1c1c",
              "surface-bright": "#fbf9f8",
              "outline-variant": "#ebbbb4",
              "surface-dim": "#dbdad9",
              "on-primary-fixed-variant": "#930100",
              "on-secondary-fixed-variant": "#474746",
              "inverse-primary": "#ffb4a8",
              "on-primary-fixed": "#410000",
              "secondary-fixed-dim": "#c8c6c5",
              "on-tertiary-fixed": "#231919",
              "on-secondary": "#ffffff",
              "on-primary": "#ffffff",
              "surface-container-highest": "#e4e2e2",
              "surface-tint": "#c00100",
              "error": "#ba1a1a",
              "primary-container": "#eb0000",
              "surface-variant": "#e4e2e2",
              "on-secondary-fixed": "#1c1b1b",
              "inverse-on-surface": "#f2f0f0",
              "on-primary-container": "#fffbff",
              "primary-fixed-dim": "#ffb4a8",
              "primary-fixed": "#ffdad4",
              "on-tertiary": "#ffffff",
              "on-error": "#ffffff",
              "on-background": "#1b1c1c",
              "secondary-container": "#e2dfde",
              "primary": "#bc0100",
              "surface-container-low": "#f5f3f3",
              "surface-container-lowest": "#ffffff",
              "surface": "#fbf9f8",
              "tertiary": "#675959",
              "secondary": "#5f5e5e",
              "outline": "#956d67",
              "on-error-container": "#93000a",
              "background": "#fbf9f8",
              "surface-container-high": "#e9e8e7",
              "tertiary-container": "#807171",
              "error-container": "#ffdad6",
              "secondary-fixed": "#e5e2e1",
              "on-tertiary-fixed-variant": "#514444",
              "on-surface-variant": "#603e39",
              "surface-container": "#efeded",
              "tertiary-fixed-dim": "#d5c2c2",
              "on-secondary-container": "#636262",
              "on-tertiary-container": "#fffbff"
            },
            fontFamily: {
              "headline": ["Plus Jakarta Sans"],
              "body": ["Inter"],
              "label": ["Inter"]
            },
            borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
          },
        },
      }
    </script>
</head>
<body x-data class="bg-surface font-body text-on-surface antialiased" x-cloak>

    <x-marketing.elements.header />

    <main class="flex-grow overflow-x-hidden">
        {{ $slot }}
    </main>

    @livewire('notifications')
    @include('theme::partials.footer')
    @include('theme::partials.footer-scripts')
    {{ $javascript ?? '' }}

</body>
</html>
