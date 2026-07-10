// Branch-aware фильтры каталога.
//
// Согласовано в docs/hws-catalog-phase-1-taxonomy.md: у каждой верхнеуровневой
// ветки свой набор фильтров, а не один глобальный. Здесь — порядок таксономий
// (pa_*) на каждую ветку. Бренд/серия/цена рендерятся отдельно в компоненте.
//
// Ключ карты — slug верхнего раздела (ветки). Для подкатегории ветка берётся
// от её родителя (см. app/catalog/[category]/page.tsx).

export const BRANCH_FILTERS: Record<string, string[]> = {
  "russian-bath-stoves": [
    "pa_fuel-type",
    "pa_equipment-type",
    "pa_steam-room-volume",
    "pa_power",
    "pa_cladding-material",
    "pa_series",
  ],
  "sauna-stoves": [
    "pa_fuel-type",
    "pa_usage-class",
    "pa_steam-room-volume",
    "pa_power",
    "pa_voltage",
    "pa_series",
  ],
  "steam-generators-and-hammam": [
    "pa_equipment-type",
    "pa_usage-class",
    "pa_power",
    "pa_voltage",
    "pa_steam-room-volume",
    "pa_series",
  ],
};

// Фолбэк для инженерных/прочих веток: минимальный набор.
export const DEFAULT_BRANCH_FILTERS: string[] = ["pa_equipment-type", "pa_series"];

// Плейсхолдеры селектов (подпись фильтра). Значения опций берутся из имён
// терминов таксономии (см. GET_ATTRIBUTE_TERMS).
export const ATTRIBUTE_LABELS: Record<string, string> = {
  "pa_fuel-type": "Тип топлива",
  "pa_equipment-type": "Тип оборудования",
  "pa_steam-room-volume": "Объём парной",
  "pa_power": "Мощность",
  "pa_cladding-material": "Облицовка",
  "pa_voltage": "Напряжение",
  "pa_usage-class": "Применение",
  "pa_room-type": "Тип помещения",
  "pa_series": "Серия",
};

// pa_power -> "power" для короткого query-параметра в URL.
export function attributeParamKey(taxonomy: string): string {
  return taxonomy.replace(/^pa_/, "");
}

export function filtersForBranch(branchSlug: string): string[] {
  return BRANCH_FILTERS[branchSlug] ?? DEFAULT_BRANCH_FILTERS;
}
