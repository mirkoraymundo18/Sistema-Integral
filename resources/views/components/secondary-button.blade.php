<button {{ $attributes->merge(['type' => 'button', 'class' => 'catalog-button']) }}>
    {{ $slot }}
</button>
