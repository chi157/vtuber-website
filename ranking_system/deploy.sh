#!/bin/bash
# ============================================
# VTuber Viewer Ranking System - Deployment Script
# ============================================

set -e

echo "🚀 Starting deployment..."

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    echo "   Please copy .env.example to .env and configure it."
    exit 1
fi

# Stop existing containers
echo "📦 Stopping existing containers..."
docker-compose down

# Build and start containers
echo "🔨 Building and starting containers..."
docker-compose up -d --build

# Wait for PostgreSQL to be ready
echo "⏳ Waiting for PostgreSQL..."
sleep 10

# Run database migrations
echo "🗄️  Running database migrations..."
docker-compose exec -T flask-api flask db upgrade 2>/dev/null || {
    echo "📝 Initializing migrations..."
    docker-compose exec -T flask-api flask db init
    docker-compose exec -T flask-api flask db migrate -m "Initial migration"
    docker-compose exec -T flask-api flask db upgrade
}

# Check health
echo "🏥 Checking health..."
sleep 5
curl -s http://localhost/health && echo ""

echo "✅ Deployment complete!"
echo ""
echo "📊 Service status:"
docker-compose ps
