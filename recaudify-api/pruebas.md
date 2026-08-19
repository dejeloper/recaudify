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

---

## `tests/Unit/Support/MoneyTest.php`

Archivo fuente: `app/Support/Money.php`

| Prueba | Descripción |
| --- | --- |
| `test_rounds_up_to_thousand` | Verifica el redondeo al millar **hacia arriba** (regla del legacy `calcularSaldoMinimo`): 12.001 y 12.999 dan 13.000, y un múltiplo exacto no se altera |
| `test_rounds_to_nearest_thousand` | Verifica el redondeo al millar más cercano, usado solo en montos informativos |
| `test_from_input_accepts_formatted_strings` | Verifica que un monto con separadores de miles y símbolo (`$ 1.250.000`) se convierte a entero de pesos |
| `test_from_input_rejects_garbage` | Verifica que un texto no numérico lanza `InvalidArgumentException` en vez de truncar a 0 en silencio |
| `test_from_input_rejects_empty_string` | Verifica que una cadena vacía o de espacios lanza `InvalidArgumentException` |
| `test_split_never_loses_or_invents_pesos` | Verifica que repartir 100.000 en 3 cuotas da `[33334, 33333, 33333]`: la suma cuadra exactamente con el total |
| `test_split_with_exact_division` | Verifica el reparto cuando la división es exacta |
| `test_split_rejects_zero_parts` | Verifica que repartir en 0 partes lanza `InvalidArgumentException` |

---

## `tests/Feature/Activity/ActivityTest.php` (casos agregados)

Archivo fuente: `app/Http/Controllers/Api/ActivityController.php`, `app/Services/ActivityService.php`, `app/Models/Activity.php`

| Prueba | Descripción |
| --- | --- |
| `test_index_filters_by_date_range` | Verifica que `from`/`to` acotan el feed al rango pedido |
| `test_index_rejects_inverted_date_range` | Verifica que un rango con `to` anterior a `from` devuelve 422 |
| `test_causer_snapshot_survives_user_deletion` | Verifica que tras `forceDelete()` del autor, el registro conserva su nombre y username, y expone `causer.exists=false` |
| `test_causer_snapshot_survives_user_rename` | Verifica que renombrar al autor no reescribe la historia: el registro guarda el nombre que tenía al momento del hecho |
| `test_purge_requires_permission` | Verifica que sin `audit.purge` la purga devuelve 403, mientras la consulta con `audit.view` sigue en 200 |
| `test_purge_deletes_only_expired_activities` | Verifica que la purga elimina lo anterior al periodo de retención y respeta lo reciente |
| `test_purge_accepts_explicit_days` | Verifica que `days` en el body sobreescribe el parámetro `activity_log_retention_days` |
| `test_purge_is_itself_audited` | Verifica que la purga deja su propio registro con autor y cantidad de registros eliminados |
| `test_purge_preview_does_not_delete` | Verifica que la vista previa informa el conteo sin borrar nada |

