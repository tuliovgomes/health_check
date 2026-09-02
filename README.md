# Health Check

Plataforma web para monitoramento automatizado da disponibilidade e do desempenho de links.

O sistema executa verificacoes periodicas, mede o tempo de resposta, registra o historico de cada link e envia notificacoes quando ocorre uma indisponibilidade, erro ou alteracao relevante de status.

## Recursos

* Cadastro, visualizacao e remocao de links monitorados.
* Verificacoes automaticas a cada 1, 5, 15, 30 ou 60 minutos.
* Classificacao dos links como saudaveis, lentos ou indisponiveis.
* Historico de verificacoes com status HTTP, tempo de resposta e mensagens de erro.
* Processamento assincrono por filas e execucao concorrente de requisicoes HTTP.
* Notificacoes por e-mail, Slack e Discord.
* Teste das integracoes configuradas.
* Autenticacao de usuarios e verificacao de e-mail.
* Planos Free, Starter e Unlimited, com limites de links, historico e integracoes.
* Integracao opcional com Stripe para assinaturas.

## Stack

* PHP 8.2+
* Laravel 12
* Laravel Fortify
* Laravel Cashier e Stripe (opcional)
* Vue 3
* Inertia.js
* TypeScript
* Tailwind CSS
* Vite
* Pest
* SQLite por padrao no ambiente local

## Requisitos

Antes de iniciar, instale:

* PHP 8.2 ou superior
* Composer
* Node.js e npm
* Uma extensao PHP para SQLite habilitada

## Instalacao

Clone o repositorio e entre na pasta do projeto:

```bash
git clone https://github.com/tuliovgomes/health_check.git
cd health_check
```

Instale as dependencias e prepare a aplicacao:

```bash
composer run setup
```

Esse comando instala as dependencias PHP, cria o arquivo `.env` , gera a chave da aplicacao, executa as migrations, instala os pacotes JavaScript e gera o build de producao.

## Configuracao

O projeto utiliza SQLite e fila em banco por padrao. Para uma instalacao local, os valores do `.env.example` normalmente sao suficientes.

Para habilitar notificacoes reais, configure o mailer desejado e as credenciais das integracoes. Para ativar assinaturas reais, informe tambem:

```env
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_PRICE_STARTER=
STRIPE_PRICE_UNLIMITED=
```

Nunca versiona credenciais, tokens ou chaves privadas no repositorio.

## Executando localmente

Para iniciar servidor, fila e Vite simultaneamente:

```bash
composer run dev
```

A aplicacao ficara disponivel, por padrao, em `http://localhost:8000` .

Se preferir executar cada processo separadamente:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

Em outro terminal, mantenha o scheduler ativo para disparar as verificacoes automaticas:

```bash
php artisan schedule:work
```

## Verificacoes de links

O comando principal e:

```bash
php artisan health:check
```

Ele verifica os links vencidos de todos os intervalos configurados. Para executar somente um intervalo:

```bash
php artisan health:check 5
```

O tamanho padrao de cada lote e 50 links. Esse valor pode ser alterado:

```bash
php artisan health:check 1 --batch-size=100
```

Os intervalos aceitos sao `1` , `5` , `15` , `30` e `60` . Cada lote e enviado para a fila `health-checks` , e as requisicoes do lote sao disparadas concorrentemente.

## Agendamento

O scheduler executa automaticamente:

| Intervalo | Frequencia |
| --- | --- |
| 1 minuto | a cada minuto |
| 5 minutos | a cada cinco minutos |
| 15 minutos | a cada quinze minutos |
| 30 minutos | a cada trinta minutos |
| 60 minutos | a cada hora |

Em producao, configure o cron do Laravel para executar a cada minuto:

```cron
* * * * * cd /caminho/para/health_check && php artisan schedule:run >> /dev/null 2>&1
```

Tambem e necessario manter um worker da fila em execucao. O worker deve consumir a fila padrao e a fila `health-checks` para processar tanto as verificacoes quanto as notificacoes.

## Testes e qualidade

Execute a suite completa:

```bash
composer run test
```

Para executar somente os testes Pest:

```bash
php artisan test
```

Para verificar a formatacao PHP:

```bash
composer run test:lint
```

## Estrutura principal

```text
app/
  Console/Commands/       Comandos Artisan de monitoramento
  Jobs/                   Jobs para verificacoes assincronas
  Models/                 Usuarios, links, verificacoes e integracoes
  Services/               Regras de verificacao e notificacao
  Http/Controllers/       Endpoints da aplicacao
resources/js/             Interface Vue com Inertia.js
database/                 Migrations, factories e seeders
tests/                    Testes automatizados
```

## Status de saude

Cada verificacao registra o resultado da requisicao HTTP, o tempo de resposta e, quando aplicavel, o erro ocorrido. Esses dados permitem acompanhar a disponibilidade e a latencia dos links ao longo do tempo.

## Licenca

Este projeto esta licenciado sob a licenca MIT.
