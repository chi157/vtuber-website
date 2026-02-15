@echo off
REM ============================================
REM VTuber Viewer Ranking System - Deployment Script (Windows)
REM ============================================

echo 🚀 Starting deployment...

REM Check if .env exists
if not exist .env (
    echo ❌ Error: .env file not found!
    echo    Please copy .env.example to .env and configure it.
    exit /b 1
)

REM Stop existing containers
echo 📦 Stopping existing containers...
docker-compose down

REM Build and start containers
echo 🔨 Building and starting containers...
docker-compose up -d --build

REM Wait for PostgreSQL to be ready
echo ⏳ Waiting for PostgreSQL...
timeout /t 10 /nobreak > nul

REM Run database migrations
echo 🗄️  Running database migrations...
docker-compose exec -T flask-api flask db upgrade 2>nul || (
    echo 📝 Initializing migrations...
    docker-compose exec -T flask-api flask db init
    docker-compose exec -T flask-api flask db migrate -m "Initial migration"
    docker-compose exec -T flask-api flask db upgrade
)

REM Check health
echo 🏥 Checking health...
timeout /t 5 /nobreak > nul
curl -s http://localhost/health
echo.

echo ✅ Deployment complete!
echo.
echo 📊 Service status:
docker-compose ps
