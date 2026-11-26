<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class BladeSyntaxTest extends TestCase
{
    public function test_all_application_views_compile_to_valid_php(): void
    {
        $this->artisan('sistema:verificar-vistas')->assertSuccessful();
    }

    public function test_blade_regression_detects_adjacent_closing_directives(): void
    {
        $broken = "@foreach(\$sales as \$sale) @if(true) @can('administrar-almacen') cobro @endcan@endif @endforeach";
        $repaired = str_replace('@endcan@endif', "@endcan\n@endif", $broken);
        token_get_all(Blade::compileString($repaired), TOKEN_PARSE);

        $this->expectException(\ParseError::class);
        token_get_all(Blade::compileString($broken), TOKEN_PARSE);
    }
}
