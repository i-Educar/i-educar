{{--
  CUSTOMIZAÇÃO Serventec / Laguna (PE-77)
  Painel minimalista para índices legados (*_index.php) — área que ficava em branco após skins.
  Identificador: {{ $customId }}
--}}
<style>
.svc-module-index{
  --svc-mi-borda: color-mix(in srgb, var(--destaque, #1a5f9e) 22%, #d8dee6);
  --svc-mi-fundo: color-mix(in srgb, var(--superficie, #fff) 92%, var(--clara, #f3f6f8));
  --svc-mi-tinta: var(--tinta, #1f2933);
  --svc-mi-suave: var(--suave, #5b6b76);
  --svc-mi-acento: var(--destaque, var(--cor, #1a5f9e));
  margin: 12px 0 20px;
  padding: 18px 20px 16px;
  border: 1px solid var(--svc-mi-borda);
  border-radius: 14px;
  background: var(--svc-mi-fundo);
  color: var(--svc-mi-tinta);
  max-width: 1100px;
  box-sizing: border-box;
}
.svc-module-index__badge{
  display:inline-block; margin:0 0 10px; padding:3px 8px; border-radius:999px;
  font-size:.68rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase;
  color: var(--svc-mi-acento);
  background: color-mix(in srgb, var(--svc-mi-acento) 10%, transparent);
  border: 1px solid color-mix(in srgb, var(--svc-mi-acento) 22%, transparent);
}
.svc-module-index__head{ display:flex; gap:12px; align-items:flex-start; margin-bottom:12px; }
.svc-module-index__ico{
  width:40px; height:40px; border-radius:12px; flex:0 0 auto;
  display:grid; place-items:center; color:#fff;
  background: var(--svc-mi-acento); font-size:1rem;
}
.svc-module-index__titulo{ margin:0; font-size:1.15rem; font-weight:700; color: var(--svc-mi-tinta); line-height:1.25; }
.svc-module-index__lead{ margin:4px 0 0; font-size:.86rem; color: var(--svc-mi-suave); line-height:1.45; max-width:62ch; }
.svc-module-index__meta{ margin:8px 0 0; font-size:.75rem; color: var(--svc-mi-suave); }
.svc-module-index__meta strong{ color: var(--svc-mi-tinta); font-weight:600; }
.svc-module-index__grupos{ display:grid; gap:14px; margin-top:14px; }
.svc-module-index__grupo-titulo{
  margin:0 0 8px; font-size:.72rem; font-weight:700; letter-spacing:.05em;
  text-transform:uppercase; color: var(--svc-mi-suave);
}
.svc-module-index__lista{
  list-style:none; margin:0; padding:0;
  display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:8px;
}
.svc-module-index__lista a{
  display:block; padding:10px 12px; border-radius:10px; text-decoration:none;
  border: 1px solid color-mix(in srgb, var(--svc-mi-borda) 80%, transparent);
  background: color-mix(in srgb, #fff 70%, var(--svc-mi-fundo));
  color: var(--svc-mi-acento) !important; font-weight:600; font-size:.88rem; line-height:1.3;
  transition: border-color .15s, background .15s;
}
.svc-module-index__lista a:hover{
  border-color: color-mix(in srgb, var(--svc-mi-acento) 40%, var(--svc-mi-borda));
  background: color-mix(in srgb, var(--svc-mi-acento) 7%, #fff);
}
.svc-module-index__lista small{
  display:block; margin-top:3px; font-weight:500; font-size:.72rem;
  color: var(--svc-mi-suave); line-height:1.3;
}
.svc-module-index__vazio{
  margin:8px 0 0; padding:12px 14px; border-radius:10px; font-size:.86rem;
  color: var(--svc-mi-suave);
  border: 1px dashed var(--svc-mi-borda);
  background: color-mix(in srgb, var(--svc-mi-fundo) 80%, #fff);
}
html[data-buriti-modo="escuro"] .svc-module-index,
body.tema-buriti-skin[data-buriti-modo="escuro"] .svc-module-index{
  --svc-mi-fundo: color-mix(in srgb, var(--superficie, #1a2332) 90%, #0f1720);
  --svc-mi-borda: color-mix(in srgb, var(--destaque, #5b9fd4) 28%, #2a3544);
}
html[data-buriti-modo="escuro"] .svc-module-index__lista a,
body.tema-buriti-skin[data-buriti-modo="escuro"] .svc-module-index__lista a{
  background: color-mix(in srgb, var(--superficie, #1a2332) 85%, #000);
}
</style>

<!-- CUSTOMIZAÇÃO: {{ $customId }} · {{ $customLabel }} -->
<section class="svc-module-index" data-customizacao="{{ $customId }}" data-processo="{{ $processo }}" aria-label="Painel do módulo {{ $titulo }}">
    <p class="svc-module-index__badge">{{ $customLabel }}</p>
    <header class="svc-module-index__head">
        <span class="svc-module-index__ico" aria-hidden="true"><i class="fa {{ $icone }}"></i></span>
        <div>
            <h2 class="svc-module-index__titulo">{{ $titulo }}</h2>
            <p class="svc-module-index__lead">{{ $descricao }}</p>
            <p class="svc-module-index__meta">
                Perfil: <strong>{{ $perfil }}</strong>
                · {{ $total }} opção(ões) liberada(s)
                · processo {{ $processo }}
            </p>
        </div>
    </header>

    @if($total > 0)
        <div class="svc-module-index__grupos">
            @foreach($grupos as $grupo)
                <div>
                    <h3 class="svc-module-index__grupo-titulo">{{ $grupo['titulo'] }}</h3>
                    <ul class="svc-module-index__lista">
                        @foreach($grupo['itens'] as $item)
                            <li>
                                <a href="{{ $item['url'] }}">
                                    {{ $item['titulo'] }}
                                    @if(!empty($item['descricao']) && $item['descricao'] !== $item['titulo'])
                                        <small>{{ $item['descricao'] }}</small>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @else
        <p class="svc-module-index__vazio">
            Nenhuma tela deste módulo está liberada para o seu tipo de usuário.
            Peça ao administrador para revisar as permissões em <strong>Configurações → Tipos de usuário</strong>.
        </p>
    @endif
</section>
