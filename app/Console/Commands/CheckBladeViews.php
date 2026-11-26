<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Throwable;

class CheckBladeViews extends Command
{
    protected $signature = 'sistema:verificar-vistas';

    protected $description = 'Compila y comprueba la sintaxis de las vistas sin ejecutarlas ni modificar datos';

    public function handle(): int
    {
        $checked = 0;
        $failures = 0;
        foreach (File::allFiles(resource_path('views')) as $file) {
            if (!str_ends_with($file->getFilename(), '.blade.php')) continue;

            try {
                $compiled = Blade::compileString($file->getContents());
                token_get_all($compiled, TOKEN_PARSE);
                $checked++;
            } catch (Throwable $exception) {
                $failures++;
                $this->error($file->getRelativePathname().': '.$exception->getMessage());
            }
        }

        $this->line("{$checked} vistas válidas; {$failures} errores. No se ejecutaron vistas ni se modificó la base de datos.");
        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
