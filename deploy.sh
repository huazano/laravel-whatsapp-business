#!/bin/bash
set -e

echo "🚀 Iniciando deployment..."

# Detectar la versión de PHP disponible en Plesk
# Plesk generalmente usa estas rutas
if [ -f "/opt/plesk/php/8.4/bin/php" ]; then
    PHP_BIN="/opt/plesk/php/8.4/bin/php"
    echo "✓ Usando PHP 8.4"
elif [ -f "/opt/plesk/php/8.3/bin/php" ]; then
    PHP_BIN="/opt/plesk/php/8.3/bin/php"
    echo "✓ Usando PHP 8.3"
elif [ -f "/opt/plesk/php/8.2/bin/php" ]; then
    PHP_BIN="/opt/plesk/php/8.2/bin/php"
    echo "✓ Usando PHP 8.2"
elif [ -f "/opt/plesk/php/8.1/bin/php" ]; then
    PHP_BIN="/opt/plesk/php/8.1/bin/php"
    echo "✓ Usando PHP 8.1"
elif command -v php &> /dev/null; then
    PHP_BIN="php"
    echo "✓ Usando PHP del sistema"
else
    echo "❌ Error: PHP no encontrado en las rutas de Plesk"
    echo "Rutas verificadas:"
    echo "  - /opt/plesk/php/8.4/bin/php"
    echo "  - /opt/plesk/php/8.3/bin/php"
    echo "  - /opt/plesk/php/8.2/bin/php"
    echo "  - /opt/plesk/php/8.1/bin/php"
    exit 1
fi

# Instalar dependencias de Composer
echo "📦 Instalando dependencias de Composer..."
$PHP_BIN $(which composer) install --no-dev --optimize-autoloader --no-interaction

# Instalar dependencias de NPM
echo "📦 Instalando dependencias de NPM..."
npm ci --prefer-offline --no-audit

# Generar tipos de Wayfinder ANTES del build
echo "🔧 Generando tipos de Wayfinder..."
$PHP_BIN artisan wayfinder:generate --with-form

# Build de assets con Vite
echo "🏗️  Compilando assets..."
npm run build

# Optimizar Laravel
echo "⚡ Optimizando Laravel..."
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# Ejecutar migraciones
echo "🗄️  Ejecutando migraciones..."
$PHP_BIN artisan migrate --force

# Limpiar cachés
echo "🧹 Limpiando cachés..."
$PHP_BIN artisan cache:clear

echo "✅ Deployment completado exitosamente!"
