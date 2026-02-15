-- PostgreSQL Initialization Script
-- This script runs when the container is first created

-- Create indexes for performance optimization
-- (These will be created after Flask-Migrate creates the tables)

-- Enable extensions if needed
CREATE EXTENSION IF NOT EXISTS pg_trgm;

-- Set timezone to UTC
SET timezone = 'UTC';

-- Log initialization
DO $$
BEGIN
    RAISE NOTICE 'Database initialized with UTC timezone';
END $$;
