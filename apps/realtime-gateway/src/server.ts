import http from "http";
import crypto from "crypto";
import express from "express";
import cors from "cors";
import { Server } from "socket.io";
import dotenv from "dotenv";

dotenv.config();

const PORT = parseInt(process.env.PORT || "4000", 10);
const CORS_ORIGIN = process.env.CORS_ORIGIN || "*";
const SIGNING_KEY = process.env.GATEWAY_SIGNING_KEY || "";

if (!SIGNING_KEY) {
  // eslint-disable-next-line no-console
  console.warn("[gateway] GATEWAY_SIGNING_KEY missing. Token verification will fail.");
}

function b64urlToBuffer(input: string): Buffer {
  const pad = "=".repeat((4 - (input.length % 4)) % 4);
  const base64 = (input + pad).replace(/-/g, "+").replace(/_/g, "/");
  return Buffer.from(base64, "base64");
}

function safeJsonParse<T>(s: string): T | null {
  try {
    return JSON.parse(s) as T;
  } catch {
    return null;
  }
}

type TokenPayload = {
  iss: string;
  iat: number;
  exp: number;
  tenant_id: number;
  user_id: number;
  role?: string;
};

function verifyRealtimeToken(token: string): TokenPayload | null {
  const parts = token.split(".");
  if (parts.length !== 3) return null;
  const [h, p, s] = parts;

  const header = safeJsonParse<Record<string, unknown>>(b64urlToBuffer(h).toString("utf8"));
  if (!header || header.alg !== "HS256") return null;

  const payload = safeJsonParse<TokenPayload>(b64urlToBuffer(p).toString("utf8"));
  if (!payload) return null;

  const now = Math.floor(Date.now() / 1000);
  if (!payload.exp || payload.exp < now) return null;
  if (!payload.tenant_id || !payload.user_id) return null;

  const data = `${h}.${p}`;
  const expected = crypto.createHmac("sha256", SIGNING_KEY).update(data).digest();
  const actual = b64urlToBuffer(s);
  if (expected.length !== actual.length) return null;
  if (!crypto.timingSafeEqual(expected, actual)) return null;

  return payload;
}

function threadRoom(tenantId: number, threadId: number): string {
  // Tenant prefix zorunlu: cross-tenant dinleme riski olmaz.
  return `thread:${tenantId}:${threadId}`;
}

function tenantRoom(tenantId: number): string {
  return `tenant:${tenantId}`;
}

function userRoom(tenantId: number, userId: number): string {
  return `user:${tenantId}:${userId}`;
}

const app = express();
app.use(cors({ origin: CORS_ORIGIN === "*" ? true : CORS_ORIGIN, credentials: true }));

app.get("/health", (_req, res) => {
  res.json({ ok: true, ts: Date.now() });
});

const server = http.createServer(app);
const io = new Server(server, {
  cors: { origin: CORS_ORIGIN === "*" ? true : CORS_ORIGIN, credentials: true },
});

io.use((socket, next) => {
  const token =
    (socket.handshake.auth?.token as string | undefined) ||
    (socket.handshake.headers["authorization"] as string | undefined)?.replace(/^Bearer\s+/i, "");

  if (!token) return next(new Error("Missing token"));
  const payload = verifyRealtimeToken(token);
  if (!payload) return next(new Error("Invalid token"));

  socket.data.user = payload;
  next();
});

io.on("connection", (socket) => {
  const user = socket.data.user as TokenPayload;
  socket.join(tenantRoom(user.tenant_id));
  socket.join(userRoom(user.tenant_id, user.user_id));

  socket.on("join_thread", (data: { thread_id: number }) => {
    const threadId = Number(data?.thread_id);
    if (!Number.isFinite(threadId) || threadId <= 0) return;
    socket.join(threadRoom(user.tenant_id, threadId));
  });

  socket.on("leave_thread", (data: { thread_id: number }) => {
    const threadId = Number(data?.thread_id);
    if (!Number.isFinite(threadId) || threadId <= 0) return;
    socket.leave(threadRoom(user.tenant_id, threadId));
  });
});

function verifyInternalSignature(rawBody: string, ts: string, signature: string): boolean {
  if (!ts || !signature) return false;
  const data = `${ts}.${rawBody}`;
  const expected = crypto.createHmac("sha256", SIGNING_KEY).update(data).digest("hex");
  const a = Buffer.from(expected, "hex");
  const b = Buffer.from(signature, "hex");
  if (a.length !== b.length) return false;
  return crypto.timingSafeEqual(a, b);
}

// Raw body capture for signature check
app.post(
  "/internal/broadcast",
  express.raw({ type: "application/json", limit: "2mb" }),
  (req, res) => {
    const ts = (req.header("x-marka-timestamp") || "").trim();
    const sig = (req.header("x-marka-signature") || "").trim();
    const rawBody = (req.body instanceof Buffer ? req.body.toString("utf8") : "") || "";

    if (!SIGNING_KEY) return res.status(500).json({ ok: false, error: "GATEWAY_SIGNING_KEY missing" });
    if (!verifyInternalSignature(rawBody, ts, sig)) {
      return res.status(401).json({ ok: false, error: "Invalid signature" });
    }

    const body = safeJsonParse<{
      tenant_id: number;
      rooms?: Array<{ type: "tenant" | "thread" | "user"; id: number }>;
      event: string;
      payload: unknown;
    }>(rawBody);

    if (!body || !body.tenant_id || !body.event) {
      return res.status(422).json({ ok: false, error: "Invalid payload" });
    }

    const tenantId = Number(body.tenant_id);
    const rooms = body.rooms || [];

    for (const r of rooms) {
      if (r.type === "tenant") io.to(tenantRoom(tenantId)).emit(body.event, body.payload);
      if (r.type === "thread") io.to(threadRoom(tenantId, Number(r.id))).emit(body.event, body.payload);
      if (r.type === "user") io.to(userRoom(tenantId, Number(r.id))).emit(body.event, body.payload);
    }

    return res.json({ ok: true });
  }
);

// IMPORTANT:
// Keep JSON body parser AFTER /internal/broadcast.
// Otherwise express.json() will consume the stream and express.raw() won't see the body,
// making signature verification fail (401) even with correct keys.
app.use(express.json({ limit: "2mb" }));

server.listen(PORT, () => {
  // eslint-disable-next-line no-console
  console.log(`[gateway] listening on :${PORT}`);
});

