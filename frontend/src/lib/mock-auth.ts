import type { RegisterPayload } from "@/context/auth-context";
import type { User } from "@/types";

const MOCK_USERS_KEY = "mugts.mock.users";
type MockAccount = User & { password_hash: string };

function readAccounts(): MockAccount[] {
  try {
    const raw = window.localStorage.getItem(MOCK_USERS_KEY);
    return raw ? (JSON.parse(raw) as MockAccount[]) : [];
  } catch {
    return [];
  }
}
function writeAccounts(accounts: MockAccount[]) {
  window.localStorage.setItem(MOCK_USERS_KEY, JSON.stringify(accounts));
}
async function hashPassword(password: string): Promise<string> {
  const digest = await window.crypto.subtle.digest("SHA-256", new TextEncoder().encode(password));
  return Array.from(new Uint8Array(digest), (byte) => byte.toString(16).padStart(2, "0")).join("");
}
function createToken() {
  return `mock.${window.crypto.randomUUID()}`;
}
function toUser(account: MockAccount): User {
  const { password_hash: _passwordHash, ...user } = account;
  return user;
}

async function ensureSeedAccounts(): Promise<MockAccount[]> {
  const accounts = readAccounts();
  const seeds: Omit<MockAccount, "password_hash">[] = [
    {
      id: "local-graduate",
      first_name: "Graduate",
      last_name: "User",
      email: "graduate@gmail.com",
      role: "graduate",
      programme: null,
      graduation_year: null,
      last_login_at: null,
      avatar_url: null,
    },
    {
      id: "local-management",
      first_name: "Management",
      last_name: "User",
      email: "management@gmail.com",
      role: "management",
      programme: null,
      graduation_year: null,
      last_login_at: null,
      avatar_url: null,
    },
    {
      id: "local-administrator",
      first_name: "Administrator",
      last_name: "User",
      email: "administrator@gmail.com",
      role: "administrator",
      programme: null,
      graduation_year: null,
      last_login_at: null,
      avatar_url: null,
    },
  ];
  let changed = false;
  for (const seed of seeds) {
    const existingAccount = accounts.find((account) => account.email.toLowerCase() === seed.email);
    if (!existingAccount) {
      accounts.push({ ...seed, password_hash: await hashPassword("Password@123") });
      changed = true;
    } else if (existingAccount.role !== seed.role) {
      // Keep previously stored role assignments consistent.
      existingAccount.role = seed.role;
      changed = true;
    }
  }
  if (changed) writeAccounts(accounts);
  return accounts;
}

export async function registerMockUser(
  payload: RegisterPayload,
): Promise<{ user: User; token: string }> {
  const email = payload.email.trim().toLowerCase();
  const accounts = await ensureSeedAccounts();
  if (accounts.some((account) => account.email.toLowerCase() === email))
    throw new Error("An account already exists for this email address.");
  const account: MockAccount = {
    id: window.crypto.randomUUID(),
    first_name: payload.first_name.trim(),
    last_name: payload.last_name.trim(),
    email,
    role: "graduate",
    programme: payload.programme?.trim() || null,
    graduation_year: payload.graduation_year ?? null,
    last_login_at: new Date().toISOString(),
    avatar_url: null,
    password_hash: await hashPassword(payload.password),
  };
  accounts.push(account);
  writeAccounts(accounts);
  return { user: toUser(account), token: createToken() };
}

export async function loginMockUser(
  email: string,
  password: string,
): Promise<{ user: User; token: string }> {
  const account = (await ensureSeedAccounts()).find(
    (candidate) => candidate.email.toLowerCase() === email.trim().toLowerCase(),
  );
  if (!account || account.password_hash !== (await hashPassword(password)))
    throw new Error("Incorrect email or password.");
  account.last_login_at = new Date().toISOString();
  const accounts = readAccounts().map((candidate) =>
    candidate.id === account.id ? account : candidate,
  );
  writeAccounts(accounts);
  return { user: toUser(account), token: createToken() };
}
export async function changeMockPassword(
  email: string,
  currentPassword: string,
  newPassword: string,
): Promise<void> {
  const accounts = await ensureSeedAccounts();
  const account = accounts.find(
    (candidate) => candidate.email.toLowerCase() === email.toLowerCase(),
  );
  if (!account || account.password_hash !== (await hashPassword(currentPassword))) {
    throw new Error("Your current password is incorrect.");
  }
  account.password_hash = await hashPassword(newPassword);
  writeAccounts(accounts);
}
