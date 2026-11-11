function slugify(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

function uniqueSlug(item, siblings) {
  const slug = slugify(item.name) || String(item.id)
  return siblings.filter((sibling) => slugify(sibling.name) === slug).length > 1
    ? `${slug}-${item.id}`
    : slug
}

export function catalogLocation(category, subcategory, page = 1, categories = []) {
  return {
    name: 'catalogo',
    params: {
      categoriaSlug: uniqueSlug(category, categories),
      subcategoriaSlug: subcategory ? uniqueSlug(subcategory, category.subcategories) : '',
    },
    query: page > 1 ? { page: String(page) } : {},
  }
}

export function resolveCatalogSelection(categories, route) {
  const friendly = route.name === 'catalogo'
  const category = categories.find((item) =>
    friendly
      ? uniqueSlug(item, categories) === route.params.categoriaSlug
      : String(item.id) === route.query.categoria,
  )
  const requestedSubcategory = friendly ? route.params.subcategoriaSlug : route.query.subcategoria
  const subcategory = requestedSubcategory
    ? category?.subcategories.find((item) =>
        friendly
          ? uniqueSlug(item, category.subcategories) === requestedSubcategory
          : String(item.id) === requestedSubcategory,
      )
    : undefined
  return { category, subcategory, hasSubcategory: Boolean(requestedSubcategory) }
}
