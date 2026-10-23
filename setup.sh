#!/bin/bash

echo "🚀 Starting Laravel Project Setup..."

# Copy .env jika belum ada
if [ ! -f .env ]; then
    echo "📄 Creating .env file..."
    cp .env.example .env
else
    echo "⚠️ .env already exists, skipping..."
fi

# Install composer dependency
echo "📦 Installing Composer dependencies..."
composer install

# Generate APP_KEY
echo "🔑 Generating app key..."
php artisan key:generate

# Clear cache
echo "🧹 Clearing cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migration
echo "🗄️ Running database migration..."
php artisan migrate

# Run seeder
echo "🌱 Seeding database..."
php artisan db:seed

# Install npm dependency
# echo "📦 Installing NPM dependencies..."
# npm install

# Build assets
# echo "🎨 Building assets..."
# npm run build

echo "✅ Laravel project setup completed!"