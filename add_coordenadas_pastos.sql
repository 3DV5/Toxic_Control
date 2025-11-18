-- Adicionar campos de coordenadas na tabela pastos
-- Execute este script no banco de dados para adicionar os campos latitude e longitude

ALTER TABLE pastos 
ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) NULL COMMENT 'Latitude do pasto/área para Google Maps',
ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) NULL COMMENT 'Longitude do pasto/área para Google Maps';

