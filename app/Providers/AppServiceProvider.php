<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        /*
         * Stand-in do @money do cknow/laravel-money, que o sistema real
         * usa. Existe aqui só pra os partials da fatura serem recortes
         * literais do template de produção — sem ele, cada `@money`
         * precisaria virar outra coisa e os arquivos deixariam de ser
         * liftáveis de volta.
         *
         * Atenção ao lifting: aqui a entrada é float em reais; confirmar
         * se lá o helper espera centavos.
         */
        Blade::directive('money', fn (string $expressao) => "<?php echo 'R$ '.number_format((float) ({$expressao}), 2, ',', '.'); ?>");
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
