#!/bin/bash
set -e

echo "Iniciando deployment..."

# Detectar la version de PHP disponible en Plesk
if [ -f "/opt/plesk/php/8.4/bin/php" ]; then
    PHP_BIN="/opt/plesk/php/8.4/bin/php"
    echo "Usando PHP 8.4"
elif [ -f "/opt/plesk/php/8.3/bin/php" ]; then
    PHP_BIN="/opt/plesk/php/8.3/bin/php"
    echo "Usando PHP 8.3"
elif [ -f "/opt/plesk/php/8.2/bin/php" ]; then
    PHP_BIN="/opt/plesk/php/8.2/bin/php"
    echo "Usando PHP 8.2"
elif [ -f "/opt/plesk/php/8.1/bin/php" ]; then
    PHP_BIN="/opt/plesk/php/8.1/bin/php"
    echo "Usando PHP 8.1"
elif command -v php &gt; /dev/null 2>&gt;&1; then
    PHP_BIN="php"
    echo "Usando PHP del sistema"
else
    echo "Error: PHP no encontrado en las rutas de Plesk"
    exit 1
fi

# Instalar dependencias de Composer
echo "Instalando dependencias de Composer..."
$PHP_BIN $(which composer) install --no-dev --optimize-autoloader --no-interaction

# Instalar dependencias de NPM
echo "Instalando dependencias de NPM..."
npm ci --prefer-offline --no-audit

# Generar tipos de Wayfinder ANTES del build
echo "Generando tipos de Wayfinder..."
$PHP_BIN artisan wayfinder:generate --with-form

# Build de assets con Vite
echo "Compilando assets..."
npm run build

# Optimizar Laravel
echo "Optimizando Laravel..."
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# Ejecutar migraciones
echo "Ejecutando migraciones..."
$PHP_BIN artisan migrate --force

# Limpiar caches
echo "Limpiando caches..."
$PHP_BIN artisan cache:clear

echo "Deployment completado exitosamente!"
