<?php

/**
 * CUSTOMIZAÇÃO Serventec / Laguna (PE-77)
 * Índice do módulo Educacenso — painel minimalista no lugar da área em branco do legado.
 *
 * @see \App\Support\LegacyModuleIndexPanel
 */

use App\Support\LegacyModuleIndexPanel;

return new class
{
    public function RenderHTML()
    {
        return LegacyModuleIndexPanel::render('Educacenso', $this->processoAp, [
            'descricao' => 'Exportações, importações e rotinas do Censo Escolar liberadas para o seu perfil.',
            'icone' => 'fa-cloud-upload',
        ]);
    }

    public function Formular()
    {
        $this->title = 'Educacenso';
        $this->processoAp = 70;
    }
};
