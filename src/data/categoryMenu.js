import { publicAsset } from 'src/utils/publicAsset'

function slugify(value) {
  return String(value)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

function category(id, name, icon, position, subcategories = [], apiName = name) {
  const iconUrl = publicAsset(`iconos/${icon}`)
  return {
    id,
    id_categoria: id,
    id_depa: 1,
    slug: slugify(`${apiName}-${id}`),
    name,
    label: name,
    icon,
    iconUrl,
    svgIcon: iconUrl,
    position,
    subcategories: subcategories.map(([subId, subName], index) => ({
      id: subId,
      id_subcategoria: subId,
      id_categoria: id,
      slug: slugify(`${subName}-${subId}`),
      name: subName.trim(),
      label: subName.trim(),
      position: index + 1,
    })),
  }
}

// Respaldo del catálogo público: conserva los identificadores de la API.
export const menuFallbackCategories = [
  category(1, 'Le Mussa', 'ico-lemussa.webp', 1, [
    [1, "Gamas Le'Mussa"],
    [60, 'Preparacion y acabado'],
    [64, 'Construccion'],
    [65, "Geles Creativos Le'Mussa"],
    [62, 'Gel ojo de Gato'],
    [63, 'Gel Galaxy, Diamante, Granizo'],
    [67, 'Gel Painting y Gel Spider'],
    [3, "Polygel Le'Mussa"],
    [61, 'Sanitizante y Liquidos Profesionales'],
  ]),
  category(13, 'Reveur', 'ico-reveur.webp', 2, [
    [44, 'Esmaltes'],
    [45, 'Sparkling Glitter'],
    [46, 'Powder Gel'],
    [47, 'Sculpting Gel'],
    [48, 'Rubber Gel'],
    [49, 'Dream Color'],
    [52, 'Builder Gel'],
    [54, 'Preparadores'],
    [55, 'Jelly Glitter'],
    [56, 'Herramientas'],
    [92, 'Pinceles'],
  ]),
  category(2, 'Producto Para Uñas', 'ico-uñas.webp', 3, [
    [6, 'Decoración (Foil, Stiker y Piedreria)'],
    [69, 'Tips'],
    [7, 'Efectos en Polvo'],
    [70, 'Muestrarios y Articulos de practica'],
    [8, 'Lamparas para uñas'],
    [9, 'Limas'],
    [10, 'Pulidores'],
    [11, 'Puntas para pulidores'],
    [12, 'Uñas de Decoradas'],
    [13, 'Extractores de polvos'],
    [14, 'Utensilios y Herramientas'],
    [42, 'Pinceles y punteros'],
    [68, 'Iman para Ojo de Gato'],
  ]),
  category(3, 'Productos para Cejas', 'ico-cejas.webp', 4, [
    [15, 'Productos para cejas'],
    [16, 'Microblading'],
  ]),
  category(4, 'Productos para Pestañas', 'ico-nagaraku.webp', 5, [
    [17, 'Pestañas'],
    [18, 'Pegamentos'],
    [19, 'Removedores'],
    [20, 'Utensilios y Herramientas'],
    [21, 'Rizados permanentes'],
  ]),
  category(9, 'Rasuradoras', 'ico-rasuradora.webp', 6, [
    [31, 'Rasuradoras'],
    [32, 'Depiladores'],
  ]),
  category(16, 'Eléctricos', 'ico-electricos.webp', 7, [
    [75, 'Lamparas de escritorio'],
    [78, 'Lamparas de Pedestal'],
    [76, 'Fundidores'],
    [77, 'Esterilizadores'],
    [86, 'Aro de luz / Fotografia'],
    [87, 'Ventiladores'],
    [88, 'Proyectores'],
    [89, 'Iluminacion'],
    [79, 'Otros'],
  ]),
  category(5, 'Productos para el Cabello', 'ico-cabello.webp', 8, [
    [22, 'Cepillo Alaciador'],
    [23, 'Planchas'],
    [24, 'Rizadoras'],
    [25, 'Ferros'],
    [26, 'Secadores'],
  ]),
  category(
    7,
    'Cosmetiqueras',
    'ico-cosmetiquera.webp',
    9,
    [
      [29, 'Cosmetiqueras'],
      [72, 'Exhibicion'],
    ],
    'Cosmetiquertas',
  ),
  category(8, 'Corporales / Faciales', 'ico-masajeador.webp', 10, [
    [73, 'Ceras'],
    [74, 'Exfolentes'],
    [80, 'Corporal/Facial'],
  ]),
  category(17, 'Guantes de Nitrilo', 'ico-guantes.webp', 11, [
    [81, 'Mediano MED'],
    [82, 'Grande LG'],
    [83, 'Chico SM'],
    [84, 'Extra Chico XS'],
    [85, 'Extra Grande XL'],
  ]),
  category(6, 'Brochas', 'ico-brochas.webp', 12, [
    [27, 'Bledos'],
    [28, 'Relacionados'],
  ]),
  category(10, 'Le Mussa Home', 'ico-hogar.webp', 13, [[33, 'Productos para el hogar']]),
  category(11, 'Varios', 'ico-otros.webp', 14, [
    [34, 'Organizacion'],
    [43, 'Productos Escolares'],
    [38, 'Para el Cabello'],
    [91, 'Pulseras'],
    [40, 'Paraguas'],
    [39, 'Zapatillas'],
    [90, 'Dental'],
    [35, 'Brasier'],
    [37, 'Espejos'],
    [36, 'Decoracion'],
  ]),
]

// Agrupaciones visuales del menú; null abre la categoría completa.
export const nailMenuTiles = [
  {
    key: 'geles',
    label: 'Geles',
    description: 'Constructor / Color',
    spriteIndex: 0,
    categoryId: 1,
    subcategoryId: 64,
  },
  {
    key: 'esmaltes',
    label: 'Esmaltes',
    description: 'Tradicional / Semipermanente',
    spriteIndex: 1,
    categoryId: 1,
    subcategoryId: 1,
  },
  {
    key: 'acrilicos',
    label: 'Acrílicos',
    description: 'Polvos / Líquidos',
    spriteIndex: 2,
    categoryId: 2,
    subcategoryId: null,
  },
  {
    key: 'preparadores',
    label: 'Preparadores',
    description: 'Primer / Deshidratador',
    spriteIndex: 3,
    categoryId: 1,
    subcategoryId: 60,
  },
  {
    key: 'herramientas',
    label: 'Herramientas',
    description: 'Limas / Empujadores',
    spriteIndex: 4,
    categoryId: 2,
    subcategoryId: 14,
  },
  {
    key: 'decoracion',
    label: 'Decoración',
    description: 'Glitter / Piedras / Stickers',
    spriteIndex: 5,
    categoryId: 2,
    subcategoryId: 6,
  },
  {
    key: 'lamparas',
    label: 'Lámparas',
    description: 'UV / LED',
    spriteIndex: 6,
    categoryId: 2,
    subcategoryId: 8,
  },
  {
    key: 'accesorios',
    label: 'Accesorios',
    description: 'Tips / Moldes / Cajas',
    spriteIndex: 7,
    categoryId: 2,
    subcategoryId: 69,
  },
  {
    key: 'limpieza',
    label: 'Limpieza',
    description: 'Removedores / Cleaner',
    spriteIndex: 8,
    categoryId: 1,
    subcategoryId: 61,
  },
  {
    key: 'kits',
    label: 'Kits Le Mussa',
    description: 'Profesionales',
    spriteIndex: 9,
    categoryId: 1,
    subcategoryId: null,
  },
]

// Productos de muestra visual, como mockStore; no son IDs ni precios del catálogo activo.
export const menuFeaturedProducts = [
  {
    id_producto: 'preview-gel',
    nombre: 'Gel Constructor Le Mussa 15g',
    codigo: 'MUESTRA-GEL',
    imagen: '',
    spriteIndex: 0,
    tiene_descuento: true,
    porcentaje_descuento: 20,
    precio_normal: 199,
    precio_final: 159.2,
    ahorro: 39.8,
  },
  {
    id_producto: 'preview-kit',
    nombre: 'Kit Esmaltes Le Mussa (6 pzas)',
    codigo: 'MUESTRA-KIT',
    imagen: '',
    spriteIndex: 9,
    tiene_descuento: true,
    porcentaje_descuento: 15,
    precio_normal: 499,
    precio_final: 424.15,
    ahorro: 74.85,
  },
  {
    id_producto: 'preview-lamp',
    nombre: 'Lámpara LED/UV Le Mussa 48W',
    codigo: 'MUESTRA-LAMPARA',
    imagen: '',
    spriteIndex: 6,
    tiene_descuento: true,
    porcentaje_descuento: 18,
    precio_normal: 799,
    precio_final: 655.18,
    ahorro: 143.82,
  },
  {
    id_producto: 'preview-organizer',
    nombre: 'Caja Organizadora para Tips',
    codigo: 'MUESTRA-ORGANIZADOR',
    imagen: '',
    spriteIndex: 7,
    tiene_descuento: true,
    porcentaje_descuento: 25,
    precio_normal: 129,
    precio_final: 96.75,
    ahorro: 32.25,
  },
]
