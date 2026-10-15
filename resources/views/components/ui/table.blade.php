<div {{ $attributes->merge(['class' => 'app-card overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="w-full min-w-max text-left text-sm">
            {{ $slot }}
        </table>
    </div>
</div>
