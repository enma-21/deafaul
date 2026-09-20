<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Extrae marca/modelo/año de vehículo a partir del título libre de una
 * publicación de Mercado Libre. Es heurístico a propósito: la API no expone
 * la compatibilidad de vehículo como un atributo estructurado (BRAND y
 * PART_NUMBER se refieren al fabricante del repuesto, no al vehículo), así
 * que el dato solo existe como texto libre dentro del título. El mismo
 * enfoque de reconocimiento por texto ya se usó y se validó manualmente
 * durante la auditoría de categorización de esta cuenta.
 */
final class M2Base_Catalogo_ML_Parser {

    /** @var array<string, string[]> Marca => alias de texto a buscar cuando no hay modelo reconocido */
    const VEHICLE_BRANDS = [
        'Toyota' => ['toyota'],
        'Chevrolet' => ['chevrolet', 'chevy'],
        'Ford' => ['ford'],
        'Dodge' => ['dodge', 'ram'],
        'Jeep' => ['jeep'],
        'Mitsubishi' => ['mitsubishi'],
        'Hyundai' => ['hyundai'],
        'Nissan' => ['nissan', 'datsun'],
        'Kia' => ['kia'],
        'Isuzu' => ['isuzu'],
        'Volkswagen' => ['volkswagen'],
        'Fiat' => ['fiat'],
        'Renault' => ['renault'],
        'Suzuki' => ['suzuki'],
        'Mazda' => ['mazda'],
        'Mack' => ['mack'],
        'Hino' => ['hino'],
        'International' => ['international'],
        'Freightliner' => ['freightliner'],
        'Kenworth' => ['kenworth'],
        'Encava' => ['encava'],
    ];

    /**
     * @var array<string, string[]> Marca => modelos conocidos.
     * Se excluyen a propósito modelos identificados solo por números cortos
     * (ej. "300", "626") porque generan demasiados falsos positivos contra
     * números de parte y medidas — la misma lección aprendida durante la
     * auditoría de categorización (ahí "Triton" resultó ser un motor, no una
     * señal de camión pesado, y "Wagoneer" resultó liviano, no pesado).
     */
    const VEHICLE_MODELS = [
        'Toyota' => ['Corolla', 'Camry', 'Yaris', 'Hilux', '4Runner', 'FJ Cruiser', 'Land Cruiser', 'Fortuner', 'Tacoma', 'Tundra', 'Rav4', 'Prado', 'Machito', 'Autana', 'Meru', 'Starlet', 'Dyna'],
        'Chevrolet' => ['Aveo', 'Optra', 'Spark', 'Matiz', 'Corsa', 'Blazer', 'Trailblazer', 'Silverado', 'Tahoe', 'Suburban', 'Malibu', 'Cavalier', 'Captiva'],
        'Ford' => ['Fiesta', 'Focus', 'Explorer', 'Escape', 'Ranger', 'F-150', 'F-100', 'F-250', 'F-350', 'F-450', 'Bronco', 'Expedition', 'EcoSport', 'Super Duty'],
        'Dodge' => ['Dakota', 'Durango', 'Journey', 'Caravan', 'Aspen', 'Coronet', 'Dart'],
        'Jeep' => ['Wrangler', 'Cherokee', 'Grand Cherokee', 'Wagoneer', 'Patriot', 'Liberty'],
        'Mitsubishi' => ['Lancer', 'Montero', 'Outlander', 'L200', 'Eclipse', 'Canter', 'Fuso'],
        'Hyundai' => ['Accent', 'Elantra', 'Tucson', 'Santa Fe', 'Getz', 'Sonata', 'H100', 'Porter', 'Galloper'],
        'Nissan' => ['Sentra', 'Almera', 'Frontier', 'Xterra', 'Pathfinder', 'Terrano', 'Patrol', 'Urvan', 'Versa', 'Tsuru'],
        'Kia' => ['Rio', 'Sportage', 'Picanto', 'Sorento', 'Cerato'],
        'Isuzu' => ['NPR', 'NHR', 'NKR', 'NQR', 'Trooper', 'Rodeo', 'FTR', 'FVR', 'FSR', 'FRR'],
        'Volkswagen' => ['Gol', 'Golf', 'Jetta', 'Saveiro', 'Amarok', 'Caddy'],
        'Fiat' => ['Uno', 'Palio', 'Doblo'],
        'Renault' => ['Logan', 'Sandero', 'Duster', 'Clio'],
        'Suzuki' => ['Swift', 'Vitara', 'Grand Vitara'],
        'Mazda' => ['BT-50'],
        'Freightliner' => ['Cascadia', 'Columbia'],
    ];

    /** @var array<string, string>|null Modelo => marca, más largo primero */
    private static ?array $modelIndex = null;

    private static function build_model_index(): array {
        if (self::$modelIndex !== null) {
            return self::$modelIndex;
        }

        $index = [];
        foreach (self::VEHICLE_MODELS as $brand => $models) {
            foreach ($models as $model) {
                $index[$model] = $brand;
            }
        }
        uksort($index, static function (string $a, string $b): int {
            return strlen($b) <=> strlen($a);
        });

        self::$modelIndex = $index;
        return $index;
    }

    /**
     * @return array{vehicle_brand:string,vehicle_model:string,vehicle_year_from:?int,vehicle_year_to:?int,vehicle_match_conf:string}
     */
    public static function parse_titulo(string $title): array {
        $result = [
            'vehicle_brand' => '',
            'vehicle_model' => '',
            'vehicle_year_from' => null,
            'vehicle_year_to' => null,
            'vehicle_match_conf' => '',
        ];

        $normalized = remove_accents($title);
        if (trim($normalized) === '') {
            return $result;
        }

        $match_offset = null;
        $match_length = 0;

        // El caso dominante en los títulos reales es el modelo sin la marca
        // (ej. "Pastillas Freno Corolla 04-08"), así que se busca modelo primero.
        foreach (self::build_model_index() as $model => $brand) {
            if (preg_match('/\b' . preg_quote($model, '/') . '\b/i', $normalized, $matches, PREG_OFFSET_CAPTURE)) {
                $result['vehicle_brand'] = $brand;
                $result['vehicle_model'] = $model;
                $match_offset = $matches[0][1];
                $match_length = strlen($matches[0][0]);
                break;
            }
        }

        if ($match_offset === null) {
            foreach (self::VEHICLE_BRANDS as $brand => $aliases) {
                foreach ($aliases as $alias) {
                    if (preg_match('/\b' . preg_quote($alias, '/') . '\b/i', $normalized, $matches, PREG_OFFSET_CAPTURE)) {
                        $result['vehicle_brand'] = $brand;
                        $match_offset = $matches[0][1];
                        $match_length = strlen($matches[0][0]);
                        break 2;
                    }
                }
            }
        }

        if ($match_offset === null) {
            return $result;
        }

        // El año se busca solo en una ventana acotada después del match para
        // evitar falsos positivos con números de parte o medidas de rodamiento.
        $window = substr($normalized, $match_offset, $match_length + 25);
        [$year_from, $year_to] = self::extract_year_range($window);
        $result['vehicle_year_from'] = $year_from;
        $result['vehicle_year_to'] = $year_to;

        if ($result['vehicle_model'] !== '' && $year_from !== null) {
            $result['vehicle_match_conf'] = 'alta';
        } elseif ($result['vehicle_model'] !== '') {
            $result['vehicle_match_conf'] = 'media';
        } else {
            $result['vehicle_match_conf'] = 'baja';
        }

        return $result;
    }

    /** @return array{0:?int,1:?int} */
    private static function extract_year_range(string $window): array {
        if (preg_match('/\b(\d{2,4})\s*[\/-]\s*(\d{2,4})\b/', $window, $m)) {
            return [self::normalize_year($m[1]), self::normalize_year($m[2])];
        }
        // Años completos separados solo por espacio, ej. "Hilux 2015 2019".
        if (preg_match('/\b((?:19|20)\d{2})\s+((?:19|20)\d{2})\b/', $window, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        if (preg_match('/\b(19|20)\d{2}\b/', $window, $m)) {
            $year = (int) $m[0];
            return [$year, $year];
        }
        return [null, null];
    }

    private static function normalize_year(string $raw): int {
        $num = (int) $raw;
        if ($num >= 1900) {
            return $num;
        }
        $threshold = ((int) gmdate('y')) + 1;
        return $num <= $threshold ? 2000 + $num : 1900 + $num;
    }
}
