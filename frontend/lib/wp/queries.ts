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
          price
          regularPrice
          salePrice
          image {
            sourceUrl
            altText
          }
          galleryImages {
            nodes {
              sourceUrl
              altText
            }
          }
          productCategories {
            nodes {
              name
              slug
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
          price
          regularPrice
          salePrice
          image {
            sourceUrl
            altText
          }
          galleryImages {
            nodes {
              sourceUrl
              altText
            }
          }
          productCategories {
            nodes {
              name
              slug
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

export const GET_PRODUCT_BY_SLUG = gql`
  query GetProductBySlug($slug: ID!) {
    product(id: $slug, idType: SLUG) {
      databaseId
      name
      slug
      description
      shortDescription
      ... on SimpleProduct {
        price
        regularPrice
        salePrice
        sku
        image {
          sourceUrl
          altText
        }
        galleryImages {
          nodes {
            sourceUrl
            altText
          }
        }
        productCategories {
          nodes {
            name
            slug
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
        price
        regularPrice
        salePrice
        sku
        image {
          sourceUrl
          altText
        }
        galleryImages {
          nodes {
            sourceUrl
            altText
          }
        }
        productCategories {
          nodes {
            name
            slug
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
            priceModifier
          }
        }
        variations {
          nodes {
            databaseId
            name
            price
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
    productCategories(where: { parent: 0, hideEmpty: true }) {
      nodes {
        databaseId
        name
        slug
        count
        children {
          nodes {
            databaseId
            name
            slug
            count
          }
        }
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
