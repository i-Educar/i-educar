<?php

/**
 * CUSTOMIZAÇÕES BURITI
 * Índice do módulo Endereçamento — painel minimalista no lugar da área em branco do legado.
 *
 * @see \App\Support\LegacyModuleIndexPanel
 */

use App\Support\LegacyModuleIndexPanel;

return new class
{
    public function RenderHTML()
    {
        return LegacyModuleIndexPanel::render('Endereçamento', $this->processoAp, [
            'descricao' => 'Cadastros de localização. Abra o submenu para países, estados, municípios e demais opções.',
            'icone' => 'fa-map-marker',
        ]);
    }

    public function Formular()
    {
        $this->title = 'Endereçamento';
        $this->processoAp = 68;
    }
};
