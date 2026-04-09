-- =============================================================================
-- RYZOMA AGRO — Schema Módulo Fertirrigación (Versión Showcase / Portfolio)
-- =============================================================================
-- Schema focalizado en el Módulo de Fertirrigación de Ryzoma Agro.
--
-- Tablas incluidas (12):
--   empresas, usuarios                          → Multi-tenancy y autenticación
--   predios, sectores, cultivos                 → Topología de la red agrícola
--   fertilizantes                               → Catálogo (Soporte JSON para micros)
--   config_distribucion_riego                   → Grafo de adyacencia hidráulica
--   fertilizaciones_cabezal                     → Registro de inyección (origen)
--   fertilizaciones_reales                      → Distribución calculada (destino)
--   programas_fertilizacion, programas_detalles → Planificación nutricional
--   reportes_tokens                             → Links efímeros para terceros
--
-- Tecnología: MySQL 8.0 | Motor: InnoDB | Charset: utf8mb4_unicode_ci
-- Autor: Cristian Antonio Manzano Ayala
-- =============================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `empresas`
--

DROP TABLE IF EXISTS `empresas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresas` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'PK: ID de la Empresa',
  `nombre` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `mes_inicio_temporada` int NOT NULL DEFAULT '7' COMMENT 'Mes (1-12) inicio temporada',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado_suscripcion` enum('activa','inactiva','prueba') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'prueba',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla maestra multi-tenant';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'PK: ID del Usuario',
  `empresa_id` int NOT NULL COMMENT 'FK a empresas.id',
  `trabajador_id` int DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol_id` int NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_usuario_empresa` (`empresa_id`),
  CONSTRAINT `fk_usuario_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cultivos`
--

DROP TABLE IF EXISTS `cultivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cultivos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `variedad` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_cultivo_empresa` (`empresa_id`),
  CONSTRAINT `fk_cultivo_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `predios`
--

DROP TABLE IF EXISTS `predios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `predios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cultivo_id` int DEFAULT NULL,
  `tipo_superficie` enum('cultivo','cabezal_virtual','infraestructura') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cultivo',
  `año_plantacion` int DEFAULT NULL,
  `plantas_por_hectarea` int DEFAULT NULL,
  `superficie_total` decimal(10,2) DEFAULT NULL,
  `cantidad_plantas` int DEFAULT NULL,
  `tipo_emisor` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `caudal_lt_hora` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `umbral_bajo` int DEFAULT '75',
  `umbral_optimo_min` int DEFAULT '90',
  `umbral_optimo_max` int DEFAULT '110',
  `umbral_exceso` int DEFAULT '130',
  PRIMARY KEY (`id`),
  KEY `fk_predio_empresa` (`empresa_id`),
  CONSTRAINT `fk_predio_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sectores`
--

DROP TABLE IF EXISTS `sectores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sectores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `predio_id` int NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `unidad` varchar(5) COLLATE utf8mb4_general_ci NOT NULL,
  `superficie` decimal(10,2) NOT NULL,
  `cantidad_plantas` int NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `entidad_legal_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sector_empresa` (`empresa_id`),
  KEY `fk_sector_predio` (`predio_id`),
  CONSTRAINT `fk_sector_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sector_predio` FOREIGN KEY (`predio_id`) REFERENCES `predios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fertilizantes`
--

DROP TABLE IF EXISTS `fertilizantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fertilizantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `nombre_comercial` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_producto` enum('fertilizante','enmienda','biostimulante','otro') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'fertilizante',
  `tipo_unidad` enum('kg','lt') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'kg',
  `densidad` decimal(5,3) DEFAULT '1.000',
  `porcentaje_n` decimal(5,2) DEFAULT '0.00',
  `porcentaje_p` decimal(5,2) DEFAULT '0.00',
  `porcentaje_k` decimal(5,2) DEFAULT '0.00',
  `micronutrientes` json DEFAULT NULL COMMENT 'Payload dinámico. Ej: {"Zn": 2.5, "Acidos_Humicos": 15.0}',
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `empresa_id` (`empresa_id`),
  CONSTRAINT `fertilizantes_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `config_distribucion_riego`
--

DROP TABLE IF EXISTS `config_distribucion_riego`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_distribucion_riego` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `predio_origen_id` int NOT NULL,
  `predio_destino_id` int NOT NULL,
  `porcentaje_flujo` decimal(5,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `empresa_id` (`empresa_id`),
  KEY `predio_origen_id` (`predio_origen_id`),
  KEY `predio_destino_id` (`predio_destino_id`),
  CONSTRAINT `config_distribucion_riego_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `config_distribucion_riego_ibfk_2` FOREIGN KEY (`predio_origen_id`) REFERENCES `predios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `config_distribucion_riego_ibfk_3` FOREIGN KEY (`predio_destino_id`) REFERENCES `predios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fertilizaciones_cabezal`
--

DROP TABLE IF EXISTS `fertilizaciones_cabezal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fertilizaciones_cabezal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `predio_cabezal_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `fertilizante_id` int NOT NULL,
  `fecha` date NOT NULL,
  `semana` int GENERATED ALWAYS AS (week(`fecha`,1)) STORED,
  `cantidad_aplicada` decimal(10,2) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `empresa_id` (`empresa_id`),
  KEY `predio_cabezal_id` (`predio_cabezal_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `fertilizante_id` (`fertilizante_id`),
  CONSTRAINT `fertilizaciones_cabezal_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fertilizaciones_cabezal_ibfk_2` FOREIGN KEY (`predio_cabezal_id`) REFERENCES `predios` (`id`),
  CONSTRAINT `fertilizaciones_cabezal_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fertilizaciones_cabezal_ibfk_4` FOREIGN KEY (`fertilizante_id`) REFERENCES `fertilizantes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fertilizaciones_reales`
--

DROP TABLE IF EXISTS `fertilizaciones_reales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fertilizaciones_reales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `fertilizacion_cabezal_id` int NOT NULL,
  `predio_destino_id` int NOT NULL,
  `cantidad_recibida` decimal(10,2) NOT NULL,
  `unidades_n` decimal(10,2) DEFAULT '0.00',
  `unidades_p` decimal(10,2) DEFAULT '0.00',
  `unidades_k` decimal(10,2) DEFAULT '0.00',
  `unidades_micronutrientes` json DEFAULT NULL COMMENT 'Calculado al registrar. Ej: {"Zn": 0.45}',
  PRIMARY KEY (`id`),
  KEY `fertilizacion_cabezal_id` (`fertilizacion_cabezal_id`),
  KEY `predio_destino_id` (`predio_destino_id`),
  CONSTRAINT `fertilizaciones_reales_ibfk_1` FOREIGN KEY (`fertilizacion_cabezal_id`) REFERENCES `fertilizaciones_cabezal` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fertilizaciones_reales_ibfk_2` FOREIGN KEY (`predio_destino_id`) REFERENCES `predios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `programas_fertilizacion`
--

DROP TABLE IF EXISTS `programas_fertilizacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `programas_fertilizacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `temporada` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `cultivo_id` int DEFAULT NULL,
  `predio_id` int DEFAULT NULL,
  `tipo` enum('base','ajuste') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'base',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_prog_empresa` (`empresa_id`),
  CONSTRAINT `fk_prog_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `programas_detalles`
--

DROP TABLE IF EXISTS `programas_detalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `programas_detalles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `programa_id` int NOT NULL,
  `mes` int NOT NULL,
  `meta_n` decimal(10,2) DEFAULT '0.00',
  `meta_p` decimal(10,2) DEFAULT '0.00',
  `meta_k` decimal(10,2) DEFAULT '0.00',
  `meta_extra_nombre` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta_extra_cantidad` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prog_mes` (`programa_id`,`mes`),
  CONSTRAINT `fk_detalle_programa` FOREIGN KEY (`programa_id`) REFERENCES `programas_fertilizacion` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reportes_tokens`
--

DROP TABLE IF EXISTS `reportes_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reportes_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `usuario_creador_id` int NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_reporte` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nutricional_temporada',
  `params` text COLLATE utf8mb4_unicode_ci,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` datetime DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_token` (`token`),
  KEY `fk_token_empresa` (`empresa_id`),
  CONSTRAINT `fk_token_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on [SANITIZED]