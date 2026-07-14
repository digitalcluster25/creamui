import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  reactStrictMode: true,
  output: "standalone",
  async rewrites() {
    return [
      {
        source: "/wp-content/uploads/:path*",
        destination: "http://wordpress/wp-content/uploads/:path*",
      },
    ];
  },
  turbopack: {
    root: __dirname,
  },
  images: {
    formats: ["image/avif", "image/webp"],
    qualities: [50, 60, 68, 75],
    deviceSizes: [360, 640, 750, 828, 1080, 1200, 1600],
    imageSizes: [32, 48, 64, 96, 128, 256, 384],
    minimumCacheTTL: 60 * 60 * 24 * 30,
    remotePatterns: [
      {
        protocol: "https",
        hostname: "wpsandbox.spaces.community",
      },
      {
        protocol: "https",
        hostname: "colabrio.ams3.cdn.digitaloceanspaces.com",
      },
    ],
  },
};

export default nextConfig;
