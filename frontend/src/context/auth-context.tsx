import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from "react";
import toast from "react-hot-toast";
import type { User, UserRole } from "@/types";
import { api, isApiConfigured } from "@/lib/api";
import { changeMockPassword, loginMockUser, registerMockUser } from "@/lib/mock-auth";
interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  hydrated: boolean;
  login: (email: string, password: string) => Promise<User>;
  register: (payload: RegisterPayload) => Promise<User>;
  updateUser: (changes: Partial<User>) => void;
  logout: () => void;
  changePassword: (currentPassword: string, newPassword: string) => Promise<void>;
}
export interface RegisterPayload {
  first_name: string;
  last_name: string;
  email: string;
  password: string;
  programme?: string;
  graduation_year?: number;
  role?: UserRole;
}
const AuthContext = createContext<AuthState | null>(null);
const STORAGE_USER = "mugts.user";
const STORAGE_TOKEN = "mugts.token";
export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [hydrated, setHydrated] = useState(false);
  useEffect(() => {
    try {
      const raw = window.localStorage.getItem(STORAGE_USER);
      if (raw) {
        const parsed = JSON.parse(raw) as User;
        console.log("[AuthContext] Hydrated user from localStorage:", parsed);
        setUser(parsed);
      }
    } catch {
      // Storage may be unavailable in privacy-restricted browsing contexts.
    }
    setHydrated(true);
  }, []);
  const persist = useCallback((next: User | null, token?: string) => {
    if (next) {
      window.localStorage.setItem(STORAGE_USER, JSON.stringify(next));
      if (token) window.localStorage.setItem(STORAGE_TOKEN, token);
    } else {
      window.localStorage.removeItem(STORAGE_USER);
      window.localStorage.removeItem(STORAGE_TOKEN);
    }
    setUser(next);
  }, []);
  const login = useCallback<AuthState["login"]>(
    async (email, password) => {
      const data = isApiConfigured()
        ? (await api.post<{ user: User; token?: string }>("/auth/login", { email, password })).data
        : await loginMockUser(email, password);
      console.log("[AuthContext] Login response user object:", data.user);
      persist(data.user, data.token);
      return data.user;
    },
    [persist],
  );
  const register = useCallback<AuthState["register"]>(
    async (payload) => {
      const data = isApiConfigured()
        ? (await api.post<{ user: User; token?: string }>("/auth/register", payload)).data
        : await registerMockUser(payload);
      persist(data.user, data.token);
      return data.user;
    },
    [persist],
  );
  const updateUser = useCallback((changes: Partial<User>) => {
    console.log("[AuthContext] updateUser() invoked with changes payload:", changes);
    setUser((current) => {
      if (!current) return current;
      const cleanChanges = Object.fromEntries(
        Object.entries(changes).filter(([_, v]) => v !== undefined)
      );
      if (!cleanChanges.avatar_url && current.avatar_url && cleanChanges.avatar_url !== null) {
        delete cleanChanges.avatar_url;
      }
      const next = { ...current, ...cleanChanges };
      console.log("[AuthContext] updateUser resulting next user state:", next);
      if (
        next.id === current.id &&
        next.first_name === current.first_name &&
        next.last_name === current.last_name &&
        next.email === current.email &&
        next.role === current.role &&
        next.avatar_url === current.avatar_url &&
        next.last_login_at === current.last_login_at &&
        next.programme === current.programme &&
        next.graduation_year === current.graduation_year
      ) {
        return current;
      }
      window.localStorage.setItem(STORAGE_USER, JSON.stringify(next));
      return next;
    });
  }, []);
  const logout = useCallback(() => persist(null), [persist]);

  useEffect(() => {
    if (!user) return;

    const INACTIVITY_TIMEOUT = 5 * 60 * 1000; // 5 minutes
    let timer: NodeJS.Timeout | number;

    const handleAutoLogout = () => {
      persist(null);
      toast.error("Your session expired due to inactivity. Please sign in again.");
      window.location.href = "/login";
    };

    const resetTimer = () => {
      if (timer) clearTimeout(timer);
      timer = setTimeout(handleAutoLogout, INACTIVITY_TIMEOUT);
    };

    const events = ["mousemove", "click", "mousedown", "keydown", "scroll", "touchstart", "touchmove"];
    events.forEach((evt) => window.addEventListener(evt, resetTimer, { passive: true }));

    resetTimer();

    return () => {
      if (timer) clearTimeout(timer);
      events.forEach((evt) => window.removeEventListener(evt, resetTimer));
    };
  }, [user, persist]);

  const changePassword = useCallback(
    async (currentPassword: string, newPassword: string) => {
      if (!user) throw new Error("You need to sign in again before changing your password.");
      if (isApiConfigured()) {
        await api.post("/auth/change-password", {
          current_password: currentPassword,
          new_password: newPassword,
        });
        return;
      }
      await changeMockPassword(user.email, currentPassword, newPassword);
    },
    [user],
  );
  const value = useMemo<AuthState>(
    () => ({
      user,
      isAuthenticated: Boolean(user),
      hydrated,
      login,
      register,
      updateUser,
      logout,
      changePassword,
    }),
    [user, hydrated, login, register, updateUser, logout, changePassword],
  );
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used inside <AuthProvider>");
  return ctx;
}
