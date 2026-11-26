{{--
  CUSTOMIZAÇÃO Serventec / Laguna (PE-77)
  Painel de visão geral nos índices legados — não substitui o submenu.
  Identificador: {{ $customId }}
--}}
<style>
.svc-module-index{
  --svc-mi-borda: color-mix(in srgb, var(--destaque, #1a5f9e) 18%, #d8dee6);
  --svc-mi-fundo: color-mix(in srgb, var(--superficie, #fff) 94%, var(--clara, #f3f6f8));
  --svc-mi-tinta: var(--tinta, #1f2933);
  --svc-mi-suave: var(--suave, #5b6b76);
  --svc-mi-acento: var(--destaque, var(--cor, #1a5f9e));
  width: 100%;
  max-width: none;
  box-sizing: border-box;
  margin: 8px 0 0;
  padding: 22px 24px 20px;
  min-height: calc(100vh - 220px);
  border: 1px solid var(--svc-mi-borda);
  border-radius: 16px;
  background: var(--svc-mi-fundo);
  color: var(--svc-mi-tinta);
  display: flex;
  flex-direction: column;
  gap: 18px;
}
.svc-module-index__badge{
  display:inline-block; margin:0; padding:3px 9px; border-radius:999px;
  font-size:.66rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase;
  color: var(--svc-mi-acento);
  background: color-mix(in srgb, var(--svc-mi-acento) 9%, transparent);
  border: 1px solid color-mix(in srgb, var(--svc-mi-acento) 20%, transparent);
}
.svc-module-index__head{
  display:grid;
  grid-template-columns: auto 1fr;
  gap: 14px 16px;
  align-items: start;
  padding-bottom: 4px;
  border-bottom: 1px solid color-mix(in srgb, var(--svc-mi-borda) 70%, transparent);
}
.svc-module-index__ico{
  width:48px; height:48px; border-radius:14px; flex:0 0 auto;
  display:grid; place-items:center; color:#fff;
  background: var(--svc-mi-acento); font-size:1.15rem;
}
.svc-module-index__titulo{ margin:0; font-size:1.35rem; font-weight:700; color: var(--svc-mi-tinta); line-height:1.2; }
.svc-module-index__lead{ margin:6px 0 0; font-size:.92rem; color: var(--svc-mi-suave); line-height:1.5; max-width: 72ch; }
.svc-module-index__meta{ margin:8px 0 0; font-size:.78rem; color: var(--svc-mi-suave); }
.svc-module-index__meta strong{ color: var(--svc-mi-tinta); font-weight:600; }
.svc-module-index__nota{
  margin:0;
  padding: 11px 14px;
  border-radius: 12px;
  font-size: .86rem;
  line-height: 1.45;
  color: var(--svc-mi-suave);
  background: color-mix(in srgb, var(--svc-mi-acento) 6%, transparent);
  border: 1px solid color-mix(in srgb, var(--svc-mi-acento) 16%, var(--svc-mi-borda));
}
.svc-module-index__nota strong{ color: var(--svc-mi-tinta); }
.svc-module-index__grade{
  flex: 1;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 12px;
  align-content: start;
}
.svc-module-index__card{
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid color-mix(in srgb, var(--svc-mi-borda) 85%, transparent);
  background: color-mix(in srgb, #fff 78%, var(--svc-mi-fundo));
  min-height: 118px;
}
a.svc-module-index__card{
  text-decoration: none;
  color: inherit;
  transition: border-color .15s, background .15s;
}
a.svc-module-index__card:hover{
  border-color: color-mix(in srgb, var(--svc-mi-acento) 38%, var(--svc-mi-borda));
  background: color-mix(in srgb, var(--svc-mi-acento) 6%, #fff);
}
.svc-module-index__card-kicker{
  margin:0;
  font-size:.68rem;
  font-weight:700;
  letter-spacing:.05em;
  text-transform:uppercase;
  color: var(--svc-mi-suave);
}
.svc-module-index__card-titulo{
  margin:0;
  font-size:1rem;
  font-weight:700;
  color: var(--svc-mi-tinta);
  line-height:1.25;
}
a.svc-module-index__card .svc-module-index__card-titulo{ color: var(--svc-mi-acento); }
.svc-module-index__card-desc{
  margin:0;
  font-size:.82rem;
  color: var(--svc-mi-suave);
  line-height:1.4;
  flex: 1;
}
.svc-module-index__card-rodape{
  margin:4px 0 0;
  font-size:.74rem;
  color: var(--svc-mi-suave);
}
.svc-module-index__card-rodape em{
  font-style: normal;
  color: var(--svc-mi-tinta);
  font-weight: 600;
}
.svc-module-index__vazio{
  margin:0;
  padding:16px 18px;
  border-radius:12px;
  font-size:.9rem;
  color: var(--svc-mi-suave);
  border: 1px dashed var(--svc-mi-borda);
  background: color-mix(in srgb, var(--svc-mi-fundo) 80%, #fff);
}
html[data-buriti-modo="escuro"] .svc-module-index,
body.tema-buriti-skin[data-buriti-modo="escuro"] .svc-module-index{
  --svc-mi-fundo: color-mix(in srgb, var(--superficie, #1a2332) 92%, #0f1720);
  --svc-mi-borda: color-mix(in srgb, var(--destaque, #5b9fd4) 26%, #2a3544);
}
html[data-buriti-modo="escuro"] .svc-module-index__card,
body.tema-buriti-skin[data-buriti-modo="escuro"] .svc-module-index__card{
  background: color-mix(in srgb, var(--superficie, #1a2332) 88%, #000);
}
@media (max-width: 720px) {
  .svc-module-index{ min-height: auto; padding: 16px; }
  .svc-module-index__grade{ grid-template-columns: 1fr; }
}
</style>

<!-- CUSTOMIZAÇÃO: {{ $customId }} · {{ $customLabel }} -->
<section class="svc-module-index" data-customizacao="{{ $customId }}" data-processo="{{ $processo }}" aria-label="Visão geral do módulo {{ $titulo }}">
    <p class="svc-module-index__badge">{{ $customLabel }}</p>

    <header class="svc-module-index__head">
        <span class="svc-module-index__ico" aria-hidden="true"><i class="fa {{ $icone }}"></i></span>
        <div>
            <h2 class="svc-module-index__titulo">{{ $titulo }}</h2>
            <p class="svc-module-index__lead">{{ $descricao }}</p>
            <p class="svc-module-index__meta">
                Perfil: <strong>{{ $perfil }}</strong>
                @if($totalGrupos > 0)
                    · {{ $totalGrupos }} grupo(s) no submenu
                    · {{ $totalAcoes }} ação(ões) liberada(s)
                @endif
            </p>
        </div>
    </header>

    <p class="svc-module-index__nota">
        <strong>Submenu intacto:</strong> todas as telas continuam no menu horizontal acima.
        Este painel só destaca os <em>grupos principais</em> para orientar — abra o item correspondente no submenu para a lista completa.
    </p>

    @if($totalGrupos > 0)
        <div class="svc-module-index__grade">
            @foreach($grupos as $grupo)
                @if(!empty($grupo['url']))
                    <a class="svc-module-index__card" href="{{ $grupo['url'] }}">
                        <p class="svc-module-index__card-kicker">No submenu</p>
                        <h3 class="svc-module-index__card-titulo">{{ $grupo['titulo'] }}</h3>
                        <p class="svc-module-index__card-desc">{{ $grupo['descricao'] }}</p>
                        <p class="svc-module-index__card-rodape">
                            Atalho direto · demais ações em <em>{{ $grupo['titulo'] }}</em> no submenu
                        </p>
                    </a>
                @else
                    <article class="svc-module-index__card">
                        <p class="svc-module-index__card-kicker">Grupo do submenu</p>
                        <h3 class="svc-module-index__card-titulo">{{ $grupo['titulo'] }}</h3>
                        <p class="svc-module-index__card-desc">{{ $grupo['descricao'] }}</p>
                        <p class="svc-module-index__card-rodape">
                            {{ $grupo['qtd'] }} ação(ões) · use <em>{{ $grupo['titulo'] }}</em> no submenu acima
                        </p>
                    </article>
                @endif
            @endforeach
        </div>
    @else
        <p class="svc-module-index__vazio">
            Nenhuma área deste módulo está liberada para o seu tipo de usuário.
            Peça ao administrador para revisar as permissões em <strong>Configurações → Tipos de usuário</strong>.
        </p>
    @endif
</section>
