<?php

/**
 * CUSTOMIZAÇÕES BURITI
 * Índice do módulo Pessoas — painel minimalista no lugar da área em branco do legado.
 *
 * @see \App\Support\LegacyModuleIndexPanel
 */

use App\Support\LegacyModuleIndexPanel;

return new class
{
    public function RenderHTML()
    {
        return LegacyModuleIndexPanel::render('Pessoas', $this->processoAp, [
            'descricao' => 'Cadastro de pessoas e tipologias. Navegue pelo submenu Cadastros e Ferramentas para as telas específicas.',
            'icone' => 'fa-users',
        ]);
    }

    public function Formular()
    {
        $this->title = 'Pessoas';
        $this->processoAp = 7;
    }
};
