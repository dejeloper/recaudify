# Pruebas unitarias — recaudify-api

## `tests/Unit/ApiResultTest.php`

Archivo fuente: `app/Http/Responses/ApiResult.php`

| Prueba                                            | Descripción                                                                                                                                                |
| ------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `test_success_has_correct_properties`             | Verifica que el factory `success()` produce un objeto con `success=true`, código 200 y los datos y mensaje indicados                                       |
| `test_created_has_201_status`                     | Verifica que el factory `created()` produce un objeto con `success=true` y código 201                                                                      |
| `test_failure_has_correct_properties`             | Verifica que el factory `failure()` produce un objeto con `success=false`, el código de error indicado y `data=null`                                       |
| `test_unauthorized_has_401_status`                | Verifica que el factory `unauthorized()` produce un objeto con `success=false` y código 401                                                                |
| `test_not_found_has_404_status`                   | Verifica que el factory `notFound()` produce un objeto con `success=false` y código 404                                                                    |
| `test_forbidden_has_403_status`                   | Verifica que el factory `forbidden()` produce un objeto con `success=false` y código 403                                                                   |
| `test_empty_has_null_data`                        | Verifica que el factory `empty()` produce un objeto con `success=true` y `data=null`                                                                       |
| `test_to_response_returns_correct_json_structure` | Verifica que `toResponse()` devuelve una `JsonResponse` con código HTTP 200 y el body con las claves `success`, `message`, `statusCode` y `data` correctas |

---

## `tests/Feature/Auth/RegisterTest.php`

Archivo fuente: `app/Http/Controllers/Api/AuthController.php` · `app/Http/Requests/Auth/RegisterRequest.php`

| Prueba                                            | Descripción                                                                                                                                       |
| ------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| `test_registers_user_with_valid_data`             | Verifica que `POST /api/auth/register` con datos válidos devuelve 201 y el username del usuario creado en el body                                 |
| `test_normalizes_username_to_lowercase`           | Verifica que el username enviado en mayúsculas se convierte a minúsculas antes de guardarse, gracias a `prepareForValidation()` en el FormRequest |
| `test_fails_when_username_is_already_taken`       | Verifica que intentar registrar un segundo usuario con el mismo username devuelve 422                                                             |
| `test_fails_when_password_not_confirmed`          | Verifica que la validación `confirmed` rechaza el registro cuando `password_confirmation` no coincide con `password`                              |
| `test_fails_when_username_has_invalid_characters` | Verifica que la validación `regex` del username rechaza cadenas con espacios u otros caracteres no permitidos                                     |
| `test_accepts_registration_without_email`         | Verifica que el campo email es opcional y el registro se completa correctamente sin proporcionarlo                                                |

---

## `tests/Feature/Auth/LoginTest.php`

Archivo fuente: `app/Http/Controllers/Api/AuthController.php` · `app/Http/Requests/Auth/LoginRequest.php`

| Prueba                                        | Descripción                                                                                                                            |
| --------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| `test_login_returns_token_on_success`         | Verifica que `POST /api/auth/login` con credenciales correctas devuelve 200 y un body con `token`, `token_type`, `expires_in` y `user` |
| `test_login_fails_with_wrong_password`        | Verifica que el login con contraseña incorrecta devuelve 401 con `success=false`                                                       |
| `test_login_fails_for_inactive_user`          | Verifica que el login de un usuario con `active=false` devuelve 403, incluso si la contraseña es correcta                              |
| `test_login_normalizes_username_to_lowercase` | Verifica que enviar el username en mayúsculas funciona igual que en minúsculas, gracias a `prepareForValidation()` en el FormRequest   |
| `test_me_returns_authenticated_user_data`     | Verifica que `GET /api/auth/me` devuelve 200 y el username del usuario autenticado en el body                                          |
| `test_me_requires_authentication`             | Verifica que `GET /api/auth/me` sin autenticación devuelve 401                                                                         |
| `test_logout_closes_session_successfully`     | Verifica que `POST /api/auth/logout` de un usuario autenticado devuelve 200 con `success=true`                                         |
