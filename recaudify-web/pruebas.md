# Pruebas unitarias — recaudify-web

## `src/app/core/utils/text.spec.ts`

Archivo fuente: `core/utils/text.ts`

| Prueba                                                          | Descripción                                                                                                |
| --------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| `lower > converts to lowercase`                                 | Verifica que convierte una cadena en mayúsculas a minúsculas                                               |
| `lower > trims whitespace`                                      | Verifica que elimina espacios al inicio y al final antes de convertir                                      |
| `lower > handles already lowercase`                             | Verifica que no altera una cadena que ya está en minúsculas                                                |
| `lower > handles mixed case`                                    | Verifica que convierte correctamente cadenas con mayúsculas y minúsculas mezcladas                         |
| `upper > converts to uppercase`                                 | Verifica que convierte una cadena en minúsculas a mayúsculas                                               |
| `upper > trims whitespace`                                      | Verifica que elimina espacios al inicio y al final antes de convertir                                      |
| `capitalize > capitalizes first letter and lowercases the rest` | Verifica que convierte la primera letra a mayúscula y el resto a minúsculas, sin importar el case original |
| `capitalize > trims whitespace`                                 | Verifica que elimina espacios al inicio y al final antes de capitalizar                                    |
| `titleCase > capitalizes each word`                             | Verifica que capitaliza la primera letra de cada palabra en una frase                                      |
| `titleCase > handles extra spaces`                              | Verifica que normaliza múltiples espacios entre palabras                                                   |
| `titleCase > lowercases uppercase input`                        | Verifica que convierte correctamente frases completamente en mayúsculas                                    |

---

## `src/app/core/interceptors/error.interceptor.spec.ts`

Archivo fuente: `core/interceptors/error.interceptor.ts`

| Prueba                                             | Descripción                                                                                                                                                            |
| -------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `maps server error body to ApiError`               | Verifica que cuando el servidor responde con un error (ej. 401), el interceptor transforma la respuesta en un objeto `ApiError` con `message` y `statusCode` correctos |
| `maps validation errors to ApiError.errors`        | Verifica que cuando el servidor responde con errores de validación (422), el interceptor mapea los errores de campos al objeto `ApiError.errors`                       |
| `falls back to generic message when body is empty` | Verifica que cuando el servidor responde sin body (ej. 500), el interceptor asigna un mensaje genérico en lugar de dejar `undefined`                                   |

---

## `src/app/core/guards/auth.guard.spec.ts`

Archivo fuente: `core/guards/auth.guard.ts`

| Prueba                                                            | Descripción                                                                                        |
| ----------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| `authGuard > allows access when authenticated`                    | Verifica que el guard permite el acceso a la ruta cuando el usuario tiene sesión activa            |
| `authGuard > redirects to /login when not authenticated`          | Verifica que el guard redirige a `/login` cuando el usuario no tiene sesión                        |
| `guestGuard > allows access when not authenticated`               | Verifica que el guard de invitado permite el acceso cuando el usuario no está autenticado          |
| `guestGuard > redirects to /dashboard when already authenticated` | Verifica que el guard de invitado redirige a `/dashboard` cuando el usuario ya tiene sesión activa |

---

## `src/app/core/services/auth.service.spec.ts`

Archivo fuente: `core/services/auth.service.ts`

| Prueba                                                               | Descripción                                                                                                                               |
| -------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| `initial state > is not authenticated when no token in localStorage` | Verifica que el servicio inicia sin autenticación cuando no hay token guardado                                                            |
| `initial state > is authenticated when token exists in localStorage` | Verifica que el servicio se inicializa como autenticado cuando ya existe un token en `localStorage`                                       |
| `login > stores token and sets isAuthenticated on success`           | Verifica que tras un login exitoso, el token se guarda en `localStorage` y la señal `isAuthenticated` cambia a `true`                     |
| `login > normalizes username to lowercase before sending`            | Verifica que el username se convierte a minúsculas antes de enviarse al API, sin importar cómo lo escribió el usuario                     |
| `register > stores token and sets isAuthenticated on success`        | Verifica que tras un registro exitoso, el token se guarda y el usuario queda autenticado                                                  |
| `register > sends null email when empty string`                      | Verifica que cuando el email está vacío, se envía `null` al API en lugar de una cadena vacía                                              |
| `logout > clears token and navigates to /login`                      | Verifica que al cerrar sesión se elimina el token de `localStorage`, la señal `isAuthenticated` cambia a `false` y se redirige a `/login` |
| `me > sets currentUser on success`                                   | Verifica que al obtener el perfil del usuario autenticado, la señal `currentUser` se actualiza con los datos recibidos                    |

---

## `src/app/app.spec.ts`

Archivo fuente: `app.ts`

| Prueba                              | Descripción                                                                               |
| ----------------------------------- | ----------------------------------------------------------------------------------------- |
| `App > should create`               | Verifica que el componente raíz de la aplicación se instancia correctamente               |
| `App > should render router-outlet` | Verifica que el componente raíz renderiza el `<router-outlet>` que gestiona la navegación |
