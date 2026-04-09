# 🌱 Ryzoma Agro — Módulo de Fertirrigación (Showcase)

> **Aviso:** Este repositorio es un *showcase* técnico extraído del sistema privado **Ryzoma Agro**. Contiene el schema de base de datos sanitizado y una descripción detallada de la arquitectura y lógica de negocio del módulo de fertirrigación, con el propósito de demostrar criterio de diseño, calidad de código y resolución de problemas técnicos complejos.

## 📝 Contexto del Proyecto

**Ryzoma Agro** es una plataforma SaaS *multi-tenant* diseñada para la gestión operativa y gerencial del sector agrícola. El sistema procesa y cruza datos críticos como fertirriego, condiciones climáticas (ej. evaporación de bandeja), registro y trazabilidad de cosechas, y georreferenciación de eventos en terreno (fugas, plagas, infraestructura).

Este *showcase* se enfoca en el **Módulo de Fertirrigación**, que resuelve el problema de inyectar fertilizantes en una red hidráulica real y calcular automáticamente la distribución de nutrientes (N, P, K) hacia cada sector de cultivo.

## 🏗 Stack y Arquitectura

El sistema implementa un patrón **MVC con Capa de Servicios**, manteniendo los controladores delgados y aislando la lógica de negocio compleja en servicios especializados.

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.x — Arquitectura MVC custom |
| Base de datos | MySQL 8.0 / InnoDB — Schema normalizado |
| Lógica de negocio | Capa de Servicios (`FertilizacionService.php`) |
| Frontend | HTML5, Bootstrap 5, JavaScript Vanilla |
| Seguridad | Multi-tenancy por `empresa_id`, consultas preparadas con `bindParam` |

## 🗄 Modelo de Datos

El archivo [`database/schema_sanitized.sql`](./database/schema_sanitized.sql) contiene el schema focalizado en este módulo (12 tablas). El modelo completo del sistema incluye ~35 tablas adicionales de otros módulos.

### Diagrama de relaciones clave

```
empresas (1) ──────────────────────────────────────────── (*) predios
    │                                                           │
    │                                                      (*) sectores
    │                                                           │
    └──── (*) fertilizantes                                     │
    │         (nombre, densidad, %N, %P, %K)                   │
    │                                                           │
    └──── (*) config_distribucion_riego ────────────────────────┘
    │         (predio_origen_id → predio_destino_id, porcentaje_flujo)
    │
    └──── (*) fertilizaciones_cabezal ──── (*) fertilizaciones_reales
              (qué se inyecta en origen)        (qué llega a cada predio)
```

### Tablas del módulo

| Tabla | Rol |
|---|---|
| `empresas` | Tabla raíz del multi-tenancy. Cada cliente es una empresa. |
| `usuarios` | Usuarios del sistema, aislados por `empresa_id`. |
| `predios` / `sectores` / `cultivos` | Topología de la red agrícola del cliente. |
| `fertilizantes` | Catálogo de productos con densidad y composición nutricional (N, P, K + extra). Permite trabajar en Kg o Lt con conversión automática. |
| `config_distribucion_riego` | **Grafo de adyacencia con pesos.** Cada fila define que el `predio_origen_id` distribuye un `porcentaje_flujo` hacia un `predio_destino_id`. Sustenta el algoritmo de *mass balance*. |
| `fertilizaciones_cabezal` | Registro de lo que se **inyecta** en el cabezal (origen). Contiene columna `semana` generada automáticamente por MySQL (`GENERATED ALWAYS AS`). |
| `fertilizaciones_reales` | Resultado del **cálculo de distribución**: qué cantidad y cuántas unidades puras (N, P, K) recibió efectivamente cada sector de destino. |
| `programas_fertilizacion` | Plan nutricional anual por cultivo o predio (lógica `base` + `ajuste`). |
| `programas_detalles` | Metas mensuales de N, P, K definidas en el programa. Permite comparar planificado vs. ejecutado. |
| `reportes_tokens` | Tokens con expiración para compartir reportes con terceros sin crear cuentas. |

## 🧠 Desafíos Técnicos Resueltos

### 1. Algoritmo de Distribución Hidráulica (Mass Balance)

**Problema:** El fertilizante se inyecta en un cabezal central, pero la red de cañerías lo distribuye a múltiples sectores con proporciones distintas y variables según la configuración de cada empresa.

**Solución:** El método `procesarDistribucion()` en `FertilizacionService`:

1. Lee el grafo de distribución desde `config_distribucion_riego` para el cabezal registrado.
2. Convierte la cantidad aplicada a **kilos netos de producto** considerando `tipo_unidad` (Kg/Lt) y `densidad` del fertilizante.
3. Itera sobre cada arco del grafo, aplica `porcentaje_flujo` y calcula el **remanente** que queda en el predio de origen.
4. Por cada destino, calcula las **unidades puras** de nutrientes: `unidades_n = cantidad_recibida * (porcentaje_n / 100)`.
5. Persiste los resultados en `fertilizaciones_reales` dentro de una transacción atómica.

```php
// Fragmento ilustrativo — FertilizacionService.php
public function procesarDistribucion(int $cabezalId, int $empresaId): void
{
    $distribuciones = $this->configModel->getDistribucion($cabezalId, $empresaId);
    DB::beginTransaction();
    foreach ($distribuciones as $dist) {
        $cantidadRecibida = $cabezal->cantidad_kg * ($dist->porcentaje_flujo / 100);
        $this->realModel->insertar([
            'predio_destino_id' => $dist->predio_destino_id,
            'cantidad_recibida' => $cantidadRecibida,
            'unidades_n'        => $cantidadRecibida * ($fertilizante->porcentaje_n / 100),
            'unidades_p'        => $cantidadRecibida * ($fertilizante->porcentaje_p / 100),
            'unidades_k'        => $cantidadRecibida * ($fertilizante->porcentaje_k / 100),
        ]);
    }
    DB::commit();
}
```

### 2. Multi-Tenancy Estricto a Nivel de Modelo

**Problema:** Garantizar que los datos de diferentes empresas agrícolas nunca se crucen, incluso ante errores de programación en capas superiores.

**Solución:** El `empresa_id` se inyecta en el constructor de cada modelo y se incluye obligatoriamente en **toda** consulta SQL mediante `bindParam`. No existe ningún método de lectura que no filtre por empresa.

```php
// FertilizanteModel.php
public function __construct(private int $empresaId) {}

public function getAll(): array
{
    $stmt = $this->db->prepare(
        'SELECT * FROM fertilizantes WHERE empresa_id = :empresa AND activo = 1'
    );
    $stmt->bindParam(':empresa', $this->empresaId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

### 3. Formularios Dinámicos con Prevención de Duplicados

**Problema:** Los operarios de terreno registran mezclas de múltiples fertilizantes en una sola pantalla desde dispositivos móviles, expuestos a doble envío por conectividad inestable.

**Solución:** El método `guardarRegistro()` en `FertilizacionController` procesa arrays dinámicos generados con `<template>` en JavaScript. Antes de cada inserción invoca `verificarDuplicado()` que comprueba la combinación `(empresa_id, predio_cabezal_id, fertilizante_id, fecha)`. Los duplicados se omiten silenciosamente y se reporta un resumen de éxitos/omisiones al usuario.

### 4. Reportes Efímeros para Asesores Externos

**Problema:** Gerencia necesita compartir métricas de fertilización (Unidades NPK/Hectárea) con asesores externos sin otorgarles acceso al sistema.

**Solución:** `reportes_tokens` almacena un hash criptográfico con fecha de expiración. `generarLinkPublico()` crea una URL de un solo uso que renderiza `reporte_nutricional.php` de forma completamente aislada y optimizada para impresión con CSS `@media print`.

## 📁 Contenido del Repositorio

```
ryzoma-agro-showcase/
├── README.md
├── app/
│   ├── controllers/
│   │   ├── FertilizacionController.php
│   │   └── FertilizanteController.php
│   ├── core/
│   │   └── FertilizacionService.php       # Lógica de negocio dura y algoritmos
│   ├── models/
│   │   └── FertilizanteModel.php          # Consultas preparadas y abstracción DB
│   └── views/
│       ├── fertilizacion/
│       │   ├── configuracion.php
│       │   ├── historial.php
│       │   ├── registro.php
│       │   └── reporte_nutricional.php
│       └── fertilizantes/
│           ├── form.php
│           └── index.php
└── database/
    └── schema_sanitized.sql               # Schema focalizado (12 tablas, sanitizado)
```

El código fuente del sistema (controladores, modelos, vistas, servicios) se mantiene en repositorio privado. Este showcase expone la arquitectura, el modelo de datos y la lógica de negocio de forma ilustrativa.

***

*Desarrollado por [Cristian Manzano Ayala](https://github.com/CristianM1337) — Sistema en producción activa.*

