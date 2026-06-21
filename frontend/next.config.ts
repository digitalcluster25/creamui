import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  reactStrictMode: true,
  turbopack: {
    root: __dirname,
  },
  images: {
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
