# SJWT - Simple JWT Library for Laravel

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue.svg)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/laravel-11.x%20%7C%2012.x-red.svg)](https://laravel.com/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Una librería simple, eficiente y fácil de usar para generar y validar tokens JWT (JSON Web Tokens) en aplicaciones Laravel 11 y 12.

## Características

- ✅ **Compatible con Laravel 11 y 12**
- ✅ **Alto rendimiento** con caché de secretos y optimizaciones
- ✅ **Fácil de usar** con API simple e intuitiva
- ✅ **Seguro** con validación de firmas y expiración
- ✅ **Bien documentado** con PHPDoc completo
- ✅ **Pruebas unitarias** completas con PHPUnit
- ✅ **Middleware listo para usar** para autenticación
- ✅ **Independiente de modelos** - funciona sin depender de Eloquent

## Instalación

### Requisitos

- PHP >= 8.2
- Laravel >= 11.0 o >= 12.0
- Composer

### Instalación vía Composer

```bash
composer require wramirez83/sjwt
```

### Publicar configuración (Opcional)

```bash
php artisan vendor:publish --tag=sjwt-config
```

Esto creará el archivo `config/sjwt.php` donde puedes configurar:

- `secret`: Clave secreta para firmar tokens (por defecto usa `SECRET_JWT` del `.env`)
- `default_expiration`: Tiempo de expiración por defecto en minutos (60)
- `header_name`: Nombre del header HTTP (por defecto: `Authorization`)
- `token_index`: Índice del token en el header (por defecto: 1, para "Bearer {token}")

## Configuración

### Variables de Entorno

Agrega a tu archivo `.env`:

```env
SECRET_JWT=tu-clave-secreta-muy-segura-aqui
JWT_EXPIRATION=60
JWT_HEADER_NAME=Authorization
JWT_TOKEN_INDEX=1
```

### Generar una Clave Secreta

Puedes generar una clave secreta segura usando:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

O usar el script incluido:

```bash
php src/Tools/Generate.php
```

## Uso Básico

### Generar un Token JWT

```php
use Wramirez83\Sjwt\SJWT;

// Crear un token con datos del usuario
$payload = [
    'user_id' => 123,
    'email' => 'usuario@example.com',
    'name' => 'Juan Pérez',
];

// Token válido por 60 minutos (por defecto)
$token = SJWT::encode($payload);

// Token válido por 120 minutos
$token = SJWT::encode($payload, 120);
```

### Validar y Decodificar un Token

```php
use Wramirez83\Sjwt\SJWT;

// Decodificar un token
$result = SJWT::decode($token);

// Verificar si el token es válido
if ($result->valid) {
    $userId = $result->payload->user_id;
    $email = $result->payload->email;
    
    echo "Usuario autenticado: $email";
} else {
    if ($result->tokenExpired) {
        echo "Token expirado";
    } elseif (!$result->signatureValid) {
        echo "Firma inválida";
    }
}
```

### Leer Token desde Headers HTTP

```php
// El método decode() puede leer automáticamente desde el header Authorization
$result = SJWT::decode(); // Lee de 'Authorization' header por defecto

// O especificar un header personalizado
$result = SJWT::decode('.', 'X-Auth-Token', 0);
```

## Middleware de Autenticación

### Registrar el Middleware

En `app/Http/Kernel.php` o `bootstrap/app.php` (Laravel 11+):

```php
use Wramirez83\Sjwt\Tools\AuthJWTMiddleware;

// Laravel 11+
$middleware->alias([
    'auth.jwt' => AuthJWTMiddleware::class,
]);
```

### Usar en Rutas

```php
use Illuminate\Support\Facades\Route;
use Wramirez83\Sjwt\Tools\AuthJWTMiddleware;

// Proteger una ruta
Route::get('/api/user', function () {
    return UserAuth::user()->getAtt();
})->middleware(AuthJWTMiddleware::class);

// O con alias
Route::middleware(['auth.jwt'])->group(function () {
    Route::get('/api/profile', function () {
        return UserAuth::user()->getAtt();
    });
});
```

### Acceder a Datos del Usuario Autenticado

```php
use Wramirez83\Sjwt\UserAuth;

// Obtener todos los atributos
$userData = UserAuth::user()->getAtt();

// Obtener atributos específicos
$userId = UserAuth::user()->id();
$email = UserAuth::user()->email();
$name = UserAuth::user()->name();

// Obtener cualquier atributo
$role = UserAuth::user()->get('role', 'user');

// Verificar si existe un atributo
if (UserAuth::user()->has('permissions')) {
    // ...
}
```

## Ejemplos de Uso

### Login con JWT

```php
use Wramirez83\Sjwt\SJWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        
        $token = SJWT::encode([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ], 120); // Válido por 2 horas

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    return response()->json(['error' => 'Credenciales inválidas'], 401);
}
```

### Proteger API Endpoints

```php
Route::middleware([AuthJWTMiddleware::class])->group(function () {
    Route::get('/api/dashboard', function () {
        $user = UserAuth::user();
        return [
            'user_id' => $user->id(),
            'email' => $user->email(),
            'dashboard_data' => getDashboardData($user->id()),
        ];
    });
});
```

## Estructura del Proyecto

```
SJWT/
├── src/
│   ├── SJWT.php                    # Clase principal para encode/decode
│   ├── UserAuth.php                # Singleton para datos de usuario
│   ├── SjwtServiceProvider.php     # Service Provider de Laravel
│   └── Tools/
│       ├── AuthJWTMiddleware.php   # Middleware de autenticación
│       ├── StructJWT.php           # Constructor de estructura JWT
│       ├── UrlEncode.php           # Codificación Base64URL
│       └── Generate.php            # Generador de secretos
├── config/
│   └── sjwt.php                    # Archivo de configuración
├── tests/
│   ├── Unit/                        # Pruebas unitarias
│   └── Feature/                    # Pruebas de integración
└── composer.json
```

## Pruebas

Ejecutar todas las pruebas:

```bash
composer test
```

O con PHPUnit directamente:

```bash
./vendor/bin/phpunit
```

Ejecutar pruebas específicas:

```bash
./vendor/bin/phpunit tests/Unit/SJWTTest.php
```

## Mejoras de Rendimiento

Esta versión incluye varias optimizaciones:

1. **Caché de Secretos**: El secreto JWT se cachea para evitar búsquedas repetidas
2. **Validación Eficiente**: Uso de `hash_equals()` para comparación segura de firmas
3. **Manejo Optimizado de Headers**: Búsqueda eficiente de tokens en headers HTTP
4. **Eliminación de Duplicación**: Código consolidado sin clases duplicadas

## Seguridad

- ✅ Validación de firmas con `hash_equals()` (timing-safe)
- ✅ Verificación de expiración de tokens
- ✅ Validación de formato JWT
- ✅ Manejo seguro de errores sin exponer información sensible
- ✅ Soporte para claves secretas configurables

## Compatibilidad

- **Laravel**: 11.x, 12.x
- **PHP**: >= 8.2
- **Dependencias**:
  - `illuminate/support`: ^11.0|^12.0
  - `illuminate/http`: ^11.0|^12.0
  - `nesbot/carbon`: ^3.0

## Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

## Autor

**Wilson Ramirez Z**
- Email: wilson.rz@gmail.com

## Changelog

### v3.0.0
- ✨ Compatibilidad con Laravel 12
- ⚡ Mejoras significativas de rendimiento
- 🧪 Pruebas unitarias completas
- 📚 Documentación mejorada
- 🔧 Refactorización del código
- 🛡️ Mejoras de seguridad

### v2.1.0
- Versión anterior

## Soporte

Si encuentras algún problema o tienes preguntas, por favor abre un issue en el repositorio.