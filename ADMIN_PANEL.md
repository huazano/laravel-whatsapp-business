# Panel de Administración de Usuarios WhatsApp

## 📱 Características Implementadas

### 1. **Lista de Usuarios de WhatsApp**

**Ruta:** `/admin/whatsapp-users`

Funcionalidades:
- ✅ Visualización de todos los usuarios de WhatsApp
- ✅ Búsqueda por número de teléfono o nombre
- ✅ Paginación (20 usuarios por página)
- ✅ Información mostrada:
  - Avatar (foto de perfil o iniciales)
  - Nombre y número de teléfono
  - Roles asignados (guest, basic, premium, vip)
  - Estado activo/inactivo
  - Número de conversaciones
  - Última interacción
  - Estado de la última conversación

### 2. **Vista de Conversación Individual**

**Ruta:** `/admin/whatsapp-users/{id}`

Funcionalidades:
- ✅ Vista estilo WhatsApp Web
- ✅ Carga de últimos 20 mensajes al inicio
- ✅ **Scroll infinito hacia arriba** - carga automática de 20 mensajes anteriores
- ✅ Diferenciación visual de mensajes:
  - Mensajes entrantes (izquierda, fondo blanco)
  - Mensajes salientes (derecha, fondo azul)
- ✅ Estados de mensajes salientes:
  - ⏱️ Pendiente (spinner)
  - ✓ Enviado (check gris)
  - ✓✓ Entregado (doble check gris)
  - ✓✓ Leído (doble check azul)
- ✅ Envío de mensajes en tiempo real
- ✅ Información del usuario en el header
- ✅ Scroll automático al enviar mensajes

## 🎨 Componentes React

### Componentes Principales

1. **`/resources/js/pages/admin/whatsapp-users/index.tsx`**
   - Lista de usuarios con búsqueda
   - Utiliza shadcn/ui: Card, Input, Button, Badge, Avatar

2. **`/resources/js/pages/admin/whatsapp-users/show.tsx`**
   - Vista de conversación individual
   - Implementa scroll infinito
   - Envío de mensajes
   - Utiliza shadcn/ui: Card, Input, Button, Badge, Avatar, Skeleton

### Componentes de shadcn/ui Utilizados

- ✅ `Card` - Tarjetas de contenido
- ✅ `Input` - Campos de entrada
- ✅ `Button` - Botones
- ✅ `Badge` - Etiquetas de estado
- ✅ `Avatar` - Avatares de usuario
- ✅ `Skeleton` - Carga placeholder
- ✅ `ScrollArea` - Área de scroll personalizada

## 🔧 Backend

### Controladores

#### 1. **WhatsappUserController**

**Ruta:** `app/Http/Controllers/Admin/WhatsappUserController.php`

Métodos:
- `index()` - Lista de usuarios con búsqueda y paginación
- `show($id)` - Detalle de usuario individual
- `updateRole()` - Actualizar rol del usuario
- `toggleActive()` - Activar/desactivar usuario

#### 2. **ConversationController**

**Ruta:** `app/Http/Controllers/Admin/ConversationController.php`

Métodos:
- `messages($conversationId)` - Obtener mensajes con paginación
  - Parámetro: `before_id` para scroll infinito
  - Retorna: 20 mensajes anteriores al ID especificado
- `sendMessage($conversationId)` - Enviar mensaje al usuario
- `getOrCreate($userId)` - Obtener o crear conversación activa
- `close($conversationId)` - Cerrar conversación

### Rutas API

```php
// Lista de usuarios
GET /admin/whatsapp-users

// Ver usuario específico
GET /admin/whatsapp-users/{id}

// Obtener mensajes de conversación (con scroll infinito)
GET /admin/conversations/{id}/messages?before_id={id}

// Enviar mensaje
POST /admin/conversations/{id}/send
Body: { message: "texto del mensaje" }

// Cerrar conversación
PUT /admin/conversations/{id}/close
```

## 🔄 Cómo Funciona el Scroll Infinito

### Lógica de Carga

1. **Carga Inicial:**
   - Al abrir la conversación, se cargan los últimos 20 mensajes
   - Se guarda el `oldest_id` (ID del mensaje más antiguo)

2. **Scroll Hacia Arriba:**
   - Al detectar scroll en posición 0 (arriba del todo)
   - Se hace petición GET con `before_id={oldest_id}`
   - Se obtienen 20 mensajes anteriores
   - Se agregan al inicio del array de mensajes
   - Se ajusta la posición del scroll para mantener la vista

3. **Indicador de Carga:**
   - Muestra un spinner mientras carga más mensajes
   - `hasMore` indica si hay más mensajes disponibles

### Código Ejemplo

```typescript
const handleScroll = (e: React.UIEvent<HTMLDivElement>) => {
    const target = e.target as HTMLDivElement;

    // Detectar si está en el tope
    if (target.scrollTop === 0 && hasMore && !loadingMore && oldestId) {
        const previousHeight = target.scrollHeight;

        // Cargar mensajes anteriores
        loadMessages(oldestId).then(() => {
            // Mantener posición visual
            const newHeight = target.scrollHeight;
            target.scrollTop = newHeight - previousHeight;
        });
    }
};
```

## 📊 Datos de Prueba

Se incluye un seeder con 5 usuarios de ejemplo:

```bash
php artisan db:seed --class=WhatsappUsersTestSeeder
```

Usuarios creados:
- Juan Pérez (premium) - 15 mensajes
- María García (vip) - 25 mensajes
- Carlos López (basic) - 8 mensajes
- Ana Martínez (guest) - 3 mensajes
- Usuario sin nombre (guest) - 5 mensajes

## 🚀 Cómo Usar

### 1. Acceder al Panel

1. Inicia sesión en el sistema
2. En el menú lateral, haz clic en "WhatsApp Users"
3. Verás la lista de usuarios de WhatsApp

### 2. Buscar Usuarios

- Usa el campo de búsqueda para filtrar por número de teléfono o nombre
- Haz clic en "Search" para aplicar el filtro
- Haz clic en "Clear" para limpiar la búsqueda

### 3. Ver Conversación

1. Haz clic en cualquier tarjeta de usuario
2. Se abrirá la vista de conversación
3. Los últimos 20 mensajes se cargarán automáticamente
4. Haz scroll hacia arriba para cargar mensajes anteriores

### 4. Enviar Mensajes

1. Escribe tu mensaje en el campo de texto inferior
2. Presiona Enter o haz clic en el botón de enviar
3. El mensaje se enviará a través de WhatsApp API
4. Verás el estado del mensaje (enviado, entregado, leído)

## 🎨 Personalización

### Cambiar Colores de Roles

Edita el objeto `getRoleBadgeColor` en `index.tsx`:

```typescript
const getRoleBadgeColor = (role: string) => {
    const colors: Record<string, string> = {
        guest: 'bg-gray-100 text-gray-800',
        basic: 'bg-blue-100 text-blue-800',
        premium: 'bg-purple-100 text-purple-800',
        vip: 'bg-yellow-100 text-yellow-800',
    };
    return colors[role] || 'bg-gray-100 text-gray-800';
};
```

### Cambiar Cantidad de Mensajes por Carga

Edita la variable `$perPage` en `ConversationController.php`:

```php
public function messages(Conversation $conversation, Request $request): JsonResponse
{
    $perPage = 20; // Cambia este número
    // ...
}
```

## 🔐 Permisos

Para acceder al panel de administración, necesitas:
- Estar autenticado (middleware `auth`)
- Tener el email verificado (middleware `verified`)

Puedes agregar permisos adicionales:

```php
Route::middleware(['auth', 'verified', 'permission:admin.whatsapp_users.view'])
    ->group(function () {
        // Rutas protegidas
    });
```

## 📱 Responsive

El diseño es completamente responsive:
- **Desktop:** Vista completa con sidebar
- **Tablet:** Sidebar colapsable
- **Mobile:** Vista optimizada para pantallas pequeñas

## 🐛 Solución de Problemas

### Los mensajes no cargan

1. Verifica que existe una conversación activa
2. Revisa la consola del navegador
3. Verifica los logs de Laravel: `storage/logs/laravel.log`

### El scroll infinito no funciona

1. Asegúrate de que `hasMore` sea `true`
2. Verifica que `oldestId` tenga un valor
3. Revisa que no haya errores en la consola

### No puedo enviar mensajes

1. Verifica que el usuario esté activo
2. Revisa que tengas configuradas las credenciales de WhatsApp
3. Verifica los logs de Laravel

## 🔮 Próximas Mejoras

- [ ] Soporte para mensajes multimedia (imágenes, videos, documentos)
- [ ] Notificaciones en tiempo real (WebSockets)
- [ ] Búsqueda de mensajes dentro de la conversación
- [ ] Exportar conversaciones
- [ ] Estadísticas de usuarios
- [ ] Respuestas rápidas (templates)
- [ ] Asignación de conversaciones a agentes
- [ ] Estados de conversación (abierta, en progreso, cerrada)
