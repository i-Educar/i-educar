<?php

namespace Tests\Unit\Support;

use App\Support\LegacyModuleIndexPanel;
use Tests\TestCase;

class LegacyModuleIndexPanelTest extends TestCase
{
    public function test_constantes_de_customizacao_estao_claras(): void
    {
        $this->assertSame('serventec-laguna-module-index', LegacyModuleIndexPanel::CUSTOM_ID);
        $this->assertStringContainsString('Serventec', LegacyModuleIndexPanel::CUSTOM_LABEL);
        $this->assertStringContainsString('Laguna', LegacyModuleIndexPanel::CUSTOM_LABEL);
    }

    public function test_render_sem_usuario_nao_quebra_e_marca_customizacao(): void
    {
        $html = LegacyModuleIndexPanel::render('Pessoas', 7, [
            'descricao' => 'Teste unitário',
            'icone' => 'fa-users',
        ]);

        $this->assertStringContainsString('svc-module-index', $html);
        $this->assertStringContainsString(LegacyModuleIndexPanel::CUSTOM_ID, $html);
        $this->assertStringContainsString('Pessoas', $html);
        $this->assertStringContainsString(LegacyModuleIndexPanel::CUSTOM_LABEL, $html);
    }
}
