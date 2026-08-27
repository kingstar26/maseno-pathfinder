import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from "react";

export type ThemePreference = "light" | "dark";
type ThemeContextValue = {
  theme: ThemePreference;
  resolvedTheme: ThemePreference;
  setTheme: (theme: ThemePreference) => void;
};
const ThemeContext = createContext<ThemeContextValue | null>(null);
const THEME_KEY = "mugts.theme";

export function ThemeProvider({ children }: { children: ReactNode }) {
  const [theme, setThemeState] = useState<ThemePreference>("light");

  useEffect(() => {
    const saved = window.localStorage.getItem(THEME_KEY);
    setThemeState(saved === "dark" ? "dark" : "light");
  }, []);

  useEffect(() => {
    document.documentElement.classList.toggle("dark", theme === "dark");
    document.documentElement.style.colorScheme = theme;
  }, [theme]);

  const setTheme = (next: ThemePreference) => {
    setThemeState(next);
    window.localStorage.setItem(THEME_KEY, next);
  };

  return (
    <ThemeContext.Provider
      value={useMemo(() => ({ theme, resolvedTheme: theme, setTheme }), [theme])}
    >
      {children}
    </ThemeContext.Provider>
  );
}

export function useTheme() {
  const context = useContext(ThemeContext);
  if (!context) throw new Error("useTheme must be used within ThemeProvider");
  return context;
}
