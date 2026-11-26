<?php

/**
 * CUSTOMIZAÇÃO Serventec / Laguna (PE-77)
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
            'descricao' => 'Países, estados, municípios e demais cadastros de localização disponíveis ao seu perfil.',
            'icone' => 'fa-map-marker',
        ]);
    }

    public function Formular()
    {
        $this->title = 'Endereçamento';
        $this->processoAp = 68;
    }
};
