<button {{ $attributes->merge(['type' => 'submit', 'class' => 'catalog-button catalog-primary']) }}>
    {{ $slot }}
</button>
