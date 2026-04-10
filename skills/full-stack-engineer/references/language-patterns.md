# Language & Framework Patterns

Quick-reference for idiomatic patterns, project structures, and best practices per language/framework. Apply these when the project uses (or chooses) one of these stacks.

---

## PHP / Laravel

### Project Structure (Standard Laravel)
```
app/
├── Console/Commands/         # Artisan commands
├── Http/
│   ├── Controllers/          # Thin controllers — delegate to services
│   ├── Middleware/            # Request/response pipeline
│   ├── Requests/             # Form request validation classes
│   └── Resources/            # API resources (JSON transformations)
├── Models/                   # Eloquent models
├── Services/                 # Business logic layer
├── Repositories/             # Data access abstraction (optional)
├── Events/ + Listeners/      # Event-driven decoupling
├── Jobs/                     # Queueable jobs
├── Mail/                     # Mailable classes
├── Notifications/            # Multi-channel notifications
├── Policies/                 # Authorization policies
└── Providers/                # Service providers
database/
├── migrations/               # Timestamped, forward-only
├── seeders/                  # Dev/test data
└── factories/                # Model factories for testing
resources/views/              # Blade templates
routes/
├── web.php                   # Web routes (session, CSRF)
├── api.php                   # API routes (stateless, token auth)
└── console.php               # Scheduled commands
tests/
├── Feature/                  # Integration/HTTP tests
└── Unit/                     # Isolated unit tests
```

### Key Patterns
- **Thin controllers**: Controllers validate input and return responses. Business logic lives in Service classes.
- **Form Request validation**: Never validate in controllers — use `FormRequest` classes.
- **Eloquent scopes**: Use query scopes on models for reusable query logic.
- **Eager loading**: Always eager-load relationships to avoid N+1 (`with()`).
- **Mass assignment protection**: Use `$fillable` or `$guarded` on every model.
- **Events for side effects**: Use events/listeners instead of chaining logic in controllers.
- **Queue heavy work**: Email, notifications, PDF generation → dispatch as queued jobs.
- **Config caching**: `php artisan config:cache` in production.
- **Route model binding**: Use implicit route model binding for cleaner controllers.

### Testing
```php
// Feature test pattern
$this->actingAs($user)
    ->postJson('/api/employees', $payload)
    ->assertStatus(201)
    ->assertJsonStructure(['data' => ['id', 'name']]);

// Use factories + database transactions
Employee::factory()->count(5)->create();
```

### Security
- CSRF: Automatic via `@csrf` in Blade forms; `VerifyCsrfToken` middleware.
- SQL injection: Eloquent and query builder use parameterized queries by default. Never use `DB::raw()` with user input.
- XSS: Blade `{{ }}` auto-escapes. Only use `{!! !!}` for trusted HTML.
- Auth: Use `Gate`/`Policy` for authorization, not manual checks.
- Mass assignment: Always define `$fillable`.

---

## JavaScript / TypeScript (Node.js)

### Project Structure (Express/Fastify/NestJS)
```
src/
├── config/                   # Environment config, validation
├── modules/                  # Feature modules
│   └── users/
│       ├── users.controller.ts
│       ├── users.service.ts
│       ├── users.repository.ts
│       ├── users.schema.ts    # Validation (Zod/Joi)
│       ├── users.types.ts
│       └── __tests__/
├── middleware/                # Auth, logging, error handling
├── shared/                   # Shared utilities, types, constants
├── database/
│   ├── migrations/
│   └── seeds/
└── index.ts                  # Entry point
```

### Key Patterns
- **Strict TypeScript**: Enable `strict: true` in tsconfig. No `any` unless absolutely necessary.
- **Zod/Joi validation**: Validate all external input at the boundary (request body, query params, env vars).
- **Async/await everywhere**: No callback-style code. Use `Promise.all()` for parallel operations.
- **Error handling middleware**: Centralized error handler; throw typed errors from services.
- **Dependency injection**: Use constructor injection or a DI container (NestJS, tsyringe, awilix).
- **Environment validation**: Validate all env vars at startup with Zod/Joi — fail fast.
- **Connection pooling**: Use connection pools for database (pg-pool, Knex, Prisma).
- **Graceful shutdown**: Handle SIGTERM/SIGINT — drain connections, finish requests.

### Testing
```typescript
// Jest/Vitest pattern
describe('UserService', () => {
  it('creates a user with hashed password', async () => {
    const user = await userService.create({ email: 'a@b.com', password: 'secret' });
    expect(user.passwordHash).not.toBe('secret');
    expect(await bcrypt.compare('secret', user.passwordHash)).toBe(true);
  });
});
```

### Security
- SQL injection: Use parameterized queries (Prisma, Knex, pg with `$1` placeholders). Never template strings.
- XSS: Use `helmet` middleware. Sanitize HTML output with DOMPurify if rendering user content.
- CSRF: Use `csurf` or SameSite cookies for session-based auth. Not needed for token-based APIs.
- Rate limiting: `express-rate-limit` or `@fastify/rate-limit`.
- Dependencies: Run `npm audit` regularly. Use `socket.dev` or Snyk for supply chain security.

---

## Python (Django / FastAPI / Flask)

### Project Structure (Django)
```
project/
├── config/                   # settings, urls, wsgi, asgi
│   ├── settings/
│   │   ├── base.py
│   │   ├── development.py
│   │   └── production.py
│   └── urls.py
├── apps/
│   └── users/
│       ├── models.py
│       ├── views.py (or viewsets.py)
│       ├── serializers.py
│       ├── services.py
│       ├── urls.py
│       ├── admin.py
│       └── tests/
├── core/                     # Shared base classes, utils
└── manage.py
```

### Project Structure (FastAPI)
```
app/
├── main.py                   # FastAPI app, middleware, startup
├── config.py                 # Pydantic Settings
├── routers/                  # Route handlers
├── services/                 # Business logic
├── models/                   # SQLAlchemy / Pydantic models
├── schemas/                  # Pydantic request/response schemas
├── dependencies/             # Dependency injection
└── tests/
```

### Key Patterns
- **Type hints everywhere**: Use `mypy` or `pyright` for static checking.
- **Pydantic models**: Validate all input with Pydantic (FastAPI) or serializers (Django REST).
- **Service layer**: Keep views/routes thin. Business logic in service functions/classes.
- **Alembic migrations**: For SQLAlchemy projects. Django has built-in migrations.
- **Async where beneficial**: FastAPI is async-first. Django supports async views (4.1+).
- **Virtual environments**: Always. Use `poetry`, `uv`, or `venv`.
- **Settings from env**: `pydantic-settings`, `django-environ`, or `python-decouple`.

### Security
- SQL injection: Django ORM and SQLAlchemy use parameterized queries. Never use `.raw()` or `text()` with f-strings.
- XSS: Django auto-escapes templates. Mark trusted HTML explicitly with `|safe`.
- CSRF: Django CSRF middleware is on by default. Enable for DRF session auth.
- Auth: Use `django.contrib.auth` or `fastapi-users`. Never roll custom password hashing.
- Secrets: Use environment variables. Never commit `.env` files.

---

## Go

### Project Structure
```
cmd/
├── api/
│   └── main.go               # Entry point
internal/
├── config/                    # Configuration
├── handler/                   # HTTP handlers (controllers)
├── service/                   # Business logic
├── repository/                # Database access
├── model/                     # Domain models
├── middleware/                 # HTTP middleware
└── pkg/                       # Internal shared packages
migrations/                    # SQL migration files
```

### Key Patterns
- **Accept interfaces, return structs**: Define interfaces at the consumer, not the provider.
- **Table-driven tests**: Use subtests with `t.Run()` for comprehensive test matrices.
- **Error handling**: Check every error. Wrap with `fmt.Errorf("context: %w", err)`.
- **Context propagation**: Pass `context.Context` as first parameter everywhere.
- **Graceful shutdown**: Use `signal.NotifyContext` + `http.Server.Shutdown()`.
- **No globals**: Inject dependencies via struct fields.
- **sqlx or pgx**: Preferred over raw `database/sql` for ergonomics. Use prepared statements.

### Security
- SQL injection: Use `sqlx` named queries (`sqlx.NamedExec`) or `$1` placeholders. Never `fmt.Sprintf` for SQL.
- Input validation: Use `go-playground/validator` struct tags.
- CORS: Use `rs/cors` middleware with explicit allowed origins.
- Secrets: Use environment variables. `godotenv` for development only.

---

## Java / Kotlin (Spring Boot)

### Project Structure
```
src/main/java/com/example/app/
├── config/                    # Bean configuration, security config
├── controller/                # REST controllers (thin)
├── service/                   # Business logic
├── repository/                # Spring Data JPA repositories
├── model/
│   ├── entity/                # JPA entities
│   └── dto/                   # Request/response DTOs
├── exception/                 # Custom exceptions + global handler
├── security/                  # Auth filters, JWT provider
└── Application.java           # Entry point
src/main/resources/
├── application.yml            # Config (with profiles)
├── db/migration/              # Flyway migrations
```

### Key Patterns
- **Constructor injection**: No `@Autowired` on fields. Use `@RequiredArgsConstructor` (Lombok) or explicit constructors.
- **DTO separation**: Never expose JPA entities in API responses. Use record classes or DTOs.
- **Spring Profiles**: `application-dev.yml`, `application-prod.yml` for environment config.
- **Flyway or Liquibase**: Versioned SQL migrations. Never use `spring.jpa.hibernate.ddl-auto=update` in production.
- **Global exception handler**: `@ControllerAdvice` with `@ExceptionHandler` methods.
- **Validation**: Use `jakarta.validation` annotations (`@NotNull`, `@Size`, `@Email`) on DTOs.

### Security
- SQL injection: Spring Data JPA and `@Query` with named parameters are safe. Never concatenate strings in JPQL.
- CSRF: Spring Security enables CSRF by default for session-based auth. Disable only for stateless token APIs.
- Auth: Use Spring Security filter chain. Configure `SecurityFilterChain` bean.
- Secrets: Use Spring Cloud Config, Vault, or environment variables. Never hardcode.

---

## C# / .NET

### Project Structure
```
src/
├── Api/                       # ASP.NET Core Web API project
│   ├── Controllers/
│   ├── Middleware/
│   ├── Program.cs
│   └── appsettings.json
├── Application/               # Business logic (Clean Architecture)
│   ├── Services/
│   ├── Interfaces/
│   └── DTOs/
├── Domain/                    # Entities, value objects, domain events
├── Infrastructure/            # EF Core DbContext, repositories, external services
│   ├── Data/
│   │   ├── Migrations/
│   │   └── AppDbContext.cs
│   └── Services/
tests/
├── UnitTests/
└── IntegrationTests/
```

### Key Patterns
- **Minimal APIs or Controllers**: Minimal APIs for simple endpoints; controllers for complex routes.
- **Dependency injection**: Built into ASP.NET Core. Register in `Program.cs` or extension methods.
- **EF Core migrations**: `dotnet ef migrations add Name` → `dotnet ef database update`.
- **Options pattern**: Bind config sections to strongly-typed classes with `IOptions<T>`.
- **MediatR**: Consider for CQRS pattern (commands/queries as objects).
- **FluentValidation**: Validate request DTOs with fluent rules.
- **Global error handling**: Use middleware or `IExceptionHandler` (.NET 8+).

### Security
- SQL injection: EF Core LINQ is safe. Use `FromSqlInterpolated()` (not `FromSqlRaw()`) for raw SQL.
- XSS: Razor auto-encodes. Use `@Html.Raw()` only for trusted content.
- CSRF: Enabled by default in Razor Pages / MVC. Use `[ValidateAntiForgeryToken]`.
- Auth: ASP.NET Core Identity + JWT Bearer or cookie auth.
- Secrets: Use `dotnet user-secrets` for dev. Key Vault or env vars in production.

---

## Rust

### Project Structure
```
src/
├── main.rs                    # Entry point
├── config.rs                  # Configuration
├── routes/                    # HTTP route handlers (Actix/Axum)
├── services/                  # Business logic
├── models/                    # Domain types + DB models (Diesel/SQLx)
├── db/                        # Connection pool, migrations
├── middleware/                 # Tower middleware (Axum) / Actix middleware
└── errors.rs                  # Custom error types with From impls
migrations/                    # Diesel or SQLx migrations
```

### Key Patterns
- **Type-driven design**: Encode invariants in types. Use newtypes for domain concepts.
- **Error handling**: Use `thiserror` for library errors, `anyhow` for application errors. Implement `IntoResponse` for HTTP errors.
- **Async runtime**: Tokio (default). Use `tokio::spawn` for concurrent tasks.
- **SQLx compile-time checks**: Use `sqlx::query!` macro for compile-time verified SQL.
- **Connection pooling**: `sqlx::PgPool` or `deadpool`.
- **Serde for serialization**: `#[derive(Serialize, Deserialize)]` on all API types.
- **Clippy**: Run `cargo clippy` — treat all warnings as errors in CI.

### Security
- Memory safety: Rust's ownership system prevents most memory bugs. No `unsafe` without justification.
- SQL injection: SQLx parameterized queries. Never format SQL strings.
- Input validation: Use `validator` crate with derive macros.
- Dependencies: `cargo audit` for vulnerability scanning.
