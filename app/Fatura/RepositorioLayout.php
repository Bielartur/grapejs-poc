<?php

declare(strict_types=1);

namespace App\Fatura;

use Illuminate\Support\Facades\Storage;
use JsonException;

/**
 * Persistência do layout.
 *
 * Arquivo JSON único porque isto é protótipo. No sistema real vira uma
 * coluna `json` na empresa (o layout é global por empresa, como diz o
 * subtítulo da tela) — e a troca é só este arquivo, já que todo o resto
 * conversa via `LayoutFatura`.
 */
final class RepositorioLayout
{
    private const ARQUIVO = 'fatura/layout.json';

    public static function carregar(): LayoutFatura
    {
        if (! Storage::exists(self::ARQUIVO)) {
            return LayoutFatura::padrao();
        }

        try {
            $dados = json_decode(
                (string) Storage::get(self::ARQUIVO),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            // arquivo corrompido não pode derrubar a tela
            return LayoutFatura::padrao();
        }

        return LayoutFatura::daArray(is_array($dados) ? $dados : []);
    }

    public static function salvar(LayoutFatura $layout): void
    {
        Storage::put(
            self::ARQUIVO,
            json_encode($layout->paraArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );
    }
}
