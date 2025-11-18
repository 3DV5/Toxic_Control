-- Adicionar campos de coordenadas na tabela propriedades
-- Execute este script no banco de dados para adicionar os campos latitude e longitude

ALTER TABLE propriedades 
ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) NULL COMMENT 'Latitude da propriedade para Google Maps',
ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) NULL COMMENT 'Longitude da propriedade para Google Maps';

