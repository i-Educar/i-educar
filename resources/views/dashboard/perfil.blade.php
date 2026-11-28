@extends('dashboard.layout')

@section('topo-titulo', 'Meu perfil')

@section('conteudo')
<div class="miolo-inner">
    <section class="hero-painel">
        <div>
            <p class="eyebrow">Conta · {{ $municipio }}</p>
            <h1>{{ $perfil['nome'] }}</h1>
            <p class="lead">Informações pessoais e vínculos desta conta no município.</p>
        </div>
        <div class="hero-meta">
            @if(!empty($perfil['tipo']))
                <span class="chip papel"><i class="fa fa-user"></i> {{ $perfil['tipo'] }}</span>
            @endif
            <span class="chip"><i class="fa fa-{{ ($perfil['ativo'] ?? false) ? 'check' : 'times' }}"></i> {{ ($perfil['ativo'] ?? false) ? 'Ativo' : 'Inativo' }}</span>
            <a class="botao claro" href="{{ url('/painel') }}"><i class="fa fa-arrow-left"></i> Voltar ao painel</a>
        </div>
    </section>

    <section class="bloco" style="flex:1">
        <h2><i class="fa fa-user" style="color:var(--cor)"></i> Dados da conta</h2>
        <div class="corpo">
            <div class="perfil-grid">
                <div class="campo">
                    <span>Nome</span>
                    <strong>{{ $perfil['nome'] }}</strong>
                </div>
                <div class="campo">
                    <span>Login</span>
                    <strong>{{ $perfil['login'] ?? '—' }}</strong>
                </div>
                <div class="campo">
                    <span>E-mail</span>
                    <strong>{{ $perfil['email'] ?? '—' }}</strong>
                </div>
                <div class="campo">
                    <span>Tipo de usuário</span>
                    <strong>{{ $perfil['tipo'] ?? '—' }}</strong>
                </div>
                <div class="campo">
                    <span>Nível de acesso</span>
                    <strong>{{ $perfil['nivel'] ?? '—' }}</strong>
                </div>
                <div class="campo">
                    <span>Situação</span>
                    <strong>
                        <span class="badge {{ $perfil['ativo'] ? 'ok' : 'erro' }}">
                            {{ $perfil['ativo'] ? 'Ativo' : 'Inativo' }}
                        </span>
                    </strong>
                </div>
                @if(!empty($perfil['data_cadastro']))
                    <div class="campo">
                        <span>Cadastro</span>
                        <strong>{{ $perfil['data_cadastro'] }}</strong>
                    </div>
                @endif
                <div class="campo" style="grid-column: 1 / -1">
                    <span>Escolas vinculadas</span>
                    <strong>
                        @if(($perfil['escolas'] ?? []) === [])
                            {{ ($perfil['nivel'] ?? '') === 'Escola' ? 'Nenhuma escola vinculada' : 'Acesso em nível de rede / SME' }}
                        @else
                            {{ implode(' · ', $perfil['escolas']) }}
                        @endif
                    </strong>
                </div>
            </div>
        </div>
    </section>

    <section class="bloco">
        <h2><i class="fa fa-external-link" style="color:var(--destaque)"></i> Ações pessoais</h2>
        <div class="atalhos">
            @foreach($perfil['links'] as $link)
                <a href="{{ $link['url'] }}">
                    <span class="ico"><i class="fa {{ $link['icone'] }}"></i></span>
                    <span>
                        <strong>{{ $link['rotulo'] }}</strong>
                        <small>Abrir formulário / tela relacionada</small>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
</div>
@endsection
