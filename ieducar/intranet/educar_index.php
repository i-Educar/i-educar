<?php

/**
 * CUSTOMIZAÇÕES BURITI
 * Índice do módulo Escola — painel minimalista no lugar da área em branco do legado.
 *
 * @see LegacyModuleIndexPanel
 */

use App\Support\LegacyModuleIndexPanel;

return new class
{
    public function RenderHTML()
    {
        return LegacyModuleIndexPanel::render('Escola', $this->processoAp, [
            'descricao' => 'Núcleo escolar: cadastros, movimentações, lançamentos e relatórios. O submenu acima tem a lista completa.',
            'icone' => 'fa-university',
        ]);
    }

    public function Formular()
    {
        $this->title = 'Escola';
        $this->processoAp = 55;
    }
};
