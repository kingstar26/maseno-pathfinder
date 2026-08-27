import { useQuery } from "@tanstack/react-query";

/**
 * Deliberately inert until the PHP API contract supplies a real endpoint.
 * This prevents the UI from inventing backend routes or displaying fabricated data.
 */
export function useBackendCollection<T>(key: string) {
  return {
    configured: false,
    ...useQuery<T[]>({
      queryKey: ["mugts", key],
      queryFn: async () => [],
      enabled: false,
      retry: false,
    }),
  };
}