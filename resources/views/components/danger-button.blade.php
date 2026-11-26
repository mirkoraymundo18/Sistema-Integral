<button {{ $attributes->merge(['type' => 'submit', 'class' => 'catalog-button catalog-danger']) }}>
    {{ $slot }}
</button>
