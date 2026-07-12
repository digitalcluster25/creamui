import { gql } from "@apollo/client";

export const GET_PRODUCTS = gql`
  query GetProducts($first: Int = 12, $after: String, $category: String) {
    products(
      first: $first
      after: $after
      where: { category: $category, status: "publish" }
    ) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        databaseId
        name
        slug
        ... on SimpleProduct {
          hwsPriceOnRequest
          price
          regularPrice
          salePrice
          image {
            sourceUrl
            altText
            hwsOptimizedUrl
          }
          productCategories {
            nodes {
              name
              slug
              parent {
                node {
                  name
                  slug
                }
              }
            }
          }
          productBrands {
            nodes {
              name
              slug
            }
          }
        }
        ... on VariableProduct {
          hwsPriceOnRequest
          price
          regularPrice
          salePrice
          image {
            sourceUrl
            altText
            hwsOptimizedUrl
          }
          productCategories {
            nodes {
              name
              slug
              parent {
                node {
                  name
                  slug
                }
              }
            }
          }
          productBrands {
            nodes {
              name
              slug
            }
          }
        }
      }
    }
  }
`;

export const GET_PRODUCTS_BY_BRAND = gql`
  query GetProductsByBrand($first: Int = 100, $after: String, $brand: String!) {
    products(
      first: $first
      after: $after
      where: {
        status: "publish"
        taxonomyFilter: {
          filters: [{ taxonomy: PRODUCT_BRAND, terms: [$brand], operator: IN }]
        }
      }
    ) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        databaseId
        name
        slug
        ... on SimpleProduct {
          hwsPriceOnRequest
          price
          regularPrice
          salePrice
          image {
            sourceUrl
            altText
            hwsOptimizedUrl
          }
          productCategories {
            nodes {
              name
              slug
              parent {
                node {
                  name
                  slug
                }
              }
            }
          }
          productBrands {
            nodes {
              name
              slug
            }
          }
        }
        ... on VariableProduct {
          hwsPriceOnRequest
          price
          regularPrice
          salePrice
          image {
            sourceUrl
            altText
            hwsOptimizedUrl
          }
          productCategories {
            nodes {
              name
              slug
              parent {
                node {
                  name
                  slug
                }
              }
            }
          }
          productBrands {
            nodes {
              name
              slug
            }
          }
        }
      }
    }
  }
`;

export const GET_PRODUCTS_BY_CATEGORY_FILTER = gql`
  query GetProductsByCategoryFilter($first: Int = 100, $after: String, $category: String!) {
    products(
      first: $first
      after: $after
      where: {
        status: "publish"
        taxonomyFilter: {
          filters: [{ taxonomy: PRODUCT_CAT, terms: [$category], operator: IN }]
        }
      }
    ) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        databaseId
        name
        slug
        ... on SimpleProduct {
          hwsPriceOnRequest
          price
          regularPrice
          salePrice
          image {
            sourceUrl
            altText
            hwsOptimizedUrl
          }
          productCategories {
            nodes {
              name
              slug
              parent {
                node {
                  name
                  slug
                }
              }
            }
          }
          productBrands {
            nodes {
              name
              slug
            }
          }
        }
        ... on VariableProduct {
          hwsPriceOnRequest
          price
          regularPrice
          salePrice
          image {
            sourceUrl
            altText
            hwsOptimizedUrl
          }
          productCategories {
            nodes {
              name
              slug
              parent {
                node {
                  name
                  slug
                }
              }
            }
          }
          productBrands {
            nodes {
              name
              slug
            }
          }
        }
      }
    }
  }
`;

// Термины атрибутов (pa_*) для карт slug -> человекочитаемое имя.
// WPGraphQL автогенерит корневые поля allPa<Name> для каждой таксономии.
export const GET_ATTRIBUTE_TERMS = gql`
  query GetAttributeTerms {
    allPaFuelType(first: 100) { nodes { name slug } }
    allPaEquipmentType(first: 100) { nodes { name slug } }
    allPaSteamRoomVolume(first: 100) { nodes { name slug } }
    allPaPower(first: 100) { nodes { name slug } }
    allPaVoltage(first: 100) { nodes { name slug } }
    allPaCladdingMaterial(first: 100) { nodes { name slug } }
    allPaUsageClass(first: 100) { nodes { name slug } }
    allPaRoomType(first: 100) { nodes { name slug } }
    allPaSeries(first: 100) { nodes { name slug } }
  }
`;

export const GET_PRODUCT_BY_SLUG = gql`
  query GetProductBySlug($slug: ID!) {
    product(id: $slug, idType: SLUG) {
      databaseId
      name
      slug
      description
      shortDescription
      ... on SimpleProduct {
        hwsPriceOnRequest
        price
        regularPrice
        salePrice
        sku
        image {
          sourceUrl
          altText
          hwsOptimizedUrl
        }
        galleryImages {
          nodes {
            sourceUrl
            altText
            hwsOptimizedUrl
          }
        }
        productCategories {
          nodes {
            name
            slug
            parent {
              node { name slug }
            }
          }
        }
        productBrands {
          nodes {
            name
            slug
          }
        }
        hwsSpecs {
          label
          value
        }
        hwsCommerceInfo {
          deliveryTitle
          deliveryText
          paymentTitle
          paymentText
          warrantyTitle
          warrantyText
          note
        }
        hwsFacingOptions {
          label
          iconUrl
          slug
          isActive
        }
        attributes {
          nodes {
            name
            options
          }
        }
      }
      ... on VariableProduct {
        hwsPriceOnRequest
        price
        regularPrice
        salePrice
        sku
        image {
          sourceUrl
          altText
          hwsOptimizedUrl
        }
        galleryImages {
          nodes {
            sourceUrl
            altText
            hwsOptimizedUrl
          }
        }
        productCategories {
          nodes {
            name
            slug
            parent {
              node { name slug }
            }
          }
        }
        productBrands {
          nodes {
            name
            slug
          }
        }
        hwsSpecs {
          label
          value
        }
        hwsCommerceInfo {
          deliveryTitle
          deliveryText
          paymentTitle
          paymentText
          warrantyTitle
          warrantyText
          note
        }
        hwsFacingOptions {
          label
          iconUrl
          slug
          isActive
        }
        hwsVariantGroups {
          key
          label
          options {
            value
            slug
            priceModifier
          }
        }
        variations(first: 100) {
          nodes {
            databaseId
            name
            sku
            price
            image {
              sourceUrl
              hwsOptimizedUrl
            }
            attributes {
              nodes {
                name
                value
              }
            }
          }
        }
      }
    }
  }
`;

export const GET_PRODUCT_CATEGORIES = gql`
  query GetProductCategories {
    productCategories(where: { parent: 0, hideEmpty: false }) {
      nodes {
        databaseId
        name
        slug
        hwsSubtitle
        hwsImageUrl
        image {
          sourceUrl
          altText
        }
        count
        children {
          nodes {
            databaseId
            name
            slug
            hwsSubtitle
            hwsImageUrl
            image {
              sourceUrl
              altText
            }
            count
          }
        }
      }
    }
  }
`;

export const GET_PRODUCT_CATEGORY_BY_SLUG = gql`
  query GetProductCategoryBySlug($slug: ID!) {
    productCategory(id: $slug, idType: SLUG) {
      databaseId
      name
      slug
      count
      parent {
        node { name slug }
      }
    }
  }
`;

export const GET_PRODUCT_BRANDS = gql`
  query GetProductBrands {
    productBrands(where: { hideEmpty: true }) {
      nodes {
        name
        slug
        logoUrl
      }
    }
  }
`;

export const GET_FEATURED_PRODUCTS = gql`
  query GetFeaturedProducts($first: Int = 3) {
    products(first: $first, where: { status: "publish", featured: true }) {
      nodes {
        databaseId
        name
        slug
        ... on Product {
          date
        }
        ... on SimpleProduct {
          hwsPriceOnRequest
          price
          regularPrice
          salePrice
          image {
            sourceUrl
            altText
            hwsOptimizedUrl
          }
          galleryImages {
            nodes {
              sourceUrl
              altText
              hwsOptimizedUrl
            }
          }
          productCategories {
            nodes {
              name
              slug
            }
          }
          hwsFacingOptions {
            label
            iconUrl
            slug
            isActive
          }
        }
        ... on VariableProduct {
          hwsPriceOnRequest
          price
          regularPrice
          salePrice
          image {
            sourceUrl
            altText
            hwsOptimizedUrl
          }
          galleryImages {
            nodes {
              sourceUrl
              altText
              hwsOptimizedUrl
            }
          }
          productCategories {
            nodes {
              name
              slug
            }
          }
          hwsFacingOptions {
            label
            iconUrl
            slug
            isActive
          }
        }
      }
    }
  }
`;

export const GET_PRODUCT_SLUGS = gql`
  query GetProductSlugs($first: Int = 100) {
    products(first: $first, where: { status: "publish" }) {
      nodes {
        slug
      }
    }
  }
`;

export const GET_CONTACT_CHANNELS = gql`
  query GetContactChannels {
    hwsContactChannels {
      whatsappNumber
      telegramUsername
    }
  }
`;

export const GET_POSTS = gql`
  query GetPosts($categoryName: String, $first: Int = 100) {
    posts(first: $first, where: { categoryName: $categoryName, status: PUBLISH }) {
      nodes {
        databaseId
        title
        slug
        date
        excerpt
        content
        author {
          node {
            name
            nickname
          }
        }
        tags {
          nodes {
            name
            slug
          }
        }
        featuredImage {
          node {
            sourceUrl
            altText
          }
        }
      }
    }
  }
`;

export const GET_POST_BY_SLUG = gql`
  query GetPostBySlug($slug: ID!) {
    post(id: $slug, idType: SLUG) {
      databaseId
      title
      slug
      date
      content
      author {
        node {
          name
          nickname
          avatar {
            url
          }
        }
      }
      tags {
        nodes {
          name
          slug
        }
      }
      categories {
        nodes {
          name
          slug
        }
      }
      featuredImage {
        node {
          sourceUrl
          altText
        }
      }
    }
  }
`;

export const GET_POST_SLUGS = gql`
  query GetPostSlugs($categoryName: String, $first: Int = 100) {
    posts(first: $first, where: { categoryName: $categoryName, status: PUBLISH }) {
      nodes {
        slug
      }
    }
  }
`;
