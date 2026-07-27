type CategoryCountNode = {
  count?: number | null;
  children?: { nodes?: CategoryCountNode[] | null } | null;
};

export function hasPublishedProductsInCategory(node: CategoryCountNode): boolean {
  if (typeof node.count === "number" && node.count > 0) return true;
  return (node.children?.nodes ?? []).some(hasPublishedProductsInCategory);
}
