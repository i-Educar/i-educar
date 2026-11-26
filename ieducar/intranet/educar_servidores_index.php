<?php

/**
 * CUSTOMIZAÇÕES BURITI
 * Índice do módulo Servidores — painel minimalista no lugar da área em branco do legado.
 *
 * @see \App\Support\LegacyModuleIndexPanel
 */

use App\Support\LegacyModuleIndexPanel;

return new class
{
    public function RenderHTML()
    {
        return LegacyModuleIndexPanel::render('Servidores', $this->processoAp, [
            'descricao' => 'Gestão de servidores e RH. Os grupos do submenu (Cadastros, RH, horários…) concentram as ações.',
            'icone' => 'fa-id-badge',
        ]);
    }

    public function Formular()
    {
        $this->title = 'Servidores';
        $this->processoAp = 71;
    }
};
