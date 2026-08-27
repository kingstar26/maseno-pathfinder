import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import tailwindcss from "@tailwindcss/vite";
import { tanstackStart } from "@tanstack/react-start/plugin/vite";

export default defineConfig({
  plugins: [tanstackStart({ server: { entry: "server" } }), react(), tailwindcss()],
  resolve: { tsconfigPaths: true },
  server: {
    proxy: {
      '/maseno-pathfinder/backend': {
        target: 'http://localhost',
        changeOrigin: true,
        configure: (proxy) => {
          proxy.on('proxyReq', (proxyRequest, request) => {
            const authorization = request.headers.authorization;
            if (authorization) proxyRequest.setHeader('Authorization', authorization);
          });
        },
      },
    },
  },
});