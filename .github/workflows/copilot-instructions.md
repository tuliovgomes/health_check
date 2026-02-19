🧠 GitHub Copilot Instructions – Laravel 12
📌 Contexto do Projeto

Este é um projeto Laravel 12 utilizando:
PHP 8.3+
MySQL
Redis (cache + filas)
Laravel Horizon
Blade
Bootstrap 5
jQuery 3.x (legado)
Vue 2 (componentes específicos)
APIs RESTful com Laravel Sanctum
O projeto segue princípios de Clean Code, SOLID e Service Layer.

🧱 Arquitetura e Organização
Controllers
Devem ser finos
Responsáveis apenas por:
Receber Request
Chamar Form Request
Delegar para Services
Retornar API Resources ou Views
NÃO conter regra de negócio
Services
Conter toda a regra de negócio
Devem ser injetados via DI
Devem ser reutilizáveis
Devem ser testáveis (unit tests)
Form Requests
Toda validação deve ficar em Form Requests
NÃO validar diretamente no Controller
DTOs
Usar para transporte de dados entre camadas quando necessário
Objetos imutáveis sempre que possível
Repositories
Utilizar apenas quando houver necessidade real de abstração
Caso contrário, usar Eloquent diretamente nos Services

🗂️ Estrutura de Pastas Esperada
app/
 ├── Actions/
 ├── DTOs/
 ├── Enums/
 ├── Events/
 ├── Exceptions/
 ├── Jobs/
 ├── Listeners/
 ├── Services/
 ├── Traits/

🔄 Filas e Jobs

Tudo que for pesado deve ser processado em Jobs
Jobs devem implementar ShouldQueue
Usar Redis como driver
Garantir idempotência
O método handle() deve apenas delegar para um Service
Nomear Jobs com verbo de ação:
ProcessUserAssessmentJob
SendAssessmentEmailJob

📡 Padrão de APIs REST
Responses padronizadas
{
  "success": true,
  "data": {},
  "message": "",
  "meta": {}
}

Regras
Usar API Resources
Nunca retornar models Eloquent diretamente
Usar status codes corretos
Implementar paginação com paginate()
Versionar rotas: /api/v1/...

🔐 Autenticação e Autorização
Usar Laravel Sanctum
Usar Policies para autorização
Não verificar permissões diretamente em Controllers

🗃️ Banco de Dados
Criar sempre:
Migrations
Factories
Seeders (quando necessário)
Utilizar softDeletes() quando fizer sentido
Padrão de nomes:
Tabelas no plural
Foreign keys: user_id, company_id

🧪 Testes
Utilizar Pest
Prioridades:
Feature tests para endpoints
Unit tests para Services
Usar RefreshDatabase
Utilizar factories (nunca dados fixos)

🧭 Convenções de Nomenclatura
Tipo	Padrão
Service	UserAssessmentService
Job	GenerateAssessmentReportJob
Event	UserAssessmentCreated
Listener	SendAssessmentNotification
DTO	AssessmentData
FormRequest	StoreUserAssessmentRequest

🖥️ Frontend

Blade como padrão
Vue 3 apenas para reatividade necessária
jQuery apenas para legado
Preferir Axios para novas requisições AJAX
Componentes Vue em:
resources/js/components

🎯 Boas Práticas de Código
Tipagem explícita sempre que possível
Retornos tipados
Evitar Facades (preferir DI)
Não usar lógica de negócio em helpers globais
Utilizar Enums do PHP para estados fixos
Utilizar Value Objects para dados complexos
Usar config() ao invés de valores hardcoded
Usar transactions em operações críticas
Logar exceções com contexto

🚫 Evitar

Lógica em Controllers
Queries complexas em Blade
Código duplicado
Regras de negócio em Jobs

Validação fora de Form Requests

🔄 Fluxo Arquitetural Esperado
Controller
 → FormRequest
 → Service
 → (Repository)
 → Event
 → Listener
 → Job

📣 Diretrizes para o Copilot

Ao sugerir código:
Seguir os padrões definidos acima
Priorizar legibilidade e testabilidade
Sugerir uso de Services ao invés de lógica em Controllers
Sugerir Form Requests para validação
Sugerir API Resources para respostas de API
Sugerir Jobs para processamento pesado
Evitar soluções rápidas que violem a arquitetura

🧩 Exemplo de Service
class CreateUserAssessmentService
{
    public function execute(AssessmentData $data): UserAssessment
    {
        return DB::transaction(function () use ($data) {
            $assessment = UserAssessment::create($data->toArray());

            event(new UserAssessmentCreated($assessment));

            return $assessment;
        });
    }
}

🧪 Exemplo de Teste (Pest)
it('creates a user assessment', function () {
    $data = UserAssessment::factory()->make()->toArray();

    $response = $this->postJson('/api/v1/assessments', $data);

    $response->assertCreated()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('user_assessments', [
        'user_id' => $data['user_id'],
    ]);
});

✅ Objetivo

Garantir:
Código limpo
Baixo acoplamento
Alta coesão
Facilidade de manutenção
Escalabilidade
Cobertura de testes