# GitHub Copilot Instructions – Laravel 12 + Vue 3 + TypeScript

## 🧠 Project Context

This is a **Laravel 12** application using:

* PHP 8.3+
* Vue 3 (Composition API)
* TypeScript
* Vite
* Inertia.js
* Multi-tenant architecture
* REST APIs
* Queues, Jobs, Events, and Listeners
* Pest for testing

Copilot must prioritize **clean architecture**, **SOLID principles**, **DDD-style separation**, and **testability**.

---

## 📁 Backend Architecture (Laravel)

### Layers

Controllers must be thin and delegate logic to:

* `Actions` → Single responsibility business actions
* `Services` → Complex business rules
* `Repositories` → Data access abstraction (only when needed)
* `DTOs` → Structured data transfer between layers
* `Policies` → Authorization rules
* `Form Requests` → Validation

Avoid placing business logic inside controllers or models.

---

### Example Structure

```
app/
 ├── Actions/
 ├── DTOs/
 ├── Services/
 ├── Repositories/
 ├── Policies/
 ├── Jobs/
 ├── Events/
 ├── Listeners/
 └── Http/
      ├── Controllers/
      ├── Requests/
      └── Resources/
```

---

### Coding Standards

* Use **strict types** in PHP files
* Prefer **constructor property promotion**
* Use **readonly properties** where possible
* Return **typed responses**
* Use **API Resources** for JSON responses
* Use **Enums** instead of string constants
* Use **CarbonImmutable** for dates

---

### Queues & Jobs

* Jobs must implement `ShouldQueue`
* Use `dispatch()` instead of direct execution
* Avoid heavy logic inside controllers — move to Jobs
* Use `retryUntil()` for time-sensitive jobs
* Use `batch()` for bulk processing

---

### Events

Use events for:

* Domain state changes
* Decoupling side effects
* Triggering async workflows

Avoid placing business logic directly inside listeners — delegate to Actions.

---

### Validation

Always use **Form Request classes**.

Rules must:

* Use typed rules
* Extract reusable rules into custom rule objects
* Avoid inline validation inside controllers

---

### Authorization

* Use **Policies**
* Never check permissions manually inside controllers
* Use `$this->authorize()` or `can()` middleware

---

### API Design

* Follow REST conventions
* Use plural resource names
* Use proper HTTP status codes
* Return consistent JSON structure:

```
{
  "data": {},
  "meta": {},
  "errors": []
}
```

---

### Testing (Pest)

Write tests for:

* Actions
* Jobs
* API endpoints
* Multi-tenant isolation

Guidelines:

* Use `RefreshDatabase`
* Use factories
* Avoid hitting external services (mock them)
* Prefer **feature tests** over unit tests for HTTP flows

---

## 🧩 Frontend Architecture (Vue 3 + TypeScript)

### Rules

* Use **Composition API**
* Use `<script setup lang="ts">`
* Strong typing for props, emits, and API responses
* Use `interfaces` or `types` for DTO mirroring backend
* Keep components small and focused

---

### Folder Structure

```
resources/js/
 ├── Components/
 ├── Pages/
 ├── Composables/
 ├── Services/   // API calls
 ├── Types/      // TypeScript interfaces
 └── Utils/
```

---

### API Consumption

* All HTTP calls must be inside `Services/`
* Never call API directly inside components
* Use typed responses
* Handle errors centrally

---

### State Management

Use:

* Vue composables for local state
* Pinia only when global state is required

---

### Forms

* Use controlled inputs
* Use reusable form components
* Extract validation logic
* Show backend validation errors

---

### UI/UX

* Use loading states for async operations
* Disable buttons during requests
* Show toast notifications for success/error
* Use skeleton loaders when possible

---

## 🔐 Security

Backend:

* Never trust frontend input
* Always validate and authorize
* Escape output when necessary
* Use rate limiting on sensitive endpoints

Frontend:

* Never store sensitive data in localStorage
* Use CSRF protection via Laravel

---

## ⚡ Performance

* Use eager loading to avoid N+1
* Use pagination for large datasets
* Cache heavy queries
* Use queues for heavy processing
* Debounce search inputs

---

## 🧰 Code Generation Rules for Copilot

When generating code:

1. Always use **typed PHP signatures**
2. Prefer **Action classes** for business logic
3. Use **Form Requests** for validation
4. Use **API Resources** for responses
5. Follow **multi-tenant safety**
6. Use **DTOs** for structured data
7. Write **Pest tests** alongside new features
8. Generate **TypeScript types** matching backend resources
9. Use **Services layer** for API calls in Vue
10. Avoid duplicated logic between backend and frontend

---

## 🧪 Example Patterns Copilot Should Follow

### Controller Pattern

```php
public function store(StoreUserRequest $request, CreateUserAction $action): JsonResponse
{
    $user = $action->execute(UserData::fromRequest($request));

    return response()->json([
        'data' => new UserResource($user)
    ], 201);
}
```

---

### Action Pattern

```php
class CreateUserAction
{
    public function execute(UserData $data): User
    {
        return DB::transaction(fn () =>
            User::create($data->toArray())
        );
    }
}
```

---

### Vue Service Pattern

```ts
export async function createUser(payload: CreateUserDTO): Promise<UserDTO> {
  const { data } = await api.post('/users', payload)
  return data.data
}
```

---

### Vue Component Pattern

```vue
<script setup lang="ts">
import { ref } from 'vue'
import { createUser } from '@/Services/UserService'
import type { CreateUserDTO } from '@/Types/User'

const form = ref<CreateUserDTO>({
  name: '',
  email: '',
})

const loading = ref(false)

async function submit() {
  loading.value = true
  try {
    await createUser(form.value)
  } finally {
    loading.value = false
  }
}
</script>
```

---

## 🚫 Anti-Patterns Copilot Must Avoid

* Fat controllers
* Business logic in models
* Direct DB queries inside controllers
* Inline validation
* Using `any` in TypeScript
* Duplicated API calls in components
* Ignoring tenant scope
* Not using API Resources

---

## ✅ Definition of Done

A feature is complete only if:

* Has Action/Service layer
* Has Form Request validation
* Has Policy authorization
* Has API Resource
* Has Pest test
* Has typed Vue integration
* Respects multi-tenant boundaries
* Uses queues if heavy

---

## 📌 Summary

Copilot must generate:

* Clean, typed, testable code
* Multi-tenant safe queries
* RESTful APIs
* Vue 3 Composition API with TypeScript
* Separation of concerns
* Scalable and maintainable patterns
