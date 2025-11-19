# Configuración de WhatsApp Business Cloud API

## 📋 Variables de Entorno Requeridas

Debes configurar las siguientes variables en tu archivo `.env`:

```env
WHATSAPP_FROM_PHONE_NUMBER_ID=      # ID del número de teléfono de WhatsApp Business
WHATSAPP_ACCESS_TOKEN=              # Token de acceso permanente de la API
WHATSAPP_BUSINESS_ACCOUNT_ID=       # ID de la cuenta de negocio
WHATSAPP_WEBHOOK_VERIFY_TOKEN=      # Token secreto para verificar el webhook
WHATSAPP_APP_SECRET=                # App Secret (opcional, para validar firmas)
WHATSAPP_API_VERSION=v21.0          # Versión de la API
WHATSAPP_DEFAULT_USER_ROLE=guest    # Rol por defecto para nuevos usuarios
```

## 🔧 Cómo Obtener las Credenciales

### 1. Crear una App en Meta for Developers

1. Ve a [Meta for Developers](https://developers.facebook.com/)
2. Crea una nueva app de tipo "Business"
3. Agrega el producto "WhatsApp" a tu app

### 2. Configurar WhatsApp Business

1. En el dashboard de tu app, ve a **WhatsApp > API Setup**
2. Aquí encontrarás:
   - **Phone Number ID** (`WHATSAPP_FROM_PHONE_NUMBER_ID`)
   - **WhatsApp Business Account ID** (`WHATSAPP_BUSINESS_ACCOUNT_ID`)

### 3. Generar Access Token

1. En **WhatsApp > API Setup**, encontrarás un token temporal de 24 horas
2. Para producción, genera un **token permanente**:
   - Ve a **System Users** en Meta Business Suite
   - Crea un system user
   - Asigna permisos de WhatsApp
   - Genera un token (`WHATSAPP_ACCESS_TOKEN`)

### 4. Obtener App Secret

1. Ve a **Settings > Basic** en tu app de Meta
2. Copia el **App Secret** (`WHATSAPP_APP_SECRET`)

## 🌐 Configurar Webhook

### URL del Webhook

La URL de tu webhook será:
```
https://tu-dominio.com/api/whatsapp/webhook
```

### Pasos para Configurar en Meta

1. Ve a **WhatsApp > Configuration** en tu app
2. En la sección **Webhook**, haz clic en **Edit**
3. Ingresa tu URL del webhook
4. Ingresa el **Verify Token** (el mismo que configuraste en `WHATSAPP_WEBHOOK_VERIFY_TOKEN`)
5. Haz clic en **Verify and Save**

### Suscribirse a Eventos

Suscríbete a los siguientes campos del webhook:
- `messages` - Para recibir mensajes entrantes
- `message_status` - Para recibir actualizaciones de estado de mensajes

## 📊 Estructura de la Base de Datos

### Tablas Principales

1. **whatsapp_users** - Usuarios de WhatsApp
   - `phone_number` - Número de teléfono (único)
   - `name` - Nombre del contacto
   - `is_active` - Estado activo/inactivo
   - `last_interaction_at` - Última interacción

2. **conversations** - Conversaciones
   - `whatsapp_user_id` - Relación con el usuario
   - `status` - Estado (active, closed, archived)
   - `last_message_at` - Último mensaje

3. **messages** - Mensajes
   - `conversation_id` - Relación con la conversación
   - `whatsapp_message_id` - ID del mensaje en WhatsApp
   - `direction` - Dirección (inbound, outbound)
   - `type` - Tipo de mensaje (text, image, video, etc.)
   - `content` - Contenido del mensaje
   - `status` - Estado (sent, delivered, read, failed)

## 🔐 Roles y Permisos

### Roles para Usuarios de WhatsApp (guard: whatsapp)

- **guest** - Usuarios nuevos sin registrar
  - Acceso básico limitado

- **basic** - Usuarios registrados básicos
  - Acceso a funcionalidades básicas
  - Notificaciones

- **premium** - Usuarios premium
  - Todas las funcionalidades básicas
  - Comandos avanzados
  - Soporte prioritario

- **vip** - Usuarios VIP
  - Acceso completo a todas las funcionalidades

### Roles para Administradores (guard: web)

- **super-admin** - Administrador principal
- **admin** - Administrador regular
- **support** - Soporte (solo lectura)

## 🚀 Uso del Servicio de WhatsApp

### Enviar un Mensaje de Texto

```php
use App\Services\WhatsAppService;
use App\Models\WhatsappUser;

$whatsAppService = app(WhatsAppService::class);
$user = WhatsappUser::where('phone_number', '1234567890')->first();

$whatsAppService->sendTextMessage($user, 'Hola, este es un mensaje de prueba');
```

### Enviar un Mensaje de Plantilla

```php
$whatsAppService->sendTemplateMessage(
    $user,
    'hello_world',
    'es_MX',
    []
);
```

### Obtener o Crear Usuario Automáticamente

El sistema crea automáticamente usuarios cuando reciben mensajes:

```php
// Esto se hace automáticamente en el webhook
$user = $whatsAppService->getOrCreateUser('1234567890', 'Juan Pérez');
```

## 📱 Flujo de Mensajes

### Mensajes Entrantes (Inbound)

1. WhatsApp envía un webhook POST a `/api/whatsapp/webhook`
2. El sistema valida la firma (si está configurado `WHATSAPP_APP_SECRET`)
3. Se crea o actualiza el usuario basado en el número de teléfono
4. Si el usuario es nuevo, se le asigna el rol "guest"
5. Se crea o recupera una conversación activa
6. Se guarda el mensaje en la base de datos
7. El mensaje se marca como leído en WhatsApp
8. **Aquí puedes agregar tu lógica de chatbot para procesar y responder**

### Mensajes Salientes (Outbound)

1. Tu aplicación llama a `sendTextMessage()` o `sendTemplateMessage()`
2. El mensaje se envía a través de la API de WhatsApp
3. Se guarda el mensaje en la base de datos con estado "sent"
4. WhatsApp envía actualizaciones de estado (delivered, read)
5. El sistema actualiza el estado del mensaje en la base de datos

## 🧪 Pruebas

### Probar la Verificación del Webhook

```bash
curl "http://tu-dominio.com/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=my_secret_verify_token_12345&hub.challenge=CHALLENGE_ACCEPTED"
```

Deberías recibir: `CHALLENGE_ACCEPTED`

### Probar el Envío de Mensajes

```bash
php artisan tinker

$service = app(\App\Services\WhatsAppService::class);
$user = \App\Models\WhatsappUser::factory()->create([
    'phone_number' => 'TU_NUMERO_DE_PRUEBA'
]);
$service->sendTextMessage($user, 'Mensaje de prueba');
```

## 🔍 Logs y Debugging

Los logs de WhatsApp se guardan en:
- `storage/logs/laravel.log`

Busca por:
- `WhatsApp webhook received` - Webhooks entrantes
- `Error processing WhatsApp message` - Errores de procesamiento
- `Error sending WhatsApp` - Errores de envío

## ⚠️ Notas Importantes

1. **Primeras 1000 conversaciones son gratis** cada mes
2. Debes tener un **número de teléfono verificado** en WhatsApp Business
3. Solo puedes enviar mensajes de **plantilla** a usuarios que no han iniciado una conversación en las últimas 24 horas
4. Los mensajes de texto normales solo se pueden enviar **dentro de las 24 horas** posteriores al último mensaje del usuario
5. Configura un **webhook público con HTTPS** para producción
6. Para desarrollo local, usa **ngrok** o similar para exponer tu servidor

## 🔗 Enlaces Útiles

- [Documentación oficial de WhatsApp Business Cloud API](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [Biblioteca PHP utilizada: netflie/whatsapp-cloud-api](https://github.com/netflie/whatsapp-cloud-api)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
