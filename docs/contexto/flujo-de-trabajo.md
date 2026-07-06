# Flujo de trabajo

## Antes de tocar nada

1. Revisar (si son relevantes a la tarea) `funcionalidades.md`, `Lista_test.md`, `plan-ejecucion.md`, `planning.md`, `vps_deploy_guide.md`, `vps_plan.md`, `NEGOCIO.md` — Claude Code los carga automáticamente al iniciar sesión vía hook `SessionStart`.
2. Confirmar en qué rama se está trabajando: el flujo real es `agent` (desarrollo) → PR a `develop` (staging) → PR a `main` (producción). Ver `vps_deploy_guide.md` §"Flujo de trabajo diario".
3. Si la tarea toca el dominio de cobranza (Clientes, Contratos, Pagos, etc.), releer la sección correspondiente de `NEGOCIO.md` antes de generar el modelo — define a qué grupo de borrado/estado pertenece la entidad.

## Para hacer un cambio

1. **Backend:** Form Request para validación → Service (una responsabilidad por método) → Repository para queries → Controller delgado → API Resource. Nunca query directa en el controlador, nunca `env()` fuera de `config/`.
2. **Frontend:** servicio de dominio con signals (`items`, `loading`, `showTrashed`) → componente de listado + componente de formulario separados, `OnPush` + `inject()` + `takeUntilDestroyed(this.destroyRef)`. Toda petición vía `ApiService`, nunca `HttpClient` directo.
3. Agregar/actualizar tests junto al código (`tests/Feature|Unit/` en backend, `*.spec.ts` junto al archivo en frontend) y documentarlos en `recaudify-api/pruebas.md` / `recaudify-web/pruebas.md`.
4. El formateo (Pint para `.php`, Prettier para `.ts/.html/.scss/.css`) corre automático al guardar vía hooks de Claude Code — no hace falta correrlo a mano salvo verificación manual.

## Antes de dar algo por terminado

- [ ] `php artisan test` pasa (backend) — cobertura de línea aún no medible (falta PCOV/Xdebug, ver `errores-conocidos.md`).
- [ ] `pnpm test` pasa (frontend, Vitest).
- [ ] `pnpm lint` sin errores (ESLint + Angular).
- [ ] `vendor/bin/pint` / `pnpm prettier --write .` sin cambios pendientes.
- [ ] `planning.md` actualizado si hubo cambio de código — un hook `Stop` bloquea el fin de sesión si hay cambios de código sin reflejar ahí (excepto `CLAUDE.md`, `AGENTS.md`, `funcionalidades.md`, `NEGOCIO.md`). Usar `/update-plan`.
- [ ] Si se agregaron/modificaron tests, actualizar `pruebas.md` y `Lista_test.md`.
- [ ] Regenerar Swagger si cambiaron endpoints: `php artisan l5-swagger:generate`.
- [ ] **Nunca hacer commit** salvo pedido explícito del usuario ("commit", "hacer commit", "guarda los cambios en git").

## Deploy

- **Backend (`recaudify-api`):** push a `develop` → GitHub Actions (`deploy-staging.yml`) hace SSH al VPS, `git pull`, `composer install --no-dev`, `migrate --force`, cachea config/rutas/vistas. Push a `main` → mismo flujo contra producción (`deploy-prod.yml`). Ambos workflows solo se disparan si el push toca `recaudify-api/**`.
- **Frontend (`recaudify-web`):** Vercel detecta `vercel.json` y construye con `pnpm run build:$BUILD_CONFIG` (`prod` en `main`, `staging` en `develop`/preview) — no hay workflow de GitHub Actions para el frontend, el deploy es automático de Vercel por rama.
- **Infraestructura:** un solo VPS (Hostinger, Ubuntu 24.04) aloja staging y producción en paths separados (`/var/www/recaudify-staging`, `/var/www/recaudify-prod`), cada uno con su propia base MySQL (`recaudify_staging`, `recaudify_prod`). Detalle completo en `vps_deploy_guide.md`.
